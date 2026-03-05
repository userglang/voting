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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        match ($this->filters['vote_type'] ?? 'all') {
            'online'  => $query->where('online_vote', true),
            'offline' => $query->where('online_vote', false),
            default   => null,
        };

        // is_valid = true  → valid votes only
        // is_valid = false → invalid votes only
        // is_valid = all   → no filter
        match ($this->filters['is_valid'] ?? 'all') {
            'valid'   => $query->where('is_valid', true),
            'invalid' => $query->where('is_valid', false),
            default   => null,
        };

        if (!empty($this->filters['position_id'])) {
            $query->whereHas('candidate', function ($q) {
                $q->where('position_id', $this->filters['position_id']);
            });
        }

        if (!empty($this->filters['date_from'])) {
            $timeFrom = $this->filters['time_from'] ?? '00:00';
            $query->where('created_at', '>=', $this->filters['date_from'] . ' ' . $timeFrom . ':00');
        }

        if (!empty($this->filters['date_to'])) {
            $timeTo = $this->filters['time_to'] ?? '23:59';
            $query->where('created_at', '<=', $this->filters['date_to'] . ' ' . $timeTo . ':59');
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
            'Branch ID',
            'Member Code',
            'Member Complete Name',
            'Last Name',
            'First Name',
            'Middle Name',
            'Candidate ID',
            'Candidate Name',
            'Position',
            'Vote Type',
            'Valid',
            'Date Cast',
            'Time Cast',
        ];
    }

    public function map($vote): array
    {
        return [
            $vote->id,
            $vote->control_number,
            $vote->branch_number          ?? 'N/A',
            $vote->branch?->branch_name   ?? 'N/A',
            $vote->branch?->id            ?? 'N/A',
            $vote->member_code            ?? 'N/A',
            $vote->member?->full_name     ?? 'N/A',
            $vote->member?->last_name     ?? 'N/A',
            $vote->member?->first_name    ?? 'N/A',
            $vote->member?->middle_name   ?? 'N/A',
            $vote->candidate_id           ?? 'N/A',
            $vote->candidate?->first_name  . ' ' .  $vote->candidate?->last_name ?? 'N/A',
            $vote->candidate?->position?->title ?? 'N/A',
            $vote->online_vote ? 'Online' : 'Offline',
            $vote->is_valid    ? 'Yes'    : 'No',
            $vote->created_at?->format('Y-m-d') ?? 'N/A',
            $vote->created_at?->format('H:i:s') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 12,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,  // ID (UUID)
            'B' => 18,  // Control Number
            'C' => 18,  // Branch Number
            'D' => 30,  // Branch Name
            'E' => 38,  // Member Code
            'F' => 15,  // Member Code
            'G' => 35,  // Member Complete Name
            'H' => 20,  // Last Name
            'I' => 20,  // First Name
            'J' => 20,  // Middle Name
            'K' => 38,  // Candidate ID (UUID)
            'L' => 35,  // Candidate Name
            'M' => 25,  // Position
            'N' => 12,  // Vote Type
            'O' => 10,  // Valid
            'P' => 15,  // Date Cast
            'Q' => 12,  // Time Cast
        ];
    }

    public function title(): string
    {
        return 'Votes Report';
    }
}
