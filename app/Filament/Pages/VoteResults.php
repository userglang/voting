<?php

namespace App\Filament\Pages;

use App\Exports\VotesExport;
use App\Exports\VotesSummaryExport;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Position;
use App\Models\Vote;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Assets\Js;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class VoteResults extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;
    protected static ?string $navigationLabel = 'Vote Results';
    protected static ?string $title = 'Candidate Vote Results';
    protected static ?string $slug = 'vote-results';
    protected string $view = 'filament.pages.vote-results';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;

    public string $filterPosition = '';
    public string $search = '';

    /** Cache TTL in seconds. */
    protected int $cacheTtl = 300;

    public static function getAssets(): array
    {
        return [
            Js::make('vote-results-js', resource_path('js/filament/vote-results.js')),
        ];
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /**
     * Branch options — cached to avoid repeated queries across all four export forms.
     */
    protected function branchOptions(): array
    {
        return Cache::remember('branch_options_vote', $this->cacheTtl, fn () =>
            Branch::orderBy('branch_name')->pluck('branch_name', 'branch_number')->toArray()
        );
    }

    /**
     * Position options — cached similarly.
     */
    protected function positionOptions(): array
    {
        return Cache::remember('position_options', $this->cacheTtl, fn () =>
            Position::where('is_active', true)->orderBy('priority')->pluck('title', 'id')->toArray()
        );
    }

    /**
     * Apply standard vote filters to a query builder.
     */
    protected function applyVoteFilters(\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder
    {
        if (!empty($data['branch_number'])) {
            $query->where('branch_number', $data['branch_number']);
        }

        match ($data['vote_type'] ?? 'all') {
            'online'  => $query->where('online_vote', true),
            'offline' => $query->where('online_vote', false),
            default   => null,
        };

        if (!empty($data['date_from'])) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }

        if (!empty($data['date_to'])) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        return $query;
    }

    /**
     * Resolve a human-readable branch name from branch_number without a DB hit
     * (uses the already-cached options array).
     */
    protected function resolveBranchName(?string $branchNumber): string
    {
        if (empty($branchNumber)) {
            return 'All Branches';
        }

        return $this->branchOptions()[$branchNumber] ?? 'Unknown Branch';
    }

    /**
     * Build the filter labels array used in all PDF exports.
     */
    protected function buildFilterLabels(array $data): array
    {
        return [
            'branch_name'     => $this->resolveBranchName($data['branch_number'] ?? null),
            'vote_type_label' => match ($data['vote_type'] ?? 'all') {
                'online'  => 'Online Votes Only',
                'offline' => 'Offline Votes Only',
                default   => 'All Vote Types',
            },
            'date_from' => $data['date_from'] ?? 'Beginning',
            'date_to'   => $data['date_to'] ?? 'Present',
        ];
    }

    /**
     * Shared vote export form schema with optional position/date fields.
     */
    protected function voteExportSchema(bool $withPosition = false, bool $withDates = true): array
    {
        $fields = [
            Select::make('branch_number')
                ->label('Branch')
                ->options($this->branchOptions())
                ->searchable()
                ->placeholder('All Branches'),

            Radio::make('vote_type')
                ->label('Vote Type')
                ->options([
                    'all'     => 'All Votes',
                    'online'  => 'Online Votes Only',
                    'offline' => 'Offline Votes Only',
                ])
                ->default('all')
                ->inline(),
        ];

        if ($withPosition) {
            $fields[] = Select::make('position_id')
                ->label('Position')
                ->options($this->positionOptions())
                ->searchable()
                ->placeholder('All Positions');
        }

        if ($withDates) {
            $fields[] = DatePicker::make('date_from')->label('Date From')->native(false);
            $fields[] = DatePicker::make('date_to')->label('Date To')->native(false);
        }

        return $fields;
    }

    // -------------------------------------------------------------------------
    // Header actions / exports
    // -------------------------------------------------------------------------

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('voteExportExcel')
                    ->label('Export to Excel (Detailed)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema($this->voteExportSchema(withPosition: true))
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            return Excel::download(
                                new VotesExport($data),
                                'votes-detailed-' . now()->format('Y-m-d-His') . '.xlsx'
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
                            ->schema($this->voteExportSchema())
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            return Excel::download(
                                new VotesSummaryExport($data),
                                'votes-summary-' . now()->format('Y-m-d-His') . '.xlsx'
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
                            ->schema($this->voteExportSchema(withPosition: true))
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $query = Vote::query()
                                ->with(['member', 'candidate.position', 'branch'])
                                ->orderByDesc('created_at');

                            $this->applyVoteFilters($query, $data);

                            if (!empty($data['position_id'])) {
                                $query->whereHas('candidate', fn ($q) =>
                                    $q->where('position_id', $data['position_id'])
                                );
                            }

                            $votes = $query->get();

                            $pdf = Pdf::loadView('pdf.votes-report', [
                                'votes'   => $votes,
                                'filters' => $this->buildFilterLabels($data),
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'votes-report-' . now()->format('Y-m-d-His') . '.pdf'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('exportPdfSummary')
                    ->label('Export to PDF (Summary)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('warning')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema($this->voteExportSchema())
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            // Aggregate in SQL — avoids loading all votes into memory
                            $voteAgg = $this->applyVoteFilters(
                                Vote::query()
                                    ->join('candidates', 'votes.candidate_id', '=', 'candidates.id')
                                    ->join('positions', 'candidates.position_id', '=', 'positions.id')
                                    ->select([
                                        'positions.id as position_id',
                                        'positions.title as position_title',
                                        'positions.vacant_count',
                                        'candidates.id as candidate_id',
                                        DB::raw('candidates.first_name'),
                                        DB::raw('candidates.last_name'),
                                        DB::raw('COUNT(*) as total'),
                                        DB::raw('SUM(CASE WHEN votes.online_vote = 1 THEN 1 ELSE 0 END) as online'),
                                        DB::raw('SUM(CASE WHEN votes.online_vote = 0 THEN 1 ELSE 0 END) as offline'),
                                    ])
                                    ->groupBy(
                                        'positions.id', 'positions.title', 'positions.vacant_count',
                                        'candidates.id', 'candidates.first_name', 'candidates.last_name'
                                    ),
                                $data
                            )->get();

                            // Totals from the aggregate (no second query needed)
                            $totalVotes        = $voteAgg->sum('total');
                            $totalOnlineVotes  = $voteAgg->sum('online');
                            $totalOfflineVotes = $voteAgg->sum('offline');
                            $totalCandidates   = $voteAgg->unique('candidate_id')->count();

                            // Group into positions → candidates structure
                            $summary = $voteAgg
                                ->groupBy('position_id')
                                ->map(function (Collection $rows) {
                                    $first             = $rows->first();
                                    $totalPositionVotes = $rows->sum('total');

                                    $candidates = $rows->map(fn ($row) => [
                                        'name'       => trim("{$row->first_name} {$row->last_name}"),
                                        'total'      => (int) $row->total,
                                        'online'     => (int) $row->online,
                                        'offline'    => (int) $row->offline,
                                        'percentage' => $totalPositionVotes > 0
                                            ? round(($row->total / $totalPositionVotes) * 100, 2)
                                            : 0.0,
                                    ])->sortByDesc('total')->values();

                                    return [
                                        'position_title' => $first->position_title,
                                        'vacant_count'   => $first->vacant_count,
                                        'total_votes'    => $totalPositionVotes,
                                        'candidates'     => $candidates,
                                    ];
                                })
                                ->sortBy('position_title')
                                ->values();

                            $pdf = Pdf::loadView('pdf.votes-summary', [
                                'summary'           => $summary,
                                'filters'           => $this->buildFilterLabels($data),
                                'totalVotes'        => $totalVotes,
                                'totalOnlineVotes'  => $totalOnlineVotes,
                                'totalOfflineVotes' => $totalOfflineVotes,
                                'totalCandidates'   => $totalCandidates,
                            ])->setPaper('a4', 'portrait');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'votes-summary-' . now()->format('Y-m-d-His') . '.pdf'
                            );
                        } catch (\Exception $e) {
                            Notification::make()->title('Export Failed')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

            ])
                ->label('Vote Export Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->button(),
        ];
    }

    // -------------------------------------------------------------------------
    // Live results
    // -------------------------------------------------------------------------

    public function getPositionsProperty(): Collection
    {
        return Position::where('is_active', true)
            ->orderBy('priority')
            ->get(['id', 'title']);
    }

    /**
     * Load results with a single DB round-trip per position set.
     *
     * Original issue: for each candidate, two separate Vote::where() queries
     * were fired to get online/offline counts — O(candidates) extra queries.
     *
     * Fix: eager-load votes and aggregate counts in one go using withCount
     * scoped constraints + a single aggregate query for online/offline splits.
     */
    public function getResultsProperty(): Collection
    {
        // Step 1: Fetch positions with candidates and their total vote counts.
        $positions = Position::query()
            ->where('is_active', true)
            ->when($this->filterPosition, fn ($q) => $q->where('id', $this->filterPosition))
            ->orderBy('priority')
            ->with(['candidates' => function ($q) {
                $q->withCount('votes')
                    ->when($this->search, fn ($q) =>
                        $q->where(fn ($inner) =>
                            $inner->where('first_name',   'like', "%{$this->search}%")
                                  ->orWhere('last_name',  'like', "%{$this->search}%")
                                  ->orWhere('middle_name','like', "%{$this->search}%")
                        )
                    )
                    ->orderByDesc('votes_count');
            }])
            ->get();

        // Step 2: Collect all candidate IDs from the result set.
        $candidateIds = $positions
            ->flatMap(fn ($p) => $p->candidates->pluck('id'))
            ->unique()
            ->values()
            ->all();

        // Step 3: Single query — online/offline vote counts for all candidates.
        $voteSplits = Vote::whereIn('candidate_id', $candidateIds)
            ->select([
                'candidate_id',
                DB::raw('SUM(CASE WHEN online_vote = 1 THEN 1 ELSE 0 END) as online_votes'),
                DB::raw('SUM(CASE WHEN online_vote = 0 THEN 1 ELSE 0 END) as onsite_votes'),
            ])
            ->groupBy('candidate_id')
            ->get()
            ->keyBy('candidate_id');

        // Step 4: Map into the view structure using already-loaded data.
        return $positions->map(fn (Position $position) => [
            'id'          => $position->id,
            'title'       => $position->title,
            'slots'       => $position->vacant_count ?? 1,
            'total_votes' => $position->candidates->sum('votes_count'),
            'candidates'  => $position->candidates->map(function ($c) use ($voteSplits) {
                $split = $voteSplits->get($c->id);

                return [
                    'full_name'    => $c->full_name,
                    'total_votes'  => $c->votes_count,
                    'online_votes' => $split ? (int) $split->online_votes : 0,
                    'onsite_votes' => $split ? (int) $split->onsite_votes : 0,
                    'image_url'    => $c->profile_image_url,
                    'initials'     => strtoupper(substr($c->first_name, 0, 1) . substr($c->last_name, 0, 1)),
                ];
            })->values(),
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'positions' => $this->getPositionsProperty(),
            'results'   => $this->getResultsProperty(),
        ];
    }
}
