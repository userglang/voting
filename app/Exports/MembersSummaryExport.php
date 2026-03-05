<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Branch;
use App\Models\Vote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MembersSummaryExport implements
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
        $query = Branch::query()->where('is_active', true);

        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        $branches = $query->orderBy('branch_name')->get();

        $isValid = $this->filters['is_valid'] ?? 'all';

        $summary = $branches->map(function ($branch) use ($isValid) {
            $memberQuery = Member::where('branch_number', $branch->branch_number);

            // is_valid = false  → filter members to invalid only
            // is_valid = true   → no member filter (export all members)
            // is_valid = all    → no member filter
            if ($isValid === 'invalid') {
                $memberQuery->where('is_valid', false);
            }

            $totalMembers = $memberQuery->count();
            $totalMigs = (clone $memberQuery)->where('is_migs', true)->count();
            $totalNonMigs = (clone $memberQuery)->where('is_migs', false)->count();

            $totalRegMigs = (clone $memberQuery)
                ->where('is_migs', true)
                ->where('is_registered', true)
                ->count();

            $totalRegNonMigs = (clone $memberQuery)
                ->where('is_migs', false)
                ->where('is_registered', true)
                ->count();

            $quorumPercentage = $totalMigs > 0
                ? ($totalRegMigs / $totalMigs) * 100
                : 0;

            // Votes filter:
            // is_valid = true  → count only valid votes
            // is_valid = false → count only invalid votes
            // is_valid = all   → count all votes
            $voteQuery = Vote::where('branch_number', $branch->branch_number);

            match ($isValid) {
                'valid'   => $voteQuery->where('is_valid', true),
                'invalid' => $voteQuery->where('is_valid', false),
                default   => null,
            };

            $totalCastedVotes = $voteQuery->count();

            return [
                'branch'              => $branch->branch_name,
                'total_member'        => $totalMembers,
                'total_migs'          => $totalMigs,
                'total_non_migs'      => $totalNonMigs,
                'total_reg_migs'      => $totalRegMigs,
                'total_reg_non_migs'  => $totalRegNonMigs,
                'quorum_percentage'   => number_format($quorumPercentage, 2) . ' %',
                'total_casted_votes'  => $totalCastedVotes,
            ];
        });

        return collect($summary);
    }

    public function headings(): array
    {
        return [
            'Branch',
            'Total Member',
            'Total Migs',
            'Total Non-Migs',
            'Total Reg MIGS',
            'Total Reg Non-Migs',
            'Quorum Percentage',
            'Total Casted Votes',
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
                    'startColor' => ['rgb' => '3B82F6'],
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
        return 'Members Summary';
    }
}
