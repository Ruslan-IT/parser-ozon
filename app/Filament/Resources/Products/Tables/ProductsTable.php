<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('price')->label('Цена')->sortable()->searchable(),
                TextColumn::make('delivery')->label('Доставка')->sortable()->searchable(),
                TextColumn::make('title')->label('Название')->sortable()->searchable(),
                TextColumn::make('url')->label('Ссылка')->url(fn ($record) => $record->url, true),
            ])
            ->filters([
                //
            ])

            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
