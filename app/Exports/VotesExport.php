<?php

namespace App\Exports;

use App\Models\Vote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Contracts\Queue\ShouldQueue;

class VotesExport implements
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
        $query = Vote::query()
            ->with(['member', 'candidate.position', 'branch'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        if (!empty($this->filters['vote_type'])) {
            if ($this->filters['vote_type'] === 'online') {
                $query->where('online_vote', true);
            } elseif ($this->filters['vote_type'] === 'offline') {
                $query->where('online_vote', false);
            }
        }

        if (!empty($this->filters['position_id'])) {
            $query->whereHas('candidate', function ($q) {
                $q->where('position_id', $this->filters['position_id']);
            });
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Control Number',
            'Branch Number',
            'Branch Name',
            'Member Code',
            'Member Complete Name',
            'Last Name',
            'First Name',
            'Middle Name',
            'Candidate ID',
            'Candidate Name',
            'Position',
            'Vote Type',
            'Date Cast',
            'Time Cast',
        ];
    }

    public function map($vote): array
    {
        return [
            $vote->id,
            str_pad($vote->control_number, 6, '0', STR_PAD_LEFT),
            $vote->branch_number ?? 'N/A',
            $vote->branch?->branch_name ?? 'N/A',
            $vote->member_code ?? 'N/A',
            $vote->member?->full_name ?? 'N/A',
            $vote->member?->last_name ?? 'N/A',
            $vote->member?->first_name ?? 'N/A',
            $vote->member?->middle_name ?? 'N/A',
            $vote->candidate_id ?? 'N/A',
            $vote->candidate?->full_name ?? 'N/A',
            $vote->candidate?->position?->title ?? 'N/A',
            $vote->online_vote ? '0' : '1',
            $vote->created_at?->format('Y-m-d') ?? 'N/A',
            $vote->created_at?->format('H:i:s') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
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
            'A' => 38,  // Candidate ID (UUID)
            'B' => 18,  // Control Number
            'C' => 18,  // Branch Number
            'D' => 30,  // Branch Name
            'E' => 15,  // Member Code
            'F' => 35,  // Member Complete Name
            'G' => 20,  // Last Name
            'H' => 20,  // First Name
            'I' => 20,  // Middle Name
            'J' => 38,  // Candidate ID (UUID)
            'K' => 35,  // Candidate Name
            'L' => 25,  // Position
            'M' => 12,  // Vote Type
            'N' => 15,  // Date Cast
            'O' => 12,  // Time Cast
        ];
    }

    public function title(): string
    {
        return 'Votes Report';
    }
}
