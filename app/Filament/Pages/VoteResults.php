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
use Filament\Forms\Components\TimePicker;
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
     * All columns are fully qualified with the 'votes' table to avoid
     * ambiguity when the query joins multiple tables (candidates, positions, votes).
     */
    protected function applyVoteFilters(\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder
    {
        if (!empty($data['branch_number'])) {
            $query->where('votes.branch_number', $data['branch_number']);
        }

        match ($data['vote_type'] ?? 'all') {
            'online'  => $query->where('votes.online_vote', true),
            'offline' => $query->where('votes.online_vote', false),
            default   => null,
        };

        // is_valid filter — only valid votes when set to 'valid'
        match ($data['is_valid'] ?? 'all') {
            'valid'   => $query->where('votes.is_valid', true),
            'invalid' => $query->where('votes.is_valid', false),
            default   => null,
        };

        if (!empty($data['date_from'])) {
            $timeFrom = $data['time_from'] ?? '00:00';
            $query->where('votes.created_at', '>=', $data['date_from'] . ' ' . $timeFrom . ':00');
        }

        if (!empty($data['date_to'])) {
            $timeTo = $data['time_to'] ?? '23:59';
            $query->where('votes.created_at', '<=', $data['date_to'] . ' ' . $timeTo . ':59');
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
            'branch_name'      => $this->resolveBranchName($data['branch_number'] ?? null),
            'vote_type_label'  => match ($data['vote_type'] ?? 'all') {
                'online'  => 'Online Votes Only',
                'offline' => 'Offline Votes Only',
                default   => 'All Vote Types',
            },
            'is_valid_label'   => match ($data['is_valid'] ?? 'all') {
                'valid'   => 'Valid Votes Only',
                'invalid' => 'Invalid Votes Only',
                default   => 'All Votes',
            },
            'date_from' => !empty($data['date_from'])
                ? \Carbon\Carbon::parse($data['date_from'] . ' ' . ($data['time_from'] ?? '00:00'))->format('F d, Y g:i A')
                : 'Beginning',
            'date_to'   => !empty($data['date_to'])
                ? \Carbon\Carbon::parse($data['date_to'] . ' ' . ($data['time_to'] ?? '23:59'))->format('F d, Y g:i A')
                : 'Present',
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

            Radio::make('is_valid')
                ->label('Validity')
                ->options([
                    'valid'   => 'Valid Votes Only',
                    'invalid' => 'Invalid Votes Only',
                    'all'     => 'All Votes',
                ])
                ->default('valid')
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
            $fields[] = DatePicker::make('date_from')
                ->label('Date From')
                ->native(false)
                ->default(now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString());
            $fields[] = TimePicker::make('time_from')
                ->label('Time From')
                ->native(false)
                ->seconds(false)
                ->default('00:00');
            $fields[] = DatePicker::make('date_to')
                ->label('Date To')
                ->native(false)
                ->default(now()->toDateString());
            $fields[] = TimePicker::make('time_to')
                ->label('Time To')
                ->native(false)
                ->seconds(false)
                ->default('23:59');
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
            // Build a deduplicated votes subquery with filters baked in
            $voteJoin = DB::table('votes')
                ->select('id', 'candidate_id', 'online_vote')
                ->distinct();

            match ($data['is_valid'] ?? 'valid') {
                'valid'   => $voteJoin->where('is_valid', true),
                'invalid' => $voteJoin->where('is_valid', false),
                default   => null,
            };

            if (!empty($data['branch_number'])) {
                $voteJoin->where('branch_number', $data['branch_number']);
            }

            match ($data['vote_type'] ?? 'all') {
                'online'  => $voteJoin->where('online_vote', true),
                'offline' => $voteJoin->where('online_vote', false),
                default   => null,
            };

            if (!empty($data['date_from'])) {
                $timeFrom = $data['time_from'] ?? '00:00';
                $voteJoin->where('created_at', '>=', $data['date_from'] . ' ' . $timeFrom . ':00');
            }

            if (!empty($data['date_to'])) {
                $timeTo = $data['time_to'] ?? '23:59';
                $voteJoin->where('created_at', '<=', $data['date_to'] . ' ' . $timeTo . ':59');
            }

            // Aggregate votes
            $voteAgg = Candidate::query()
                ->join('positions', 'candidates.position_id', '=', 'positions.id')
                ->leftJoinSub($voteJoin, 'votes', 'votes.candidate_id', '=', 'candidates.id')
                ->where('positions.is_active', true)
                ->select([
                    'positions.id as position_id',
                    'positions.title as position_title',
                    'positions.priority',
                    'positions.vacant_count',
                    'candidates.id as candidate_id',
                    'candidates.first_name',
                    'candidates.last_name',
                    DB::raw('COUNT(DISTINCT votes.id) as total'),
                    DB::raw('COUNT(DISTINCT CASE WHEN votes.online_vote = 1 THEN votes.id END) as online'),
                    DB::raw('COUNT(DISTINCT CASE WHEN votes.online_vote = 0 AND votes.id IS NOT NULL THEN votes.id END) as offline'),
                ])
                ->groupBy(
                    'positions.id',
                    'positions.title',
                    'positions.priority',
                    'positions.vacant_count',
                    'candidates.id',
                    'candidates.first_name',
                    'candidates.last_name'
                )
                ->get();

            // Totals
            $totalVotes        = $voteAgg->sum('total');
            $totalOnlineVotes  = $voteAgg->sum('online');
            $totalOfflineVotes = $voteAgg->sum('offline');
            $totalCandidates   = $voteAgg->unique('candidate_id')->count();

            // Build grouped summary
            $summary = $voteAgg
                ->groupBy('position_id')
                ->map(function (Collection $rows) {
                    $first = $rows->first();
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
                        'priority'       => $first->priority,
                        'vacant_count'   => $first->vacant_count,
                        'total_votes'    => $totalPositionVotes,
                        'candidates'     => $candidates,
                    ];
                })
                ->sortBy('priority') // ORDER BY POSITION PRIORITY
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
            Notification::make()
                ->title('Export Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
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
                $q->withCount(['votes' => fn ($q) => $q->where('is_valid', true)])
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

        // Step 3: Single query — online/offline valid vote counts for all candidates.
        $voteSplits = Vote::whereIn('candidate_id', $candidateIds)
            ->where('is_valid', true)
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
