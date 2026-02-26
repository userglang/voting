<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Exports\CandidatesExport;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Models\Position;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Maatwebsite\Excel\Facades\Excel;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Export Filters')
                        ->description('Filter the candidates to export')
                        ->schema([
                            Select::make('position_id')
                                ->label('Position')
                                ->options(Position::pluck('title', 'id'))
                                ->searchable()
                                ->placeholder('All Positions'),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $filename = 'candidates-' . now()->format('Y-m-d-His') . '.xlsx';

                        return Excel::download(
                            new CandidatesExport($data),
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
