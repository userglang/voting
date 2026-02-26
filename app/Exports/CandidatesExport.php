<?php

namespace App\Exports;

use App\Models\Candidate;
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

class CandidatesExport implements
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
        return Candidate::query()
            ->with('position')
            ->when(
                !empty($this->filters['position_id']),
                fn ($q) => $q->where('position_id', $this->filters['position_id'])
            )
            ->orderBy('position_id')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Candidate Code',
            'Position Code',
            'Position',
            'Last Name',
            'First Name',
            'Middle Name',
            'Full Name',
            'Background Profile',
            'Created At',
        ];
    }

    public function map($candidate): array
    {
        return [
            $candidate->id,
            $candidate->position_id,
            $candidate->position?->title,
            $candidate->last_name,
            $candidate->first_name,
            $candidate->middle_name                          ?? 'N/A',
            $candidate->full_name,
            $candidate->background_profile                   ?? 'No background profile provided',
            $candidate->created_at?->format('Y-m-d')         ?? 'N/A',
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
            'A' => 38, // Candidate Code
            'B' => 38, // Position Code
            'C' => 30, // Position
            'D' => 20, // Last Name
            'E' => 20, // First Name
            'F' => 20, // Middle Name
            'G' => 45, // Full Name
            'H' => 50, // Background Profile
            'I' => 15, // Created At
        ];
    }

    public function title(): string
    {
        return 'Candidates Report';
    }
}
