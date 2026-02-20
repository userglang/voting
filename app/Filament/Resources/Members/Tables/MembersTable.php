<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use League\Csv\Writer;

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
                    ->searchable(query: function ($query, $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn ($record) => $record->branch?->branch_name)
                    ->copyable()
                    ->copyMessage('Name copied!')
                    ->sortable()
                    ->toggleable(),


                // Personal Details
                TextColumn::make('birth_date')
                    ->label('Birth Date')
                    ->date('M d, Y')
                    ->description(fn ($record) => $record->age
                        ? $record->age . ' year' . ($record->age != 1 ? 's' : '') . ' old'
                        : null
                    )
                    ->toggleable(),

                TextColumn::make('address')
                    ->label('Address')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->address)
                    ->description(fn ($record) =>
                        $record->contact_number
                            ? '📱 ' . $record->contact_number
                            : ($record->email ? '✉️ ' . $record->email : null)
                    )
                    ->toggleable(),

                TextColumn::make('gender')
                    ->color(fn (string $state) => match ($state) {
                        'Male' => 'info',
                        'Female' => 'pink',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                // Membership Info
                TextColumn::make('registration_type')
                    ->label('Registration Type')
                    ->getStateUsing(fn ($record) => strtoupper($record->registration_type ?: 'Not Registered'))
                    ->description(fn ($record) => $record->is_registered ? 'Registered' : 'Not Registered')
                    ->alignCenter()
                    ->toggleable(),

                // Status Indicators
                TextColumn::make('is_migs')
                    ->label('MIGS')
                    ->formatStateUsing(fn ($state) => strtoupper($state ? 'MIGS' : 'Non-MIGS'))
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->description(fn ($record) => $record->share_amount ? 'P3,000.00 & Above' : 'Below P3,000.00')
                    ->alignCenter()
                    ->toggleable(),

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
                // Custom Update Action for Registration Status
                Action::make('registered')
                    ->label('Mark Registered')  // Action label
                    ->icon('heroicon-o-check')  // Icon for the action
                    ->requiresConfirmation()  // Enable confirmation modal
                    ->modalHeading('Confirm Registration Status Update')  // Modal heading
                    ->modalDescription('Please confirm that you want to mark this member as registered. You can also provide additional information if needed.')  // Modal description
                    ->form([
                        TextInput::make('notes')
                            ->label('Notes')
                            ->placeholder('Enter any additional notes...')
                            ->nullable()  // Make this field optional
                            ->maxLength(255),  // Optional length validation


                    ])  // Add a form with two fields (notes and send_notification)
                    ->action(function (Member $record, array $data) {
                        // Perform the update action here
                        $record->update([
                            'is_registered' => true,
                            // 'remarks' => $data['notes'] ?? null,  // Save notes if provided
                        ]);
                    })
                    ->visible(fn (Member $record) => !$record->is_registered),  // Only show if the member is not already registered

                Action::make('unregistered')
                    ->label('Mark Unregistered')  // Action label
                    ->icon('heroicon-o-exclamation-circle')  // Icon for the action
                    ->requiresConfirmation()  // Enable confirmation modal
                    ->modalHeading('Confirm Registration Status Update')  // Modal heading
                    ->modalDescription('Are you sure you want to mark this member as unregistered? This action cannot be undone.')  // Modal description
                    ->action(fn (Member $record) => $record->update(['is_registered' => false]))  // Action to perform
                    ->visible(fn (Member $record) => $record->is_registered),  // Only show if the member is registered
                ActionGroup::make([
                    // Edit Action
                    EditAction::make()
                        ->label('Edit Member')
                        ->icon('heroicon-m-pencil-square'),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Mark as Registered Bulk Action
                    BulkAction::make('mark_as_registered')
                        ->label('Mark as Registered')  // Bulk action label
                        ->icon('heroicon-o-check')  // Icon for the action
                        ->requiresConfirmation()  // Enable confirmation
                        ->modalHeading('Confirm Bulk Registration')  // Modal heading for bulk action
                        ->modalDescription('Are you sure you want to mark all selected members as registered? This action cannot be undone.')  // Modal description
                        ->action(fn (Collection $records) => $records->each->update(['is_registered' => true]))  // Action to perform on each selected member
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Members Registered')
                                ->body('Selected members have been marked as registered successfully.')
                        ),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Members')
                        ->modalDescription('Are you sure you want to activate all selected members?')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Members Activated')
                                ->body('Selected members have been activated successfully.')
                        ),
                    BulkAction::make('deactivate')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Archive Selected Members')
                        ->modalDescription('Are you sure you want to archive all selected members?')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Members Archived')
                                ->body('Selected members have been Archived successfully.')
                        ),
                    BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            // Export logic here - you can use Excel export or CSV
                            return response()->streamDownload(function () use ($records) {
                                $csv = Writer::createFromString('');
                                $csv->insertOne([
                                    'Code', 'CID', 'Full Name', 'Last Name', 'First Name', 'Middle Name', 'Share Account', 'Branch', 'Email', 'Phone', 'Address',
                                    'Religion', 'Birth Date', 'Age', 'Gender', 'Marital Status', 'Is Registered',
                                    'Status', 'Registration Type',
                                ]);

                                foreach ($records as $record) {
                                    $csv->insertOne([
                                        $record->code,
                                        $record->cid,
                                        $record->full_name,
                                        $record->last_name,
                                        $record->first_name,
                                        $record->middle_name,
                                        $record->share_account,
                                        $record->branch->branch_name,
                                        $record->email,
                                        $record->contact_number,
                                        $record->address,
                                        $record->religion,
                                        $record->birth_date,
                                        $record->age,
                                        $record->gender,
                                        $record->marital_status,
                                        $record->is_registered,
                                        $record->is_active,
                                        $record->registration_type,
                                    ]);
                                }

                                echo $csv->toString();
                            }, 'ga-members-export-' . now()->format('Y-m-d-H-i-s') . '.csv', [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="members-export-' . now()->format('Y-m-d-H-i-s') . '.csv"',
                            ]);
                        }),
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
