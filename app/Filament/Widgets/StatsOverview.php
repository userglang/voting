<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Member;
use App\Models\Position;
use App\Models\Vote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Cache TTL in seconds (5 minutes).
     * Adjust as needed based on how frequently your data changes.
     */
    protected int $cacheTtl = 300;

    /**
     * Fetch all raw stats data, cached to avoid redundant DB hits.
     */
    protected function getRawStats(): array
    {
        return Cache::remember('stats_overview_data', $this->cacheTtl, function () {
            $totalMembers      = Member::count();
            $activeMembers     = Member::where('is_active', true)->count();
            $totalBranches     = Branch::count();
            $activeBranches    = Branch::where('is_active', true)->count();
            $totalCandidates   = Candidate::count();
            $vacantPositions   = Position::where('vacant_count', '>', 0)->count();
            $totalCastedVotes  = Vote::count();
            $migsMembersCount  = Member::where('is_migs', true)->count();

            $previousMonthTotal = Member::where('created_at', '<', now()->startOfMonth())->count();
            $newMembersThisMonth = $totalMembers - $previousMonthTotal;

            $percentageChange = $previousMonthTotal > 0
                ? round((($newMembersThisMonth) / $previousMonthTotal) * 100, 1)
                : ($totalMembers > 0 ? 100 : 0);

            // Build a 7-day registration trend keyed by date string
            $rawTrend = Member::query()
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $registrationTrend = $this->buildDailyTrend($rawTrend, 7);

            // Gender distribution
            $genderDistribution = Member::select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray();

            $maleCount   = $genderDistribution['Male'] ?? 0;
            $femaleCount = $genderDistribution['Female'] ?? 0;

            $nonMigsMembersCount    = $totalMembers - $migsMembersCount;
            $registeredMigsCount    = Member::where('is_migs', true)->where('is_registered', true)->count();
            $registeredNonMigsCount = Member::where('is_migs', false)->where('is_registered', true)->count();

            // Vacant positions trend: total vacancies across positions per day based on updated_at
            // (created_at doesn't reflect vacancy changes — use a sensible proxy or static chart)
            $rawVacantTrend = Position::query()
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(vacant_count) as vacant_count'))
                ->where('updated_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('vacant_count', 'date')
                ->toArray();

            $vacantPositionsTrend = $this->buildDailyTrend($rawVacantTrend, 7);

            return compact(
                'totalMembers',
                'activeMembers',
                'totalBranches',
                'activeBranches',
                'totalCandidates',
                'vacantPositions',
                'totalCastedVotes',
                'migsMembersCount',
                'nonMigsMembersCount',
                'registeredMigsCount',
                'registeredNonMigsCount',
                'maleCount',
                'femaleCount',
                'percentageChange',
                'newMembersThisMonth',
                'registrationTrend',
                'vacantPositionsTrend',
            );
        });
    }

    /**
     * Fill a date-keyed array into a fixed-length array of $days values,
     * using 0 for any missing days. Always returns exactly $days elements.
     */
    protected function buildDailyTrend(array $dateKeyedData, int $days): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date    = now()->subDays($i)->toDateString();
            $trend[] = (int) ($dateKeyedData[$date] ?? 0);
        }

        return $trend;
    }

    /**
     * Build and return the stat cards.
     */
    protected function getStats(): array
    {
        $data = $this->getRawStats();

        $totalMembers         = $data['totalMembers'];
        $totalBranches        = $data['totalBranches'];
        $activeBranches       = $data['activeBranches'];
        $migsMembersCount     = $data['migsMembersCount'];
        $nonMigsMembersCount  = $data['nonMigsMembersCount'];
        $percentageChange     = $data['percentageChange'];

        $trendIsUp       = $percentageChange >= 0;
        $changeLabel     = $trendIsUp
            ? "{$percentageChange}% increase this month"
            : abs($percentageChange) . '% decrease this month';
        $changeTrendIcon = $trendIsUp
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';

        $migsPercent    = $totalMembers > 0 ? round(($migsMembersCount / $totalMembers) * 100, 1) : 0;
        $nonMigsPercent = $totalMembers > 0 ? round(($nonMigsMembersCount / $totalMembers) * 100, 1) : 0;
        $branchPercent  = $totalBranches > 0 ? round(($activeBranches / $totalBranches) * 100, 1) : 0;

        // Chart showing active vs inactive branches as a proportion [active, inactive]
        $branchChart = [$activeBranches, max(0, $totalBranches - $activeBranches)];

        return [
            Stat::make('Total Members', number_format($totalMembers))
                ->description($changeLabel)
                ->descriptionIcon($changeTrendIcon)
                ->color($trendIsUp ? 'success' : 'danger')
                ->chart($data['registrationTrend']),

            Stat::make('Vacant Positions', number_format($data['vacantPositions']))
                ->description('Vacancies available')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('danger')
                ->chart($data['vacantPositionsTrend']),

            Stat::make('Total Candidates', number_format($data['totalCandidates']))
                ->description('All candidates in the system')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('Active Branches', number_format($activeBranches))
                ->description("{$branchPercent}% of {$totalBranches} total branches")
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success')
                ->chart($branchChart),

            Stat::make('MIGS Members', number_format($migsMembersCount))
                ->description("{$migsPercent}% of total members")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),

            Stat::make('Non-MIGS Members', number_format($nonMigsMembersCount))
                ->description("{$nonMigsPercent}% of total members")
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('primary'),

            Stat::make('Registered MIGS', number_format($data['registeredMigsCount']))
                ->description('Active MIGS members')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Registered Non-MIGS', number_format($data['registeredNonMigsCount']))
                ->description('Active Non-MIGS members')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Gender Distribution', "{$data['maleCount']}M / {$data['femaleCount']}F")
                ->description('Male vs Female breakdown')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Total Votes Cast', number_format($data['totalCastedVotes']))
                ->description('All votes recorded in the system')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
        ];
    }
}
