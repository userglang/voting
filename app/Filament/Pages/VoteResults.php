<?php

namespace App\Filament\Pages;

use App\Exports\MembersExport;
use App\Exports\MembersSummaryExport;
use App\Exports\VotesExport;
use App\Exports\VotesSummaryExport;
use App\Models\Branch;
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
use Filament\Support\Facades\FilamentAsset;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class VoteResults extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::QueueList;
    protected static ?string $navigationLabel = 'Vote Results';
    protected static ?string $title           = 'Candidate Vote Results';
    protected static ?string $slug            = 'vote-results';
    protected string  $view            = 'filament.pages.vote-results';

    protected static string | UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;

    public string $filterPosition = '';
    public string $search         = '';

    /**
     * Register the external JS file so Livewire never touches it.
     */
    public static function getAssets(): array
    {
        return [
            Js::make('vote-results-js', resource_path('js/filament/vote-results.js')),
        ];
    }



    protected function getHeaderActions(): array
    {
        return [

            ActionGroup::make([
                // Export to Excel - Detailed
                Action::make('voteExportExcel')
                    ->label('Export to Excel (Detailed)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Radio::make('vote_type')
                                    ->label('Vote Type')
                                    ->options([
                                        'all' => 'All Votes',
                                        'online' => 'Online Votes Only',
                                        'offline' => 'Offline Votes Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Select::make('position_id')
                                    ->label('Position')
                                    ->options(Position::pluck('title', 'id'))
                                    ->searchable()
                                    ->placeholder('All Positions'),

                                DatePicker::make('date_from')
                                    ->label('Date From')
                                    ->native(false),

                                DatePicker::make('date_to')
                                    ->label('Date To')
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'votes-detailed-' . now()->format('Y-m-d-His') . '.xlsx';

                            return Excel::download(
                                new VotesExport($data),
                                $filename
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export to Excel - Summary
                Action::make('exportSummary')
                    ->label('Export to Excel (Summary)')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Radio::make('vote_type')
                                    ->label('Vote Type')
                                    ->options([
                                        'all' => 'All Votes',
                                        'online' => 'Online Votes Only',
                                        'offline' => 'Offline Votes Only',
                                    ])
                                    ->default('all')
                                    ->inline(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'votes-summary-' . now()->format('Y-m-d-His') . '.xlsx';

                            return Excel::download(
                                new VotesSummaryExport($data),
                                $filename
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export to PDF - Detailed
                Action::make('exportPdf')
                    ->label('Export to PDF (Detailed)')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches')
                                    ->live(),

                                Radio::make('vote_type')
                                    ->label('Vote Type')
                                    ->options([
                                        'all' => 'All Votes',
                                        'online' => 'Online Votes Only',
                                        'offline' => 'Offline Votes Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Select::make('position_id')
                                    ->label('Position')
                                    ->options(Position::pluck('title', 'id'))
                                    ->searchable()
                                    ->placeholder('All Positions'),

                                DatePicker::make('date_from')
                                    ->label('Date From')
                                    ->native(false),

                                DatePicker::make('date_to')
                                    ->label('Date To')
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $query = Vote::query()
                                ->with(['member', 'candidate.position', 'branch'])
                                ->orderBy('created_at', 'desc');

                            // Apply filters
                            if (!empty($data['branch_number'])) {
                                $query->where('branch_number', $data['branch_number']);
                            }

                            if (!empty($data['vote_type'])) {
                                if ($data['vote_type'] === 'online') {
                                    $query->where('online_vote', true);
                                } elseif ($data['vote_type'] === 'offline') {
                                    $query->where('online_vote', false);
                                }
                            }

                            if (!empty($data['position_id'])) {
                                $query->whereHas('candidate', function ($q) use ($data) {
                                    $q->where('position_id', $data['position_id']);
                                });
                            }

                            if (!empty($data['date_from'])) {
                                $query->whereDate('created_at', '>=', $data['date_from']);
                            }

                            if (!empty($data['date_to'])) {
                                $query->whereDate('created_at', '<=', $data['date_to']);
                            }

                            $votes = $query->get();

                            // Prepare filter labels for PDF
                            $filters = [
                                'branch_name' => !empty($data['branch_number'])
                                    ? Branch::where('branch_number', $data['branch_number'])->first()?->branch_name
                                    : 'All Branches',
                                'vote_type_label' => match($data['vote_type'] ?? 'all') {
                                    'online' => 'Online Votes Only',
                                    'offline' => 'Offline Votes Only',
                                    default => 'All Vote Types',
                                },
                                'date_from' => $data['date_from'] ?? 'Beginning',
                                'date_to' => $data['date_to'] ?? 'Present',
                            ];

                            $pdf = Pdf::loadView('pdf.votes-report', [
                                'votes' => $votes,
                                'filters' => $filters,
                            ])->setPaper('a4', 'landscape');

                            $filename = 'votes-report-' . now()->format('Y-m-d-His') . '.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export to PDF - Summary (NEW!)
                Action::make('exportPdfSummary')
                    ->label('Export to PDF (Summary)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('warning')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Radio::make('vote_type')
                                    ->label('Vote Type')
                                    ->options([
                                        'all' => 'All Votes',
                                        'online' => 'Online Votes Only',
                                        'offline' => 'Offline Votes Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                DatePicker::make('date_from')
                                    ->label('Date From')
                                    ->native(false),

                                DatePicker::make('date_to')
                                    ->label('Date To')
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $query = Vote::query()
                                ->with(['candidate.position', 'branch']);

                            // Apply filters
                            if (!empty($data['branch_number'])) {
                                $query->where('branch_number', $data['branch_number']);
                            }

                            if (!empty($data['vote_type'])) {
                                if ($data['vote_type'] === 'online') {
                                    $query->where('online_vote', true);
                                } elseif ($data['vote_type'] === 'offline') {
                                    $query->where('online_vote', false);
                                }
                            }

                            if (!empty($data['date_from'])) {
                                $query->whereDate('created_at', '>=', $data['date_from']);
                            }

                            if (!empty($data['date_to'])) {
                                $query->whereDate('created_at', '<=', $data['date_to']);
                            }

                            $votes = $query->get();

                            // Group by position
                            $summary = $votes->groupBy(function ($vote) {
                                return $vote->candidate->position->id;
                            })->map(function ($positionVotes) {
                                $position = $positionVotes->first()->candidate->position;
                                $totalPositionVotes = $positionVotes->count();

                                $candidates = $positionVotes->groupBy('candidate_id')
                                    ->map(function ($candidateVotes) use ($totalPositionVotes) {
                                        $candidate = $candidateVotes->first()->candidate;
                                        $total = $candidateVotes->count();

                                        return [
                                            'name' => $candidate->full_name,
                                            'total' => $total,
                                            'online' => $candidateVotes->where('online_vote', true)->count(),
                                            'offline' => $candidateVotes->where('online_vote', false)->count(),
                                            'percentage' => $totalPositionVotes > 0
                                                ? round(($total / $totalPositionVotes) * 100, 2)
                                                : 0,
                                        ];
                                    })
                                    ->sortByDesc('total')
                                    ->values();

                                return [
                                    'position_title' => $position->title,
                                    'vacant_count' => $position->vacant_count,
                                    'total_votes' => $totalPositionVotes,
                                    'candidates' => $candidates,
                                ];
                            })
                            ->sortBy('position_title')
                            ->values();

                            // Calculate totals
                            $totalVotes = $votes->count();
                            $totalOnlineVotes = $votes->where('online_vote', true)->count();
                            $totalOfflineVotes = $votes->where('online_vote', false)->count();
                            $totalCandidates = $summary->sum(fn($pos) => $pos['candidates']->count());

                            // Prepare filter labels
                            $filters = [
                                'branch_name' => !empty($data['branch_number'])
                                    ? Branch::where('branch_number', $data['branch_number'])->first()?->branch_name
                                    : 'All Branches',
                                'vote_type_label' => match($data['vote_type'] ?? 'all') {
                                    'online' => 'Online Votes Only',
                                    'offline' => 'Offline Votes Only',
                                    default => 'All Vote Types',
                                },
                                'date_from' => $data['date_from'] ?? 'Beginning',
                                'date_to' => $data['date_to'] ?? 'Present',
                            ];

                            $pdf = Pdf::loadView('pdf.votes-summary', [
                                'summary' => $summary,
                                'filters' => $filters,
                                'totalVotes' => $totalVotes,
                                'totalOnlineVotes' => $totalOnlineVotes,
                                'totalOfflineVotes' => $totalOfflineVotes,
                                'totalCandidates' => $totalCandidates,
                            ])->setPaper('a4', 'portrait');

                            $filename = 'votes-summary-' . now()->format('Y-m-d-His') . '.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);

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

    public function getPositionsProperty(): Collection
    {
        return Position::where('is_active', true)
            ->orderBy('priority')
            ->get(['id', 'title']);
    }

    public function getResultsProperty(): Collection
    {
        return Position::query()
            ->where('is_active', true)
            ->when($this->filterPosition, fn ($q) => $q->where('id', $this->filterPosition))
            ->orderBy('priority')
            ->with(['candidates' => function ($q) {
                $q->withCount('votes')
                  ->when($this->search, fn ($q) =>
                      $q->where(fn ($q) =>
                          $q->where('first_name',  'like', "%{$this->search}%")
                            ->orWhere('last_name',  'like', "%{$this->search}%")
                            ->orWhere('middle_name','like', "%{$this->search}%")
                      )
                  )
                  ->orderByDesc('votes_count');
            }])
            ->get()
            ->map(fn (Position $position) => [
                'id'          => $position->id,
                'title'       => $position->title,
                'slots'       => $position->vacant_count ?? 1,
                'total_votes' => $position->candidates->sum('votes_count'),
                'candidates'  => $position->candidates->map(fn ($c) => [
                    'full_name'    => $c->full_name,
                    'total_votes'  => $c->votes_count,
                    'online_votes' => Vote::where('candidate_id', $c->id)->where('online_vote', true)->count(),
                    'onsite_votes' => Vote::where('candidate_id', $c->id)->where('online_vote', false)->count(),
                    'image_url'    => $c->profile_image_url,
                    'initials'     => strtoupper(substr($c->first_name, 0, 1) . substr($c->last_name, 0, 1)),
                ])->values(),
            ]);
    }

    protected function getViewData(): array
    {
        return [
            'positions' => $this->getPositionsProperty(),
            'results'   => $this->getResultsProperty(),
        ];
    }

    public function updatedFilterPosition(): void {}
    public function updatedSearch(): void {}
}
