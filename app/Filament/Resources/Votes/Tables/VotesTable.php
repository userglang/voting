<?php

namespace App\Filament\Resources\Votes\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use League\Csv\Writer;

class VotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Control Number - Searchable, sortable, and copyable
                TextColumn::make('control_number')
                    ->label('Control #')
                    ->copyable()
                    ->sortable()
                    ->copyMessage('Control number copied!')
                    ->tooltip('Click to copy')
                    ->weight('medium'),

                // Member Information - Enhanced with avatar placeholder and better formatting
                TextColumn::make('member.full_name')
                    ->label('Member')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('member', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn ($record) => $record->member?->branch?->branch_name ?? 'No Branch Assigned')
                    ->wrap()
                    ->icon('heroicon-m-user')
                    ->iconColor('success')
                    ->weight('semibold')
                    ->limit(40),

                // Candidate Information - Enhanced with position badge
                TextColumn::make('candidate.full_name')
                    ->label('Candidate')
                    ->description(fn ($record) => $record->candidate?->position?->title ?? 'Position Not Set')
                    ->wrap()
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('warning')
                    ->weight('semibold')
                    ->limit(40),

                // Branch - Quick reference with color coding
                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Vote Type - Enhanced visual indicator
                IconColumn::make('online_vote')
                    ->label('Vote Type')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-building-office-2')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state): string => $state ? 'Online Vote' : 'In-Person Vote')
                    ->alignCenter(),

                // Timestamp Information - Better formatted
                TextColumn::make('created_at')
                    ->label('Cast At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->updated_at->diffForHumans())
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
            ])
            ->filters([
                // Example filter to be added if required in the future
                // TextFilter::make('status')->label('Status')->options([...])
                // Filter by vote type (online vs in-person)
                TernaryFilter::make('online_vote')
                    ->label('Vote Type')
                    ->placeholder('All Votes')
                    ->trueLabel('Online Votes')
                    ->falseLabel('In-Person Votes')
                    ->queries(
                        true: fn (Builder $query) => $query->where('online_vote', true),
                        false: fn (Builder $query) => $query->where('online_vote', false),
                    ),

                // Filter by branch
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'branch_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                // Filter by position
                SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('candidate.position', 'title')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                // Filter by date range
                SelectFilter::make('date_range')
                    ->label('Date Range')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'week' => 'This Week',
                        'month' => 'This Month',
                        'year' => 'This Year',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'today' => $query->whereDate('created_at', today()),
                            'yesterday' => $query->whereDate('created_at', today()->subDay()),
                            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                            'year' => $query->whereYear('created_at', now()->year),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                // EditAction::make(), // Record editing functionality
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-m-eye'),
                    EditAction::make()
                        ->icon('heroicon-m-pencil-square'),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
                ->label('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            // Export logic here - you can use Excel export or CSV
                            return response()->streamDownload(function () use ($records) {
                                $csv = Writer::createFromString('');
                                $csv->insertOne([
                                    'ID', 'Member Code', 'CID', 'Member Complete Name', 'Last Name', 'First Name', 'Middle Name',
                                    'Branch Number', 'Branch Name', 'Control Number', 'Candidate ID', 'Candidate Complete Name', 'Position Title', 'Vote Type',
                                    'Casted Date'
                                ]);

                                foreach ($records as $record) {
                                    $csv->insertOne([
                                        $record->id,
                                        $record->member_code,
                                        $record->member->cid,
                                        $record->member->full_name,
                                        $record->member->last_name,
                                        $record->member->first_name,
                                        $record->member->middle_name,
                                        $record->branch_number,
                                        $record->branch->branch_name,
                                        $record->control_number,
                                        $record->candidate_id,
                                        $record->candidate->full_name,
                                        $record->candidate->position->title,
                                        $record->online_vote,
                                        $record->created_at ? $record->created_at->format('Y-m-d') : 'N/A',
                                    ]);
                                }

                                echo $csv->toString();
                            }, 'vote-export-' . now()->format('Y-m-d-H-i-s') . '.csv', [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="members-export-' . now()->format('Y-m-d-H-i-s') . '.csv"',
                            ]);
                        }),

                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Votes')
                        ->modalDescription('Are you sure you want to delete these votes? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete')
                        ->successNotificationTitle('Votes deleted successfully'),

                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->deferLoading()
            ->searchOnBlur()
            ->searchDebounce('500ms')
            ->poll(null)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->extremePaginationLinks()
            ->emptyStateHeading('No Votes Recorded')
            ->emptyStateDescription('Votes will appear here once members start casting their ballots.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
