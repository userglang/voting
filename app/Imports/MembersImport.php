<?php

namespace App\Imports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MembersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $importedCount = 0;
    protected $updatedCount  = 0;
    protected $skippedCount  = 0;
    protected $errors        = [];
    protected $branchNumber;

    public function __construct($branchNumber = null)
    {
        $this->branchNumber = $branchNumber;

        set_time_limit(0);
        ini_set('memory_limit', '512M');
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        // Normalize CIDs from this chunk (trim + cast to string)
        $cidsInChunk = $rows
            ->map(fn($r) => $this->normalizeCid($r['cid'] ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Pre-fetch existing members keyed by normalized CID
        $existingMembers = Member::where('branch_number', $this->branchNumber)
            ->whereIn('cid', $cidsInChunk)
            ->pluck('id', 'cid')
            ->toArray();

        $toInsert = [];
        $toUpdate = [];

        foreach ($rows as $row) {
            try {
                $result = $this->parseRow($row->toArray());

                if ($result === null) {
                    $this->skippedCount++;
                    continue;
                }

                if (isset($existingMembers[$result['cid']])) {
                    $result['id'] = $existingMembers[$result['cid']];
                    $toUpdate[]   = $result;
                } else {
                    $toInsert[] = $result;
                }
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        $now = now();

        // ---------------------------------------------------------------
        // BATCH INSERT — use upsert on 'code' unique key so that if the
        // same code already exists (e.g. re-import), it updates instead
        // of throwing a duplicate key error
        // ---------------------------------------------------------------
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                $chunk = array_map(function ($row) use ($now) {
                    $row['id']         = (string) Str::uuid();
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    return $row;
                }, $chunk);

                // Use upsert instead of insert so duplicate 'code' values
                // trigger an update instead of a fatal error
                Member::upsert(
                    $chunk,
                    ['code'],  // unique key — match on code
                    [          // update these columns if code already exists
                        'cid',
                        'branch_number',
                        'first_name',
                        'last_name',
                        'middle_name',
                        'birth_date',
                        'gender',
                        'share_account',
                        'is_migs',
                        'share_amount',
                        'is_active',
                        'is_registered',
                        'process_type',
                        'updated_at',
                    ]
                );
            }
            $this->importedCount += count($toInsert);
        }

        // ---------------------------------------------------------------
        // BATCH UPSERT for known existing members (matched by id)
        // ---------------------------------------------------------------
        if (!empty($toUpdate)) {
            foreach (array_chunk($toUpdate, 500) as $chunk) {
                $chunk = array_map(function ($row) use ($now) {
                    $row['updated_at'] = $now;
                    return $row;
                }, $chunk);

                Member::upsert(
                    $chunk,
                    ['id'],
                    [
                        'code',
                        'first_name',
                        'last_name',
                        'middle_name',
                        'birth_date',
                        'gender',
                        'share_account',
                        'is_migs',
                        'share_amount',
                        'is_active',
                        'is_registered',
                        'process_type',
                        'updated_at',
                    ]
                );
            }
            $this->updatedCount += count($toUpdate);
        }
    }

    /**
     * Parse and validate a single row.
     * Returns null if the row should be skipped.
     */
    protected function parseRow(array $row): ?array
    {
        $cid = $this->normalizeCid($row['cid'] ?? '');

        if (empty($cid)) {
            return null;
        }

        $firstName  = trim($row['first_name']  ?? '');
        $lastName   = trim($row['last_name']   ?? '');
        $middleName = trim($row['middle_name'] ?? '');

        if (!empty($row['full_name'])) {
            $parsed     = $this->parseFullName(trim($row['full_name']));
            $firstName  = $firstName  ?: $parsed['first_name'];
            $lastName   = $lastName   ?: $parsed['last_name'];
            $middleName = $middleName ?: $parsed['middle_name'];
        }

        if (empty($firstName) || empty($lastName)) {
            return null;
        }

        return [
            'code'          => $this->generateCode($this->branchNumber, $cid, $lastName),
            'cid'           => $cid,
            'branch_number' => $this->branchNumber,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'middle_name'   => $middleName,
            'birth_date'    => $this->parseDate($row['birth_date'] ?? null),
            'gender'        => $this->parseGender($row['gender'] ?? ''),
            'share_account' => trim($row['share_account'] ?? ''),
            'is_migs'       => $this->parseBoolean($row['is_migs'] ?? false),
            'share_amount'  => $this->parseDecimal($row['share_amount'] ?? 0),
            'is_active'     => true,
            'is_registered' => false,
            'process_type'  => 'Updating and Voting',
        ];
    }

    /**
     * Normalize CID — trim whitespace and cast to string.
     * Ensures consistent matching between Excel data and DB values.
     */
    protected function normalizeCid($value): string
    {
        return trim((string) $value);
    }

    /**
     * Generate member code.
     * Format: OIC{LAST4}-{branch_padded}-{cid_padded}
     * Example: OICSMIT-011-000123
     */
    protected function generateCode(string $branchNumber, string $cid, string $lastName): string
    {
        $branchPadded  = str_pad($branchNumber, 3, '0', STR_PAD_LEFT);
        $cidCleaned    = preg_replace('/[^A-Za-z0-9]/', '', $cid);
        $cidPadded     = str_pad($cidCleaned, 3, '0', STR_PAD_LEFT);
        $lastNameShort = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lastName)), 0, 4);

        return "OIC{$lastNameShort}-{$branchPadded}-{$cidPadded}";
    }

    /**
     * Parse full name into components.
     * Supports "Last, First Middle" and "First Middle Last" formats.
     */
    protected function parseFullName(string $fullName): array
    {
        $empty = ['first_name' => '', 'last_name' => '', 'middle_name' => ''];

        if (empty($fullName)) {
            return $empty;
        }

        if (strpos($fullName, ',') !== false) {
            [$rawLast, $rawRest] = explode(',', $fullName, 2);
            $lastName = trim($rawLast);
            $names    = array_values(array_filter(explode(' ', trim($rawRest))));

            return [
                'first_name'  => $names[0] ?? '',
                'last_name'   => $lastName,
                'middle_name' => count($names) > 1 ? implode(' ', array_slice($names, 1)) : '',
            ];
        }

        $names = array_values(array_filter(explode(' ', $fullName)));
        $count = count($names);

        if ($count >= 3) {
            return [
                'first_name'  => $names[0],
                'last_name'   => $names[$count - 1],
                'middle_name' => implode(' ', array_slice($names, 1, $count - 2)),
            ];
        }

        if ($count === 2) {
            return ['first_name' => $names[0], 'last_name' => $names[1], 'middle_name' => ''];
        }

        return array_merge($empty, ['first_name' => $names[0] ?? '']);
    }

    /**
     * Normalize gender to match ENUM('Male', 'Female').
     * Handles: M, F, male, female, MALE, FEMALE, 1, 0, etc.
     */
    protected function parseGender($value): string
    {
        if (empty($value)) return '';

        $v = strtolower(trim((string) $value));

        if (in_array($v, ['m', 'male', '1'], true))   return 'Male';
        if (in_array($v, ['f', 'female', '0'], true)) return 'Female';

        // Already correct case (Male/Female) — return as-is
        return ucfirst($v);
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        if (empty($value)) return false;

        return in_array(strtolower(trim((string) $value)), ['1', 'tRUE', 'True', 'TRUE', 'true', 'yes', 'y', 'on'], true);
    }

    protected function parseDecimal($value): float
    {
        if (empty($value)) return 0.0;

        return (float) preg_replace('/[^0-9.-]/', '', (string) $value);
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                );
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int  { return $this->updatedCount;  }
    public function getSkippedCount(): int  { return $this->skippedCount;  }
    public function getFailures(): array    { return $this->errors;        }
}
