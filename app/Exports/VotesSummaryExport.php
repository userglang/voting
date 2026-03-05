<?php

namespace App\Exports;

use App\Models\Vote;
use App\Models\Position;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VotesSummaryExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Vote::query()
            ->with(['candidate.position', 'branch']);

        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        match ($this->filters['vote_type'] ?? 'all') {
            'online'  => $query->where('online_vote', true),
            'offline' => $query->where('online_vote', false),
            default   => null,
        };

        // is_valid = valid   → valid votes only
        // is_valid = invalid → invalid votes only
        // is_valid = all     → no filter
        match ($this->filters['is_valid'] ?? 'all') {
            'valid'   => $query->where('is_valid', true),
            'invalid' => $query->where('is_valid', false),
            default   => null,
        };

        if (!empty($this->filters['date_from'])) {
            $timeFrom = $this->filters['time_from'] ?? '00:00';
            $query->where('created_at', '>=', $this->filters['date_from'] . ' ' . $timeFrom . ':00');
        }

        if (!empty($this->filters['date_to'])) {
            $timeTo = $this->filters['time_to'] ?? '23:59';
            $query->where('created_at', '<=', $this->filters['date_to'] . ' ' . $timeTo . ':59');
        }

        $votes = $query->get();

        $summary = $votes->groupBy(function ($vote) {
            return $vote->candidate->position->title;
        })->map(function ($positionVotes) {
            return $positionVotes->groupBy('candidate_id')->map(function ($candidateVotes) {
                $candidate = $candidateVotes->first()->candidate;

                return [
                    'position'      => $candidate->position->title,
                    'candidate'     => $candidate->full_name,
                    'total_votes'   => $candidateVotes->count(),
                    'online_votes'  => $candidateVotes->where('online_vote', true)->count(),
                    'offline_votes' => $candidateVotes->where('online_vote', false)->count(),
                ];
            })->values();
        })->flatten(1);

        return $summary;
    }

    public function headings(): array
    {
        return [
            'Position',
            'Candidate',
            'Total Votes',
            'Online Votes',
            'Offline Votes',
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
                    'startColor' => ['rgb' => '059669'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Votes Summary';
    }
}
