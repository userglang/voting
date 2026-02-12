<?php

namespace App\Imports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MembersImport implements ToCollection, WithHeadingRow
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $branchNumber;

    public function __construct($branchNumber = null)
    {
        $this->branchNumber = $branchNumber;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $this->processRow($row->toArray());
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    protected function processRow(array $row)
    {
        $cid = trim($row['cid'] ?? '');

        if (empty($cid)) {
            $this->skippedCount++;
            return;
        }

        // Parse names
        $firstName = trim($row['first_name'] ?? '');
        $lastName = trim($row['last_name'] ?? '');
        $middleName = trim($row['middle_name'] ?? '');

        if (!empty($row['full_name'])) {
            $parsed = $this->parseFullName(trim($row['full_name']));
            $firstName = $firstName ?: $parsed['first_name'];
            $lastName = $lastName ?: $parsed['last_name'];
            $middleName = $middleName ?: $parsed['middle_name'];
        }

        if (empty($firstName) || empty($lastName)) {
            $this->skippedCount++;
            return;
        }

        // Check existing by CID and branch
        $existing = Member::where('cid', $cid)
            ->where('branch_number', $this->branchNumber)
            ->first();

        // Generate code based on branch and CID
        $code = $this->generateCode($this->branchNumber, $cid, $lastName);

        $data = [
            'code' => $code,
            'cid' => $cid,
            'branch_number' => $this->branchNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'birth_date' => $this->parseDate($row['birth_date'] ?? null),
            'gender' => trim($row['gender'] ?? ''),
            'share_account' => trim($row['share_account'] ?? ''),
            'is_migs' => $this->parseBoolean($row['is_migs'] ?? false),
            'share_amount' => $this->parseDecimal($row['share_amount'] ?? 0),
            'is_active' => true,
            'is_registered' => false,
            'process_type' => 'Updating and Voting',
        ];

        if ($existing) {
            // Update existing member (code should remain the same)
            $existing->update($data);
            $this->updatedCount++;
        } else {
            Member::create($data);
            $this->importedCount++;
        }
    }

    /**
     * Generate member code
     * Format: OIC-{branch_number}-{cid}
     * Example: OIC-011-123456
     *
     * @param string $branchNumber
     * @param string $cid
     * @return string
     */
    protected function generateCode($branchNumber, $cid, $lastName)
    {
        // Pad branch number to 3 digits (e.g., 11 becomes 011)
        $branchPadded = str_pad($branchNumber, 3, '0', STR_PAD_LEFT);

        // Clean CID (remove any special characters, keep only alphanumeric)
        $cidCleaned = preg_replace('/[^A-Za-z0-9]/', '', $cid);

        // Pad CID to at least 3 characters
        $cidPadded = str_pad($cidCleaned, 3, '0', STR_PAD_LEFT);

        // Clean last name, convert to uppercase, and take first 4 characters
        $lastNameShort = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lastName)), 0, 4);

        return "OIC{$lastNameShort}-{$branchPadded}-{$cidPadded}";
    }

    protected function parseFullName($fullName)
    {
        if (empty($fullName)) {
            return ['first_name' => '', 'last_name' => '', 'middle_name' => ''];
        }

        if (strpos($fullName, ',') !== false) {
            $parts = explode(',', $fullName, 2);
            $lastName = trim($parts[0]);
            $rest = trim($parts[1] ?? '');
            $names = explode(' ', $rest);
            return [
                'first_name' => $names[0] ?? '',
                'last_name' => $lastName,
                'middle_name' => isset($names[1]) ? implode(' ', array_slice($names, 1)) : '',
            ];
        }

        $names = explode(' ', $fullName);
        $count = count($names);

        if ($count >= 3) {
            return [
                'first_name' => $names[0],
                'last_name' => $names[$count - 1],
                'middle_name' => implode(' ', array_slice($names, 1, $count - 2)),
            ];
        } elseif ($count === 2) {
            return ['first_name' => $names[0], 'last_name' => $names[1], 'middle_name' => ''];
        }

        return ['first_name' => $names[0] ?? '', 'last_name' => '', 'middle_name' => ''];
    }

    protected function parseBoolean($value)
    {
        if (is_bool($value)) return $value;
        if (empty($value)) return false;
        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    protected function parseDecimal($value)
    {
        if (empty($value)) return 0;
        return (float) preg_replace('/[^0-9.-]/', '', $value);
    }

    protected function parseDate($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getImportedCount() { return $this->importedCount; }
    public function getUpdatedCount() { return $this->updatedCount; }
    public function getSkippedCount() { return $this->skippedCount; }
    public function getFailures() { return $this->errors; }
}
