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

                Textarea::make('url')
                    ->label('Ссылка')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpan('full') // новая строка и полностью видимое поле

                    // если хочешь, чтобы автоматически показывался весь текст:
                    ->rows(3), // высота textarea в 3 строки

                TextInput::make('price')
                    ->label('Цена')
                    ->required()
                    ->numeric()
                    ->columnSpan('full'), // новая строка
            ]);
    }
}
