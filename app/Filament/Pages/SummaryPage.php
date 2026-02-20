<?php

namespace App\Filament\Pages;

use App\Exports\MembersExport;
use App\Exports\MembersSummaryExport;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Vote;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class SummaryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static ?string $title = 'Summary';
    protected static ?string $navigationLabel = 'Summary';
    protected string $view = 'filament.pages.summary-page';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 3;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 300;

    /**
     * Return branch options for export forms, cached to avoid repeated queries.
     */
    protected function branchOptions(): array
    {
        return Cache::remember('branch_options', $this->cacheTtl, fn () =>
            Branch::orderBy('branch_name')->pluck('branch_name', 'branch_number')->toArray()
        );
    }

    /**
     * Shared export filter form schema (reused across all export actions).
     * Accepts which optional fields to include.
     */
    protected function exportFormSchema(bool $withStatus = false, bool $withMigs = false, bool $withRegistration = false, bool $withGender = false): array
    {
        $fields = [
            Select::make('branch_number')
                ->label('Branch')
                ->options($this->branchOptions())
                ->searchable()
                ->placeholder('All Branches'),
        ];

        if ($withStatus) {
            $fields[] = Radio::make('is_active')
                ->label('Status')
                ->options([
                    'all'      => 'All Members',
                    'active'   => 'Active Only',
                    'inactive' => 'Inactive Only',
                ])
                ->default('all')
                ->inline();
        }

        if ($withMigs) {
            $fields[] = Radio::make('is_migs')
                ->label('MIGS Membership')
                ->options([
                    'all' => 'All Members',
                    'yes' => 'MIGS Members Only',
                    'no'  => 'Non-MIGS Only',
                ])
                ->default('all')
                ->inline();
        }

        if ($withRegistration) {
            $fields[] = Radio::make('is_registered')
                ->label('Registration Status')
                ->options([
                    'all'            => 'All Members',
                    'registered'     => 'Registered Only',
                    'not_registered' => 'Not Registered Only',
                ])
                ->default('all')
                ->inline();
        }

        if ($withGender) {
            $fields[] = Select::make('gender')
                ->label('Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->placeholder('All Genders');
        }

        return $fields;
    }

    /**
     * Build a filtered Member query from export form data.
     */
    protected function buildMemberQuery(array $data, bool $withBranch = false): Builder
    {
        $query = Member::query();

        if ($withBranch) {
            $query->with('branch');
        }

        if (!empty($data['branch_number'])) {
            $query->where('branch_number', $data['branch_number']);
        }

        match ($data['is_active'] ?? 'all') {
            'active'   => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default    => null,
        };

        match ($data['is_migs'] ?? 'all') {
            'yes' => $query->where('is_migs', true),
            'no'  => $query->where('is_migs', false),
            default => null,
        };

        match ($data['is_registered'] ?? 'all') {
            'registered'     => $query->where('is_registered', true),
            'not_registered' => $query->where('is_registered', false),
            default          => null,
        };

        if (!empty($data['gender'])) {
            $query->where('gender', $data['gender']);
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('exportExcel')
                    ->label('Export to Excel (Detailed)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema($this->exportFormSchema(withStatus: true, withMigs: true, withRegistration: true, withGender: true))
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            return Excel::download(
                                new MembersExport($data),
                                'members-detailed-' . now()->format('Y-m-d-His') . '.xlsx'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('exportSummary')
                    ->label('Export to Excel (Summary)')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema($this->exportFormSchema())
                            ->columns(1),
                    ])
                    ->action(function (array $data) {
                        try {
                            return Excel::download(
                                new MembersSummaryExport($data),
                                'members-summary-' . now()->format('Y-m-d-His') . '.xlsx'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('exportPdf')
                    ->label('Export to PDF (Detailed)')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema($this->exportFormSchema(withStatus: true, withMigs: true, withGender: true))
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $members = $this->buildMemberQuery($data, withBranch: true)
                                ->orderBy('branch_number')
                                ->orderBy('last_name')
                                ->get();

                            // Compute statistics from the already-loaded collection (no extra queries)
                            $stats = $this->computeMemberStats($members);

                            $branchName = !empty($data['branch_number'])
                                ? Branch::where('branch_number', $data['branch_number'])->value('branch_name')
                                : 'All Branches';

                            $filters = [
                                'branch_name'  => $branchName,
                                'status_label' => match ($data['is_active'] ?? 'all') {
                                    'active'   => 'Active Only',
                                    'inactive' => 'Inactive Only',
                                    default    => 'All Statuses',
                                },
                                'migs_label' => match ($data['is_migs'] ?? 'all') {
                                    'yes'   => 'MIGS Only',
                                    'no'    => 'Non-MIGS Only',
                                    default => 'All Members',
                                },
                                'gender' => $data['gender'] ?? 'All Genders',
                            ];

                            $pdf = Pdf::loadView('pdf.members-report', array_merge(
                                ['members' => $members, 'filters' => $filters],
                                $stats
                            ))->setPaper('a4', 'portrait');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'members-report-' . now()->format('Y-m-d-His') . '.pdf'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('exportPdfSummary')
                    ->label('Export to PDF (Summary)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('warning')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema($this->exportFormSchema())
                            ->columns(1),
                    ])
                    ->action(function (array $data) {
                        try {
                            $branchQuery = Branch::where('is_active', true)->orderBy('branch_name');

                            if (!empty($data['branch_number'])) {
                                $branchQuery->where('branch_number', $data['branch_number']);
                            }

                            $branchNumbers = $branchQuery->pluck('branch_number');

                            // Single query: member stats per branch
                            $memberStats = Member::whereIn('branch_number', $branchNumbers)
                                ->select([
                                    'branch_number',
                                    DB::raw('COUNT(*) as total_members'),
                                    DB::raw('SUM(CASE WHEN is_migs = 1 THEN 1 ELSE 0 END) as total_migs'),
                                    DB::raw('SUM(CASE WHEN is_migs = 0 THEN 1 ELSE 0 END) as total_non_migs'),
                                    DB::raw('SUM(CASE WHEN is_migs = 1 AND is_registered = 1 THEN 1 ELSE 0 END) as total_reg_migs'),
                                    DB::raw('SUM(CASE WHEN is_migs = 0 AND is_registered = 1 THEN 1 ELSE 0 END) as total_reg_non_migs'),
                                ])
                                ->groupBy('branch_number')
                                ->get()
                                ->keyBy('branch_number');

                            // Single query: vote counts per branch
                            $voteCounts = Vote::whereIn('branch_number', $branchNumbers)
                                ->select('branch_number', DB::raw('COUNT(*) as total_casted_votes'))
                                ->groupBy('branch_number')
                                ->pluck('total_casted_votes', 'branch_number');

                            $branches = $branchQuery->get();

                            $summary = $branches->map(function (Branch $branch) use ($memberStats, $voteCounts) {
                                $stats        = $memberStats->get($branch->branch_number);
                                $totalMigs    = (int) ($stats->total_migs ?? 0);
                                $totalRegMigs = (int) ($stats->total_reg_migs ?? 0);

                                return [
                                    'branch_number'    => $branch->branch_number,
                                    'branch_name'      => $branch->branch_name,
                                    'total_members'    => (int) ($stats->total_members ?? 0),
                                    'total_migs'       => $totalMigs,
                                    'total_non_migs'   => (int) ($stats->total_non_migs ?? 0),
                                    'total_reg_migs'   => $totalRegMigs,
                                    'total_reg_non_migs' => (int) ($stats->total_reg_non_migs ?? 0),
                                    'quorum_percentage' => $totalMigs > 0
                                        ? round(($totalRegMigs / $totalMigs) * 100, 2)
                                        : 0.0,
                                    'total_casted_votes' => (int) ($voteCounts[$branch->branch_number] ?? 0),
                                ];
                            });

                            $totalMigs    = $summary->sum('total_migs');
                            $totalRegMigs = $summary->sum('total_reg_migs');

                            $pdf = Pdf::loadView('pdf.members-summary', [
                                'summary'          => $summary,
                                'totalMembers'     => $summary->sum('total_members'),
                                'totalBranches'    => $summary->count(),
                                'totalMigs'        => $totalMigs,
                                'totalNonMigs'     => $summary->sum('total_non_migs'),
                                'totalRegMigs'     => $totalRegMigs,
                                'totalRegNonMigs'  => $summary->sum('total_reg_non_migs'),
                                'totalRegistered'  => $summary->sum('total_reg_migs') + $summary->sum('total_reg_non_migs'),
                                'totalVotes'       => $summary->sum('total_casted_votes'),
                                'overallQuorum'    => $totalMigs > 0
                                    ? round(($totalRegMigs / $totalMigs) * 100, 2)
                                    : 0.0,
                            ])->setPaper('a4', 'portrait');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'members-summary-' . now()->format('Y-m-d-His') . '.pdf'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

            ])
                ->label('Export Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->button(),
        ];
    }

    /**
     * Compute member statistics from an already-loaded collection.
     * Avoids extra DB queries after an eager-loaded fetch.
     */
    protected function computeMemberStats(Collection $members): array
    {
        $totalMale   = $members->where('gender', 'Male')->count();
        $totalFemale = $members->where('gender', 'Female')->count();

        $totalMigs    = $members->where('is_migs', true)->count();
        $totalNonMigs = $members->where('is_migs', false)->count();

        return [
            'totalMembers'       => $members->count(),
            'totalMale'          => $totalMale,
            'totalFemale'        => $totalFemale,
            'registeredMale'     => $members->where('gender', 'Male')->where('is_registered', true)->count(),
            'registeredFemale'   => $members->where('gender', 'Female')->where('is_registered', true)->count(),
            'totalMigs'          => $totalMigs,
            'totalNonMigs'       => $totalNonMigs,
            'registeredMigs'     => $members->where('is_migs', true)->where('is_registered', true)->count(),
            'registeredNonMigs'  => $members->where('is_migs', false)->where('is_registered', true)->count(),
        ];
    }

    /**
     * Overall summary statistics — two DB queries total, cached.
     */
    public function getOverallSummaryProperty(): array
    {
        return Cache::remember('overall_summary', $this->cacheTtl, function () {
            // Single query for all member stats
            $memberStats = Member::where('is_active', true)
                ->select([
                    DB::raw('COUNT(*) as total_members'),
                    DB::raw('SUM(CASE WHEN is_migs = 1 THEN 1 ELSE 0 END) as total_migs'),
                    DB::raw('SUM(CASE WHEN is_migs = 0 THEN 1 ELSE 0 END) as total_non_migs'),
                    DB::raw('SUM(CASE WHEN is_migs = 1 AND is_registered = 1 THEN 1 ELSE 0 END) as total_registered_migs'),
                    DB::raw('SUM(CASE WHEN is_migs = 0 AND is_registered = 1 THEN 1 ELSE 0 END) as total_registered_non_migs'),
                ])
                ->first();

            $totalMigs           = (int) $memberStats->total_migs;
            $totalRegisteredMigs = (int) $memberStats->total_registered_migs;
            $totalRegisteredNonMigs = (int) $memberStats->total_registered_non_migs;

            // Single query for vote stats
            $voteStats = Vote::select([
                DB::raw('COUNT(*) as total_casted_votes'),
                DB::raw('COUNT(DISTINCT member_code) as total_voters'),
            ])->first();

            return [
                'total_members'              => (int) $memberStats->total_members,
                'total_migs'                 => $totalMigs,
                'total_non_migs'             => (int) $memberStats->total_non_migs,
                'total_registered_migs'      => $totalRegisteredMigs,
                'total_registered_non_migs'  => $totalRegisteredNonMigs,
                'total_registered'           => $totalRegisteredMigs + $totalRegisteredNonMigs,
                'total_voters'               => (int) $voteStats->total_voters,
                'quorum_percentage'          => $totalMigs > 0
                    ? round(($totalRegisteredMigs / $totalMigs) * 100, 2)
                    : 0.0,
                'total_casted_votes'         => (int) $voteStats->total_casted_votes,
            ];
        });
    }

    /**
     * Filament Table — branch breakdown.
     *
     * Key fix: use a subquery-based approach to avoid N+1.
     * We join aggregated member and vote counts directly in the query,
     * then use standard column accessors — no getStateUsing closures.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                // Subquery: member aggregates per branch
                $memberAgg = DB::table('members')
                    ->where('is_active', true)
                    ->select([
                        'branch_number',
                        DB::raw('COUNT(*) as total_members'),
                        DB::raw('SUM(CASE WHEN is_migs = 1 THEN 1 ELSE 0 END) as total_migs'),
                        DB::raw('SUM(CASE WHEN is_migs = 0 THEN 1 ELSE 0 END) as total_non_migs'),
                        DB::raw('SUM(CASE WHEN is_migs = 1 AND is_registered = 1 THEN 1 ELSE 0 END) as registered_migs'),
                        DB::raw('SUM(CASE WHEN is_migs = 0 AND is_registered = 1 THEN 1 ELSE 0 END) as registered_non_migs'),
                        DB::raw('SUM(CASE WHEN is_registered = 1 THEN 1 ELSE 0 END) as total_registered'),
                    ])
                    ->groupBy('branch_number');

                // Subquery: vote counts per branch
                $voteAgg = DB::table('votes')
                    ->select([
                        'branch_number',
                        DB::raw('COUNT(*) as votes_cast'),
                    ])
                    ->groupBy('branch_number');

                return Branch::query()
                    ->where('branches.is_active', true)
                    ->leftJoinSub($memberAgg, 'member_stats', 'branches.branch_number', '=', 'member_stats.branch_number')
                    ->leftJoinSub($voteAgg, 'vote_stats', 'branches.branch_number', '=', 'vote_stats.branch_number')
                    ->select([
                        'branches.*',
                        DB::raw('COALESCE(member_stats.total_members, 0) as total_members'),
                        DB::raw('COALESCE(member_stats.total_migs, 0) as total_migs'),
                        DB::raw('COALESCE(member_stats.total_non_migs, 0) as total_non_migs'),
                        DB::raw('COALESCE(member_stats.registered_migs, 0) as registered_migs'),
                        DB::raw('COALESCE(member_stats.registered_non_migs, 0) as registered_non_migs'),
                        DB::raw('COALESCE(member_stats.total_registered, 0) as total_registered'),
                        DB::raw('COALESCE(vote_stats.votes_cast, 0) as votes_cast'),
                        DB::raw('CASE WHEN COALESCE(member_stats.total_migs, 0) > 0 THEN ROUND((COALESCE(member_stats.registered_migs, 0) / member_stats.total_migs) * 100, 2) ELSE 0 END as quorum_percentage'),
                    ]);
            })
            ->columns([

                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_members')
                    ->label('Total Members')
                    ->alignCenter()
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('total_migs')
                    ->label('Total MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('total_non_migs')
                    ->label('Total Non-MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('registered_migs')
                    ->label('Registered MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('registered_non_migs')
                    ->label('Registered Non-MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('total_registered')
                    ->label('Total Registered')
                    ->alignCenter()
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('quorum_percentage')
                    ->label('Quorum %')
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2) . '%')
                    ->color(fn (float $state): string => match (true) {
                        $state >= 75 => 'success',
                        $state >= 50 => 'warning',
                        $state >= 25 => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('votes_cast')
                    ->label('Votes Cast')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),

            ])
            ->filters([

                SelectFilter::make('branch_name')
                    ->label('Branch')
                    ->options(
                        Branch::where('is_active', true)
                            ->orderBy('branch_name')
                            ->pluck('branch_name', 'id')
                    )
                    ->searchable()
                    ->preload(),

                // Quorum threshold filter — now works because quorum_percentage is a real column
                SelectFilter::make('quorum_threshold')
                    ->label('Quorum Status')
                    ->options([
                        'above_75' => 'Above 75% (Excellent)',
                        'above_50' => '50–75% (Good)',
                        'above_25' => '25–50% (Fair)',
                        'below_25' => 'Below 25% (Poor)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'above_75' => $query->where('quorum_percentage', '>=', 75),
                            'above_50' => $query->whereBetween('quorum_percentage', [50, 74.99]),
                            'above_25' => $query->whereBetween('quorum_percentage', [25, 49.99]),
                            'below_25' => $query->where('quorum_percentage', '<', 25),
                            default    => $query,
                        };
                    }),

                // Votes filter — works because votes_cast is a real column
                SelectFilter::make('has_votes')
                    ->label('Votes Cast')
                    ->options([
                        'yes' => 'With votes',
                        'no'  => 'Without votes',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'yes'   => $query->where('votes_cast', '>', 0),
                            'no'    => $query->where('votes_cast', '=', 0),
                            default => $query,
                        };
                    }),

            ])
            ->persistFiltersInSession()
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->defaultSort('branch_name')
            ->emptyStateHeading('No branches found')
            ->emptyStateDescription('No branches match your current filters.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    protected function getViewData(): array
    {
        return [
            'overallSummary' => $this->getOverallSummaryProperty(),
        ];
    }
}
