<?php

namespace App\Exports;

use App\Models\Vote;
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
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $votes = Vote::query()
            ->select('votes.*')           // explicit select prevents ambiguous columns
            ->distinct()                  // deduplicate rows inflated by any joins
            ->with([
                'candidate:id,position_id,first_name,last_name',
                'candidate.position:id,title',
            ])
            ->when(
                !empty($this->filters['branch_number']),
                fn ($q) => $q->where('branch_number', $this->filters['branch_number'])
            )
            ->when(
                ($this->filters['vote_type'] ?? 'all') === 'online',
                fn ($q) => $q->where('online_vote', true)
            )
            ->when(
                ($this->filters['vote_type'] ?? 'all') === 'offline',
                fn ($q) => $q->where('online_vote', false)
            )
            ->when(
                ($this->filters['is_valid'] ?? 'all') === 'valid',
                fn ($q) => $q->where('is_valid', true)
            )
            ->when(
                ($this->filters['is_valid'] ?? 'all') === 'invalid',
                fn ($q) => $q->where('is_valid', false)
            )
            ->when(
                !empty($this->filters['date_from']),
                fn ($q) => $q->where('created_at', '>=',
                    $this->filters['date_from'] . ' ' . ($this->filters['time_from'] ?? '00:00') . ':00'
                )
            )
            ->when(
                !empty($this->filters['date_to']),
                fn ($q) => $q->where('created_at', '<=',
                    $this->filters['date_to'] . ' ' . ($this->filters['time_to'] ?? '23:59') . ':59'
                )
            )
            ->get();

        // Group by position → candidate, then flatten into a flat row collection.
        // Because votes are already deduplicated above, counts here are accurate.
        return $votes
            ->filter(fn ($vote) => $vote->candidate?->position !== null) // skip orphaned votes
            ->groupBy(fn ($vote) => $vote->candidate->position->title)
            ->map(fn ($positionVotes) =>
                $positionVotes
                    ->groupBy('candidate_id')
                    ->map(function ($candidateVotes) {
                        $candidate = $candidateVotes->first()->candidate;

                        return [
                            'position'      => $candidate->position->title,
                            'candidate'     => trim("{$candidate->first_name} {$candidate->last_name}"),
                            'total_votes'   => $candidateVotes->count(),
                            'online_votes'  => $candidateVotes->where('online_vote', true)->count(),
                            'offline_votes' => $candidateVotes->where('online_vote', false)->count(),
                        ];
                    })
                    ->values()
            )
            ->flatten(1);
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

    public function styles(Worksheet $sheet): array
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
