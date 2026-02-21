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
    ShouldAutoSize
{
    public function __construct(protected array $filters = []) {}

    public function query()
    {
        return Member::query()
            ->with('branch')
            ->when(!empty($this->filters['branch_number']), fn ($q) => $q->where('branch_number', $this->filters['branch_number']))
            ->when($this->filters['is_migs'] === 'yes',      fn ($q) => $q->where('is_migs', true))
            ->when($this->filters['is_migs'] === 'no',       fn ($q) => $q->where('is_migs', false))
            ->when($this->filters['is_active'] === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($this->filters['is_active'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filters['is_registered'] === 'registered',     fn ($q) => $q->where('is_registered', true))
            ->when($this->filters['is_registered'] === 'not_registered', fn ($q) => $q->where('is_registered', false))
            ->when(!empty($this->filters['gender']), fn ($q) => $q->where('gender', $this->filters['gender']))
            ->orderBy('branch_number')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Code', 'CID', 'Branch Number', 'Branch Name',
            'Last Name', 'First Name', 'Middle Name', 'Full Name',
            'Birth Date', 'Age', 'Gender', 'Marital Status',
            'Religion', 'Address', 'Contact Number', 'Email',
            'Occupation', 'Share Account', 'Share Amount',
            'MIGS', 'Active', 'Registered',
            'Process Type', 'Registration Type', 'Membership Date',
        ];
    }

    public function map($m): array
    {
        return [
            $m->code                                    ?? 'N/A',
            $m->cid                                     ?? 'N/A',
            $m->branch_number                           ?? 'N/A',
            $m->branch?->branch_name                    ?? 'N/A',
            $m->last_name                               ?? 'N/A',
            $m->first_name                              ?? 'N/A',
            $m->middle_name                             ?? 'N/A',
            $m->full_name                               ?? 'N/A',
            $m->birth_date?->format('Y-m-d')            ?? 'N/A',
            $m->age                                     ?? 'N/A',
            $m->gender                                  ?? 'N/A',
            $m->marital_status                          ?? 'N/A',
            $m->religion                                ?? 'N/A',
            $m->address                                 ?? 'N/A',
            $m->contact_number                          ?? 'N/A',
            $m->email                                   ?? 'N/A',
            $m->occupation                              ?? 'N/A',
            $m->share_account                           ?? 'N/A',
            $m->share_amount ? number_format($m->share_amount, 2) : '0.00',
            $m->is_migs       ? 'YES' : 'NO',
            $m->is_active     ? 'YES' : 'NO',
            $m->is_registered ? 'YES' : 'NO',
            $m->process_type       ?? 'N/A',
            $m->registration_type  ?? 'N/A',
            $m->membership_date?->format('Y-m-d') ?? 'N/A',
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
            'A' => 20, 'B' => 15, 'C' => 15, 'D' => 25,
            'E' => 20, 'F' => 20, 'G' => 20, 'H' => 35,
            'I' => 15, 'J' => 8,  'K' => 10, 'L' => 15,
            'M' => 15, 'N' => 35, 'O' => 15, 'P' => 25,
            'Q' => 20, 'R' => 15, 'S' => 15, 'T' => 8,
            'U' => 8,  'V' => 12, 'W' => 25, 'X' => 20,
            'Y' => 18,
        ];
    }

    public function title(): string
    {
        return 'Members Report';
    }
}
