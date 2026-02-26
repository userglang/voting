<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MembersExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize,
    WithChunkReading
{
    public function __construct(protected array $filters = []) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function query(): Builder
    {
        $isActive     = $this->filters['is_active']     ?? 'all';
        $isMigs       = $this->filters['is_migs']       ?? 'all';
        $isRegistered = $this->filters['is_registered'] ?? 'all';

        return Member::query()
            ->select([
                'id', 'code', 'cid', 'branch_number',
                'last_name', 'first_name', 'middle_name',
                'birth_date', 'gender', 'marital_status',
                'religion', 'address', 'contact_number', 'email',
                'tin', // Added TIN here
                'occupation', 'share_account', 'share_amount',
                'is_migs', 'is_active', 'is_registered',
                'process_type', 'registration_type', 'membership_date',
                'updated_at',
            ])
            ->with([
                'branch:id,branch_number,branch_name',
            ])
            ->when(!empty($this->filters['branch_number']), fn ($q) => $q->where('branch_number', $this->filters['branch_number']))
            ->when(!empty($this->filters['gender']),        fn ($q) => $q->where('gender', $this->filters['gender']))
            ->when($isMigs === 'yes',                       fn ($q) => $q->where('is_migs', true))
            ->when($isMigs === 'no',                        fn ($q) => $q->where('is_migs', false))
            ->when($isActive === 'active',                  fn ($q) => $q->where('is_active', true))
            ->when($isActive === 'inactive',                fn ($q) => $q->where('is_active', false))
            ->when($isRegistered === 'registered',          fn ($q) => $q->where('is_registered', true))
            ->when($isRegistered === 'not_registered',      fn ($q) => $q->where('is_registered', false))
            ->orderBy('branch_number')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Code', 'CID', 'Branch Number', 'Branch ID', 'Branch Name',
            'Last Name', 'First Name', 'Middle Name', 'Full Name',
            'Birth Date', 'Age', 'Gender', 'Marital Status',
            'Religion', 'Address', 'Contact Number', 'Email', 'TIN', // Added TIN here
            'Occupation', 'Share Account', 'Share Amount',
            'MIGS', 'Active', 'Registered',
            'Process Type', 'Registration Type', 'Membership Date',
            'Registered Date', 'Registered Time',
        ];
    }

    public function map($m): array
    {
        return [
            $m->code                                              ?? 'N/A',
            $m->cid                                               ?? 'N/A',
            $m->branch_number                                     ?? 'N/A',
            $m->branch?->id                                       ?? 'N/A',
            $m->branch?->branch_name                              ?? 'N/A',
            $m->last_name                                         ?? 'N/A',
            $m->first_name                                        ?? 'N/A',
            $m->middle_name                                       ?? 'N/A',
            trim("{$m->last_name}, {$m->first_name} {$m->middle_name}"),
            $m->birth_date?->format('Y-m-d')                      ?? 'N/A',
            $m->birth_date ? (int) $m->birth_date->diffInYears(now()) : 'N/A',
            $m->gender                                            ?? 'N/A',
            $m->marital_status                                    ?? 'N/A',
            $m->religion                                          ?? 'N/A',
            $m->address                                           ?? 'N/A',
            $m->contact_number                                    ?? 'N/A',
            $m->email                                             ?? 'N/A',
            $m->tin                                               ?? 'N/A', // Added TIN here
            $m->occupation                                        ?? 'N/A',
            $m->share_account                                     ?? 'N/A',
            $m->share_amount ? number_format($m->share_amount, 2) : '0.00',
            $m->is_migs       ? 'YES' : 'NO',
            $m->is_active     ? 'YES' : 'NO',
            $m->is_registered ? 'YES' : 'NO',
            $m->process_type                                      ?? 'N/A',
            $m->registration_type                                 ?? 'N/A',
            $m->membership_date?->format('Y-m-d')                 ?? 'N/A',
            $m->updated_at?->format('Y-m-d')                      ?? 'N/A',
            $m->updated_at?->format('H:i:s')                      ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 15, 'C' => 15, 'D' => 38, 'E' => 25,
            'F' => 20, 'G' => 20, 'H' => 20, 'I' => 35,
            'J' => 15, 'K' => 8,  'L' => 10, 'M' => 15,
            'N' => 15, 'O' => 35, 'P' => 15, 'Q' => 25,
            'R' => 20, // Added TIN column width
            'S' => 20, 'T' => 15, 'U' => 15, 'V' => 8,
            'W' => 8,  'X' => 12, 'Y' => 25, 'Z' => 20,
            'AA' => 18, 'AB' => 15, 'AC' => 15,
        ];
    }

    public function title(): string
    {
        return 'Members Report';
    }
}
