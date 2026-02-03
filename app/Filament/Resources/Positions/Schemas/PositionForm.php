<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // Section for Position Details
                Section::make('Position Details')
                    ->columnSpanFull()
                    ->columns(2) // Two columns layout for better readability
                    ->components([
                        // Position Title Input with improved helper text and placeholder
                        TextInput::make('title')
                            ->label('Position Title')
                            ->required()
                            ->maxLength(255) // Limit the length to ensure consistency
                            ->placeholder('e.g. Manager, Secretary, Treasurer')
                            ->helperText('Specify the position name (e.g., "Manager" or "Secretary"). Keep it concise.'),

                        // Vacant Positions with numeric validation and clear helper text
                        TextInput::make('vacant_count')
                            ->label('Vacant Positions')
                            ->required()
                            ->numeric() // Ensures only numbers are entered
                            ->default(0)
                            ->placeholder('e.g. 5')
                            ->helperText('Enter the number of available positions for this role. Must be a positive number.'),

                        // Priority with clearer placeholder and range validation
                        TextInput::make('priority')
                            ->label('Priority Number')
                            ->required()
                            ->numeric() // Ensures only numbers are entered
                            ->default(0)
                            ->placeholder('e.g. 1 (highest priority)')
                            ->helperText('Priority number determines the order (e.g., 1 is the highest priority).'),
                    ]),

                // Section for Position Status
                Section::make('Position Status')
                    ->columnSpanFull()
                    ->columns(1) // One column layout since we have a toggle for active status
                    ->components([

                        // Toggle for active status with a clearer description
                        Toggle::make('is_active')
                            ->label('Is Position Active?')
                            ->required()
                            ->default(true) // Default to active
                            ->helperText('Toggle to mark this position as active or inactive. Inactive positions will not be considered in the voting process.'),
                    ]),
            ]);
    }
}
