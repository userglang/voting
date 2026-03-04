<?php

namespace App\Filament\Resources\RevoteWindows\Pages;

use App\Filament\Resources\RevoteWindows\RevoteWindowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRevoteWindows extends ListRecords
{
    protected static string $resource = RevoteWindowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
