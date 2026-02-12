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

        $votes = $query->get();

        // Group by position and candidate
        $summary = $votes->groupBy(function ($vote) {
            return $vote->candidate->position->title;
        })->map(function ($positionVotes, $positionTitle) {
            return $positionVotes->groupBy('candidate_id')->map(function ($candidateVotes) {
                $candidate = $candidateVotes->first()->candidate;
                return [
                    'position' => $candidate->position->title,
                    'candidate' => $candidate->full_name,
                    'total_votes' => $candidateVotes->count(),
                    'online_votes' => $candidateVotes->where('online_vote', true)->count(),
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
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
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

    public function title(): string
    {
        return 'Votes Summary';
    }
}
