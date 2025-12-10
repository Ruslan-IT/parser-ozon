<?php

namespace App\Filament\Resources\ParserItems\Tables;

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
                TextInputColumn::make('url')
                    ->label('Ссылка')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    //->textarea() // делает поле многострочным
                    //->maxLength(500) // максимальная длина текста

                TextColumn::make('price')
                    ->label('Цена')
                    //->numeric()
                    ->sortable(),
            ])

            ->headerActions([
                Action::make('runAllParsers')
                    ->label('Запустить парсер для всех моделей')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {

                        $items = ParserItem::all(); // все модели

                        foreach ($items as $item) {

                            $response = Http::timeout(60)->post('http://155.212.219.85:5001/run-parser', [
                                'query' => $item->name,
                                'max_items' => 20,
                                'price_min' => $item->price,
                            ]);

                            $minPrice = intval(preg_replace('/[^\d.]/', '', $item->price));

                            if ($response->failed()) {
                                Notification::make()
                                    ->title("Ошибка при парсинге: {$item->name}")
                                    ->danger()
                                    ->send();
                                continue;
                            }

                            // пример сохранения
                            $data = $response->json();

                           

                            foreach ($data['products'] as $i) {

                                dd($minPrice);


                                Product::create([
                                    'title' => $i['title'] ?? null,
                                    'url'   => $i['url'] ?? null,
                                    'price' => $i['price'] ?? null,
                                    'min_price' => $minPrice,
                                    'delivery' => $i['delivery'] ?? null,
                                    'sent_alert' => 0,
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Парсинг всех моделей завершён!')
                            ->success()
                            ->send();
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
