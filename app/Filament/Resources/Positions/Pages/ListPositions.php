<?php

namespace App\Filament\Resources\Positions\Pages;

use App\Exports\PositionsExport;
use App\Filament\Resources\Positions\PositionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Maatwebsite\Excel\Facades\Excel;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Export Filters')
                        ->description('Filter the positions to export')
                        ->schema([
                            Radio::make('is_active')
                                ->label('Status')
                                ->options([
                                    'all'      => 'All Positions',
                                    'active'   => 'Active Only',
                                    'inactive' => 'Inactive Only',
                                ])
                                ->default('all')
                                ->inline(),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $filename = 'positions-' . now()->format('Y-m-d-His') . '.xlsx';

                        return Excel::download(
                            new PositionsExport($data),
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
