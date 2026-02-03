<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch_number')
                    ->label('Branch #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('branch_name')
                    ->label('Branch Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    ->description(fn ($record) => $record->code ?? 'No code'),

                TextColumn::make('address')
                    ->label('Location')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->address)
                    ->placeholder('No address provided')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray'),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Inactive',
                    ])
                    ->icons([
                        'heroicon-s-check-circle' => 'Active',
                        'heroicon-s-x-circle' => 'Inactive',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray')
                    ->size('sm')
                    ->since(),
            ])
            ->filters([
                //
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->placeholder('All statuses'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->requiresConfirmation()
                            ->deselectRecordsAfterCompletion(),
                        BulkAction::make('activate')
                            ->label('Activate Selected')
                            ->icon('heroicon-s-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->action(fn ($records) => $records->each->update(['is_active' => true]))
                            ->deselectRecordsAfterCompletion(),
                        BulkAction::make('deactivate')
                            ->label('Deactivate Selected')
                            ->icon('heroicon-s-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(fn ($records) => $records->each->update(['is_active' => false]))
                            ->deselectRecordsAfterCompletion(),
                    ])
                    ->label('Bulk Actions'),
                ]),
            ])
            ->defaultSort('branch_name', 'asc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->extremePaginationLinks()
            ->emptyStateHeading('No branches found')
            ->emptyStateDescription('Get started by creating your first branch location.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create First Branch')
                    ->icon('heroicon-s-plus'),
            ]);
    }
}
