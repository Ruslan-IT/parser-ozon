<?php

namespace App\Filament\Resources\ParserItems\Schemas;


use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;


class ParserItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan('full'), // новая строка



                TextInput::make('price')
                    ->label('Цена-мин')
                    ->required()
                    ->numeric()
                    ->columnSpan('full'), // новая строка samsung galaxy a35
            ]);
    }
}
