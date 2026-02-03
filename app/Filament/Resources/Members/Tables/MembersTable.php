<?php

namespace App\Filament\Resources\Members\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Basic Info
                TextColumn::make('code')
                    ->label('Member Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('cid')
                    ->label('CID')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Name (Combined)
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->state(fn ($record) => trim(
                        "{$record->first_name} {$record->middle_name} {$record->last_name}"
                    ))
                    ->searchable(
                        query: fn ($query, $search) =>
                            $query->whereRaw(
                                "CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?",
                                ["%{$search}%"]
                            )
                    )
                    ->sortable(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->toggleable(),


                // Personal Details
                TextColumn::make('birth_date')
                    ->label('Birth Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Male' => 'info',
                        'Female' => 'pink',
                        default => 'gray',
                    }),

                // Membership Info
                TextColumn::make('registration_type')
                    ->badge()
                    ->colors([
                        'primary' => 'Online',
                        'success' => 'On-premise',
                        'danger'  => fn ($state) => $state === 'Not Registered',
                    ])
                    ->getStateUsing(fn ($record) => $record->registration_type ?: 'Not Registered'),

                // Financial

                TextColumn::make('share_amount')
                    ->label('Share Amount')
                    ->money('PHP')
                    ->sortable(),

                // Status Indicators
                IconColumn::make('is_migs')
                    ->label('MIGS')
                    ->boolean()
                    ->tooltip('Member is Non-MIGS')
                    ->alignCenter(),

                // Meta
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // You can later add:
                // - Active / Inactive
                // - Gender
                // - Registration Type
                // - Date ranges
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete Selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->deferLoading()
            ->searchOnBlur()
            ->searchDebounce('750ms')
            // Disable polling for large datasets
            ->poll(null)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->extremePaginationLinks()
            ->emptyStateHeading('No member found')
            ->emptyStateDescription('Get started by creating your first member information.')
            ->emptyStateIcon('heroicon-o-identification')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create First Member')
                    ->icon('heroicon-s-plus'),
            ]);
    }
}
