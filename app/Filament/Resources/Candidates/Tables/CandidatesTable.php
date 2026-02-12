<?php

namespace App\Filament\Resources\Candidates\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                // Profile Image - Circular with placeholder
                ImageColumn::make('image_base64') // Use the base64 accessor
    ->label('Photo')
    ->circular()
    ->size(80)
    ->extraImgAttributes([
        'class' => 'border-2 border-gray-200',
        'style' => 'object-fit: cover;'
    ])
    ->alignCenter(),

                // Full Name - Enhanced with better formatting
                TextColumn::make('full_name')
                    ->label('Candidate Name')
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    })
                    ->getStateUsing(function ($record) {
                        $middleInitial = $record->middle_name
                            ? ' ' . strtoupper(substr($record->middle_name, 0, 1)) . '.'
                            : '';
                        return "{$record->first_name}{$middleInitial} {$record->last_name}";
                    })
                    ->description(fn ($record) => $record->position?->title ?? 'Position Not Set')
                    ->icon('heroicon-m-user')
                    ->iconColor('primary')
                    ->weight('semibold')
                    ->wrap()
                    ->copyable()
                    ->copyMessage('Name copied!')
                    ->limit(50),

                // Background Profile - Improved readability
                TextColumn::make('background_profile')
                    ->label('Background')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn ($record): ?string => $record->background_profile)
                    ->placeholder('No background provided')
                    ->color('gray')
                    ->limit(100)
                    ->toggleable(isToggledHiddenByDefault: false),

                // Timestamp Information
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->updated_at->diffForHumans())
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add custom filters here in the future (e.g., position, status, etc.)
                // Example: TextFilter::make('status')->label('Status')->options([...])

                SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'title')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All Positions'),

                // Filter by candidates with/without background
                TernaryFilter::make('has_background')
                    ->label('Background Profile')
                    ->placeholder('All Candidates')
                    ->trueLabel('With Background')
                    ->falseLabel('Without Background')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('background_profile')
                            ->where('background_profile', '!=', ''),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('background_profile')
                            ->orWhere('background_profile', '=', '');
                        }),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
                ->label('Actions')
                ->tooltip('Candidate Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Candidates')
                        ->modalDescription('Are you sure you want to delete these candidates? This will also remove all associated votes and cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete Candidates')
                        ->successNotificationTitle('Candidates deleted successfully')
                        ->deselectRecordsAfterCompletion(),
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
            ->emptyStateHeading('No Candidates Yet')
            ->emptyStateDescription('Start by adding candidates to the system for the upcoming election.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
