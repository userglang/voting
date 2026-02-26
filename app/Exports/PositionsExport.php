<?php

namespace App\Exports;

use App\Models\Position;
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

class PositionsExport implements
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

        return Position::query()
            ->when($status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('priority')
            ->orderBy('title');
    }

    public function headings(): array
    {
        return [
            'Position Code',
            'Title',
            'Vacant Count',
            'Priority',
            'Status',
            'Created At',
        ];
    }

    public function map($position): array
    {
        return [
            $position->id,
            $position->title,
            $position->vacant_count ?? 0,
            $position->priority,
            $position->is_active ? 'YES' : 'NO',
            $position->created_at?->format('Y-m-d') ?? 'N/A',
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
            'A' => 38, // Position Code
            'B' => 35, // Title
            'C' => 15, // Vacant Count
            'D' => 12, // Priority
            'E' => 10, // Status
            'F' => 15, // Created At
        ];
    }

    public function title(): string
    {
        return 'Positions Report';
    }
}
