<?php

namespace App\Filament\Resources\Votes\Pages;

use App\Filament\Resources\Votes\VoteResource;
use App\Exports\VotesExport;
use App\Exports\VotesSummaryExport;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Storage;

class ListVotes extends ListRecords
{
    protected static string $resource = VoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Vote')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->modalWidth('3xl')
                ->slideOver()
                ->successNotificationTitle('Vote Recorded')
                ->after(function (\App\Models\Vote $record) {
                    \App\Models\Member::where('code', $record->member_code)
                        ->update([
                            'is_registered' => true,
                            'registration_type' => $record->online_vote ? 'online' : 'offline',
                        ]);
                }),

            // Export Dropdown Group
            Actions\ActionGroup::make([
                // Export to Excel - Detailed
                Actions\Action::make('exportExcel')
                    ->label('Export to Excel (Detailed)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
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
                                    ->options(\App\Models\Position::pluck('title', 'id'))
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
                Actions\Action::make('exportSummary')
                    ->label('Export to Excel (Summary)')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
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
                Actions\Action::make('exportPdf')
                    ->label('Export to PDF (Detailed)')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
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
                                    ->options(\App\Models\Position::pluck('title', 'id'))
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
                            $query = \App\Models\Vote::query()
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
                                    ? \App\Models\Branch::where('branch_number', $data['branch_number'])->first()?->branch_name
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
                Actions\Action::make('exportPdfSummary')
                    ->label('Export to PDF (Summary)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('warning')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
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
                            $query = \App\Models\Vote::query()
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
                                    ? \App\Models\Branch::where('branch_number', $data['branch_number'])->first()?->branch_name
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
                ->label('Export Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->button(),

            Actions\Action::make('resetVotes')
                ->label('Reset Votes')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Votes')
                ->modalDescription('This action will delete votes and reset member registration status. You must enter your password to confirm.')
                ->modalWidth('lg')
                ->form([
                    Radio::make('vote_type')
                        ->label('Vote Type')
                        ->options([
                            'all' => 'All Votes',
                            'online' => 'Online Votes Only',
                            'offline' => 'Offline Votes Only',
                        ])
                        ->default('all')
                        ->required()
                        ->inline(),

                    Select::make('branch_number')
                        ->label('Branch (Optional)')
                        ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                        ->searchable()
                        ->placeholder('All Branches'),

                    Checkbox::make('reset_member_registration')
                        ->label('Reset Member Registration Status')
                        ->helperText('Uncheck if you only want to delete votes without resetting member registration')
                        ->default(true),

                    TextInput::make('password')
                        ->label('Confirm Password')
                        ->password()
                        ->required()
                        ->revealable()
                        ->helperText('Enter your password to confirm this action')
                        ->rule(function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (!\Illuminate\Support\Facades\Hash::check($value, auth()->user()->password)) {
                                    $fail('The password is incorrect.');
                                }
                            };
                        }),
                ])
                ->action(function (array $data) {
                    try {
                        \Illuminate\Support\Facades\DB::beginTransaction();

                        // Build vote query
                        $voteQuery = \App\Models\Vote::query();

                        // Filter by vote type
                        if ($data['vote_type'] === 'online') {
                            $voteQuery->where('online_vote', true);
                        } elseif ($data['vote_type'] === 'offline') {
                            $voteQuery->where('online_vote', false);
                        }

                        // Filter by branch if selected
                        if (!empty($data['branch_number'])) {
                            $voteQuery->where('branch_number', $data['branch_number']);
                        }

                        // Get affected member codes before deletion
                        $affectedMemberCodes = $voteQuery->pluck('member_code')->unique();

                        // Count votes to be deleted
                        $voteCount = $voteQuery->count();

                        // Delete votes
                        $voteQuery->delete();

                        $memberCount = 0;

                        // Update members if checkbox is checked
                        if ($data['reset_member_registration']) {
                            $memberQuery = \App\Models\Member::whereIn('code', $affectedMemberCodes);

                            // If branch filter is applied, also filter members by branch
                            if (!empty($data['branch_number'])) {
                                $memberQuery->where('branch_number', $data['branch_number']);
                            }

                            $memberCount = $memberQuery->update([
                                'is_registered' => false,
                                'registration_type' => 'Online',
                            ]);
                        }

                        \Illuminate\Support\Facades\DB::commit();

                        $message = "Successfully deleted {$voteCount} vote(s)";
                        if ($data['reset_member_registration']) {
                            $message .= " and reset {$memberCount} member(s) registration status";
                        }

                        Notification::make()
                            ->title('Votes Reset Successfully')
                            ->body($message . '.')
                            ->success()
                            ->duration(5000)
                            ->send();

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();

                        Notification::make()
                            ->title('Reset Failed')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->duration(5000)
                            ->send();
                    }
                })
        ];
    }
}
