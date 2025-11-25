<?php

namespace App\Filament\Resources\ParserItems;

use App\Filament\Resources\ParserItems\Pages\CreateParserItem;
use App\Filament\Resources\ParserItems\Pages\EditParserItem;
use App\Filament\Resources\ParserItems\Pages\ListParserItems;
use App\Filament\Resources\ParserItems\Schemas\ParserItemForm;
use App\Filament\Resources\ParserItems\Tables\ParserItemsTable;
use App\Models\ParserItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParserItemResource extends Resource
{
    protected static ?string $model = ParserItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'no';

    public static function form(Schema $schema): Schema
    {
        return ParserItemForm::configure($schema);
    }


    public static function table(Table $table): Table
    {
        return ParserItemsTable::configure($table);
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
            'index' => ListParserItems::route('/'),
            'create' => CreateParserItem::route('/create'),
            'edit' => EditParserItem::route('/{record}/edit'),
        ];
    }
}
