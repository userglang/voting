<?php

namespace App\Filament\Resources\RevoteWindows\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RevoteWindowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('position_id')
                    ->default(null),
                Textarea::make('reason')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('start_at'),
                DateTimePicker::make('end_at'),
            ]);
    }
}
