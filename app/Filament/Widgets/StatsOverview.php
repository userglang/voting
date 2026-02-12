<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Position;
use App\Models\Candidate;
use App\Models\Branch;
use App\Models\Vote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Define the statistics to display with trends and charts
     */
    protected function getStats(): array
    {
        // Use caching to avoid repeated DB queries on large datasets
        $cacheKey = 'stats_overview_' . now()->startOfDay()->toDateString();
        $cachedStats = Cache::get($cacheKey);

        if ($cachedStats) {
            return $cachedStats;
        }

        // Current month data (optimized with DB::raw for performance)
        $totalMembers = Cache::remember('total_members', 60, fn() => Member::count());
        $activeMembers = Cache::remember('active_members', 60, fn() => Member::where('is_active', true)->count());

        // Previous month data for comparison (optimized query)
        $previousMonthTotal = Cache::remember('previous_month_total', 60, fn() => Member::where('created_at', '<', now()->startOfMonth())->count());
        $newMembersThisMonth = $totalMembers - $previousMonthTotal;

        // Calculate percentage change with optimized check
        $percentageChange = $previousMonthTotal > 0
            ? round((($totalMembers - $previousMonthTotal) / $previousMonthTotal) * 100, 1)
            : 0;

        // Get member registration trend for last 7 days (optimized for large datasets)
        $registrationTrend = Member::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Pad array to ensure we have 7 values (for trend chart)
        $registrationTrend = array_pad($registrationTrend, 7, 0);

        // Get member counts by gender (optimized)
        $genderDistribution = Cache::remember('gender_distribution', 60, fn() => Member::select(DB::raw('gender, COUNT(*) as count'))->groupBy('gender')->get()->keyBy('gender')->toArray());
        $maleCount = $genderDistribution['Male']['count'] ?? 0;
        $femaleCount = $genderDistribution['Female']['count'] ?? 0;

        // Get member counts by branch (optimized)
        $branchDistribution = Cache::remember('branch_distribution', 60, fn() => Member::select('branch_number', DB::raw('COUNT(*) as count'))
            ->groupBy('branch_number')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'branch_number')
            ->toArray());

        // MIGS Members count (optimized)
        $migsMembersCount = Cache::remember('migs_members', 60, fn() => Member::where('is_migs', true)->count());
        $nonMigsMembersCount = $totalMembers - $migsMembersCount;

        // Registered MIGS and Non-MIGS counts
        $registeredMigsCount = Cache::remember('registered_migs', 60, fn() => Member::where('is_migs', true)->where('is_active', true)->count());
        $registeredNonMigsCount = Cache::remember('registered_non_migs', 60, fn() => Member::where('is_migs', false)->where('is_active', true)->count());

        // Fetch position data
        $vacantPositions = Cache::remember('vacant_positions', 60, fn() => Position::where('vacant_count', '>', 0)->count());

        // Get position vacant count trend for the last 7 days
        $vacantPositionsTrend = Position::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(vacant_count) as vacant_count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('vacant_count', 'date')
            ->toArray();

        // Pad array to ensure we have 7 values (for trend chart)
        $vacantPositionsTrend = array_pad($vacantPositionsTrend, 7, 0);

        // Candidate Data
        $totalCandidates = Cache::remember('total_candidates', 60, fn() => Candidate::count());

        // Fetch Branch Data
        $totalBranches = Cache::remember('total_branches', 60, fn() => Branch::count());
        $activeBranches = Cache::remember('active_branches', 60, fn() => Branch::where('is_active', true)->count());

        // Total Casted Votes
        $totalCastedVotes = Cache::remember('total_casted_votes', 60, fn() => Vote::count());

        // Cache and return the results
        $stats = [
            // Total Members with trend
            Stat::make('Total Members', number_format($totalMembers))
                ->description($percentageChange >= 0
                    ? "{$percentageChange}% increase"
                    : abs($percentageChange) . "% decrease")
                ->descriptionIcon($percentageChange >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($percentageChange >= 0 ? 'success' : 'danger')
                ->chart($registrationTrend),

            // Vacant Positions
            Stat::make('Vacant Positions', number_format($vacantPositions))
                ->description("Vacancies available")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('danger')
                ->chart($vacantPositionsTrend),

            // Total Candidates
            Stat::make('Total Candidates', number_format($totalCandidates))
                ->description('All candidates in the system')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            // Active Branches over Total Branches
            Stat::make('Active Branches', number_format($activeBranches))
                ->description("Active branches out of {$totalBranches}")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success')
                ->chart([round(($activeBranches / max($totalBranches, 1)) * 100, 1)]), // Showing the percentage in a chart as a single value

            // MIGS Members
            Stat::make('MIGS Members', number_format($migsMembersCount))
                ->description(round(($migsMembersCount / max($totalMembers, 1)) * 100, 1) . '% enrolled')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),

            // Non-MIGS Members
            Stat::make('Non-MIGS Members', number_format($nonMigsMembersCount))
                ->description(round(($nonMigsMembersCount / max($totalMembers, 1)) * 100, 1) . '% of total members')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('primary'),

            // Registered MIGS
            Stat::make('Registered MIGS', number_format($registeredMigsCount))
                ->description('MIGS members registered and active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            // Registered Non-MIGS
            Stat::make('Registered Non-MIGS', number_format($registeredNonMigsCount))
                ->description('Non-MIGS members registered and active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            // Gender Distribution
            Stat::make('Gender Distribution', "{$maleCount}M / {$femaleCount}F")
                ->description('Male / Female members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            // Total Casted Votes
            Stat::make('Total Casted Votes', number_format($totalCastedVotes))
                ->description('Total number of votes casted in the system')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),


        ];

        // Store the result in cache for 60 minutes
        Cache::put($cacheKey, $stats, 60);

        return $stats;
    }
}
