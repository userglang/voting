<?php

namespace App\Exports;

use App\Models\Branch;
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

class BranchesExport implements
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
        $status = $this->filters['is_active'] ?? 'all';

        return Branch::query()
            ->when($status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('branch_number');
    }

    public function headings(): array
    {
        return [
            'Branch Number',
            'Branch Code',
            'Branch Name',
            'Address',
            'Email',
            'Contact Person',
            'Contact Number',
            'Code',
            'Status',
            'Created At',
        ];
    }

    public function map($branch): array
    {
        return [
            $branch->branch_number,
            $branch->id,
            $branch->branch_name,
            $branch->address                          ?? 'N/A',
            $branch->email                            ?? 'N/A',
            $branch->contact_person                   ?? 'N/A',
            $branch->contact_number                   ?? 'N/A',
            $branch->code                             ?? 'N/A',
            $branch->is_active ? 'YES' : 'NO',
            $branch->created_at?->format('Y-m-d')     ?? 'N/A',
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
            'A' => 18, // Branch Number
            'B' => 15, // Branch Code
            'C' => 30, // Branch Name
            'D' => 40, // Address
            'E' => 28, // Email
            'F' => 25, // Contact Person
            'G' => 18, // Contact Number
            'H' => 12, // Code
            'I' => 10, // Status
            'J' => 15, // Created At
        ];
    }

    public function title(): string
    {
        return 'Branches Report';
    }
}
