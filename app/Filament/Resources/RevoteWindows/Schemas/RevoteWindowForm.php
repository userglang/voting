<?php

namespace App\Filament\Resources\RevoteWindows\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RevoteWindowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Position Details')
                    ->description('Link this revote window to a specific position.')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Select::make('position_id')
                            ->label('Position')
                            ->relationship('position', 'title')
                            ->required()
                            ->preload()
                            ->placeholder('Select a position...')
                            ->searchable()
                            ->hint('Select a position from the list')
                            ->helperText('The position this revote window applies to.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Revote Window Schedule')
                    ->description('Define when the revote window opens and closes.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_at')
                                    ->label('Start Date & Time')
                                    ->required()
                                    ->native(false)
                                    ->icon('heroicon-o-play')
                                    ->helperText('When the revote window opens.')
                                    ->hoursStep(1)
                                    ->minutesStep(1)
                                    ->seconds(),

                                DateTimePicker::make('end_at')
                                    ->label('End Date & Time')
                                    ->required()
                                    ->native(false)
                                    ->icon('heroicon-o-stop')
                                    ->helperText('When the revote window closes.')
                                    ->hoursStep(1)
                                    ->minutesStep(1)
                                    ->seconds()
                                    ->afterOrEqual('start_at'),
                            ]),
                    ]),

                Section::make('Reason')
                    ->description('Provide a justification for opening this revote window.')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for Revote')
                            ->placeholder('Explain why this revote window is being opened...')
                            ->helperText('This will be visible to voters and administrators.')
                            ->rows(4)
                            ->default(null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
