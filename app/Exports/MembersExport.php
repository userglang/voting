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
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Member::query()
            ->with(['branch'])
            ->orderBy('branch_number')
            ->orderBy('last_name')
            ->orderBy('first_name');

        // Apply filters
        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        if (!empty($this->filters['is_migs'])) {
            if ($this->filters['is_migs'] === 'yes') {
                $query->where('is_migs', true);
            } elseif ($this->filters['is_migs'] === 'no') {
                $query->where('is_migs', false);
            }
        }

        if (!empty($this->filters['is_active'])) {
            if ($this->filters['is_active'] === 'active') {
                $query->where('is_active', true);
            } elseif ($this->filters['is_active'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (!empty($this->filters['is_registered'])) {
            if ($this->filters['is_registered'] === 'registered') {
                $query->where('is_registered', true);
            } elseif ($this->filters['is_registered'] === 'not_registered') {
                $query->where('is_registered', false);
            }
        }

        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Code',
            'CID',
            'Branch Number',
            'Branch Name',
            'Last Name',
            'First Name',
            'Middle Name',
            'Full Name',
            'Birth Date',
            'Age',
            'Gender',
            'Marital Status',
            'Religion',
            'Address',
            'Contact Number',
            'Email',
            'Occupation',
            'Share Account',
            'Share Amount',
            'MIGS',
            'Active',
            'Registered',
            'Process Type',
            'Registration Type',
            'Membership Date',
        ];
    }

    public function map($member): array
    {
        return [
            $member->code ?? 'N/A',
            $member->cid ?? 'N/A',
            $member->branch_number ?? 'N/A',
            $member->branch?->branch_name ?? 'N/A',
            $member->last_name ?? 'N/A',
            $member->first_name ?? 'N/A',
            $member->middle_name ?? 'N/A',
            $member->full_name ?? 'N/A',
            $member->birth_date?->format('Y-m-d') ?? 'N/A',
            $member->age ?? 'N/A',
            $member->gender ?? 'N/A',
            $member->marital_status ?? 'N/A',
            $member->religion ?? 'N/A',
            $member->address ?? 'N/A',
            $member->contact_number ?? 'N/A',
            $member->email ?? 'N/A',
            $member->occupation ?? 'N/A',
            $member->share_account ?? 'N/A',
            $member->share_amount ? number_format($member->share_amount, 2) : '0.00',
            $member->is_migs ? 'YES' : 'NO',
            $member->is_active ? 'YES' : 'NO',
            $member->is_registered ? 'YES' : 'NO',
            $member->process_type ?? 'N/A',
            $member->registration_type ?? 'N/A',
            $member->membership_date?->format('Y-m-d') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Code
            'B' => 15,  // CID
            'C' => 15,  // Branch Number
            'D' => 25,  // Branch Name
            'E' => 20,  // Last Name
            'F' => 20,  // First Name
            'G' => 20,  // Middle Name
            'H' => 35,  // Full Name
            'I' => 15,  // Birth Date
            'J' => 8,   // Age
            'K' => 10,  // Gender
            'L' => 15,  // Marital Status
            'M' => 15,  // Religion
            'N' => 35,  // Address
            'O' => 15,  // Contact Number
            'P' => 25,  // Email
            'Q' => 20,  // Occupation
            'R' => 15,  // Share Account
            'S' => 15,  // Share Amount
            'T' => 8,   // MIGS
            'U' => 8,   // Active
            'V' => 12,  // Registered
            'W' => 25,  // Process Type
            'X' => 20,  // Registration Type
            'Y' => 18,  // Membership Date
        ];
    }

    public function title(): string
    {
        return 'Members Report';
    }
}
