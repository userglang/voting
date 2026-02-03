<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branch Information')
                    ->description('Enter the basic details for this branch')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        ...self::getBranchInformation(),
                        ...self::getStatusAndSettings(),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function getBranchInformation(): array
    {
        return
        [
            Grid::make(2)
                ->schema([
                    TextInput::make('branch_number')
                        ->label('Branch Number')
                        ->placeholder('Enter branch number (e.g., B001)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Unique identifier for the branch')
                        ->validationAttribute('branch number')
                        ->unique(ignoreRecord: true)
                        ->alphaDash(),

                    TextInput::make('code')
                        ->label('Branch Code')
                        ->placeholder('Enter branch code (e.g., DT-001)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Short code for quick identification')
                        ->validationAttribute('branch code')
                        ->unique(ignoreRecord: true)
                        ->alphaDash(),
                ]),

            TextInput::make('branch_name')
                ->label('Branch Name')
                ->placeholder('Enter branch name (e.g., Downtown Branch)')
                ->required()
                ->maxLength(255)
                ->helperText('Full name of the branch location')
                ->validationAttribute('branch name')
                ->columnSpanFull(),

            Textarea::make('address')
                ->label('Address')
                ->placeholder('Enter complete address...')
                ->maxLength(500)
                ->rows(3)
                ->helperText('Complete address including street, city, and postal code')
                ->columnSpanFull(),
        ];
    }

    public static function getStatusAndSettings(): array
    {
        return
        [
            Section::make('Status & Settings')
                ->description('Configure branch status and settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active Status')
                        ->helperText('Toggle to activate or deactivate this branch')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger')
                        ->onIcon('heroicon-s-check-circle')
                        ->offIcon('heroicon-s-x-circle')
                        ->inline(false),
                ]),
        ];
    }
}
