<?php

namespace App\Filament\Resources\RevoteWindows\Pages;

use App\Filament\Resources\RevoteWindows\RevoteWindowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRevoteWindow extends EditRecord
{
    protected static string $resource = RevoteWindowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
