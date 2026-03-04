<?php

namespace App\Filament\Resources\RevoteWindows;

use App\Filament\Resources\RevoteWindows\Pages\CreateRevoteWindow;
use App\Filament\Resources\RevoteWindows\Pages\EditRevoteWindow;
use App\Filament\Resources\RevoteWindows\Pages\ListRevoteWindows;
use App\Filament\Resources\RevoteWindows\Schemas\RevoteWindowForm;
use App\Filament\Resources\RevoteWindows\Tables\RevoteWindowsTable;
use App\Models\RevoteWindow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RevoteWindowResource extends Resource
{
    protected static ?string $model = RevoteWindow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RevoteWindowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RevoteWindowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRevoteWindows::route('/'),
            'create' => CreateRevoteWindow::route('/create'),
            'edit' => EditRevoteWindow::route('/{record}/edit'),
        ];
    }
}
