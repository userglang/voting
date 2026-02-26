<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Exports\BranchesExport;
use App\Filament\Resources\Branches\BranchResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Maatwebsite\Excel\Facades\Excel;

class ListBranches extends ListRecords
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Export Filters')
                        ->description('Filter the branches to export')
                        ->schema([
                            Radio::make('is_active')
                                ->label('Status')
                                ->options([
                                    'all'      => 'All Branches',
                                    'active'   => 'Active Only',
                                    'inactive' => 'Inactive Only',
                                ])
                                ->default('all')
                                ->inline(),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $filename = 'branches-' . now()->format('Y-m-d-His') . '.xlsx';

                        return Excel::download(
                            new BranchesExport($data),
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
            CreateAction::make(),
        ];
    }
}
