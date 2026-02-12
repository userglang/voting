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

        $summary = $branches->map(function ($branch) {
            $memberQuery = Member::where('branch_number', $branch->branch_number);

            $totalMembers = $memberQuery->count();
            $totalMigs = (clone $memberQuery)->where('is_migs', true)->count();
            $totalNonMigs = (clone $memberQuery)->where('is_migs', false)->count();

            // Registered members
            $totalRegMigs = (clone $memberQuery)
                ->where('is_migs', true)
                ->where('is_registered', true)
                ->count();

            $totalRegNonMigs = (clone $memberQuery)
                ->where('is_migs', false)
                ->where('is_registered', true)
                ->count();

            // Calculate quorum percentage
            $quorumPercentage = $totalMigs > 0
                ? ($totalRegMigs / $totalMigs) * 100
                : 0;

            // Total votes cast from this branch
            $totalCastedVotes = Vote::where('branch_number', $branch->branch_number)->count();

            return [
                'branch' => $branch->branch_name,
                'total_member' => $totalMembers,
                'total_migs' => $totalMigs,
                'total_non_migs' => $totalNonMigs,
                'total_reg_migs' => $totalRegMigs,
                'total_reg_non_migs' => $totalRegNonMigs,
                'quorum_percentage' => number_format($quorumPercentage, 2) . ' %',
                'total_casted_votes' => $totalCastedVotes,
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
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'],
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
        return 'Members Summary';
    }
}
