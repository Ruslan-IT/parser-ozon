<?php

namespace App\Filament\Resources\ParserItems\Tables;

use App\Http\Controllers\ProductAlertController;
use App\Jobs\RunParserJob;
use App\Models\ParserItem;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Support\Facades\Http;

class ParserItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Марка')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    //>inlineEditable(), // Фильд редактируется прямо в таблице


                TextColumn::make('price')
                    ->label('Цена-мин')
                    //->numeric()
                    ->sortable(),
            ])

            ->headerActions([
                Action::make('runAllParsers')
                    ->label('Запустить парсера для всех моделей')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {

                        $items = ParserItem::all(); // все модели

                        foreach ($items as $item) {
                            RunParserJob::dispatch($item->id)
                                ->onQueue('parsers');
                        }

                        Notification::make()
                            ->title('Парсинг всех моделей завершён!')
                            ->success()
                            ->send();

                        //  сразу отправляем уведомления
                        (new ProductAlertController)->sendAlerts();

                    }),
            ])

            ->filters([
                //
            ])

            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc') // сортировка по умолчанию
            ->paginated([10, 25, 50]); // пагинация по 15 записей
    }

}
