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

                            $response = Http::timeout(60)->post('http://127.0.0.1:5001/run-parser2', [
                                'query' => $item->name,
                                'max_items' => 20,
                            ]);

                            if ($response->failed()) {
                                Notification::make()
                                    ->title("Ошибка при парсинге: {$item->name}")
                                    ->danger()
                                    ->send();
                                continue;
                            }

                            // пример сохранения
                            $data = $response->json();

                            //dd($data);

                            foreach ($data['products'] as $i) {

                                Product::create([
                                    'title' => $i['title'] ?? null,
                                    'url'   => $i['url'] ?? null,
                                    'price' => $i['price'] ?? null,
                                    'delivery' => $i['delivery'] ?? null,
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),

                Action::make('sendToTelegram')
                    ->label('Отправить в Telegram')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->action(function (\App\Models\ParserItem $record) {

                        $token = env('TELEGRAM_BOT_TOKEN');

                        // Добавляем несколько ID
                        $chatIds = [
                            env('TELEGRAM_CHAT_ID'), // первый (из .env)
                            5985008383,              // второй пользователь
                            1951908603,              // второй пользователь

                            // добавляй сюда ещё ID по желанию
                        ];

                        $text = "📦 *Новый товар:*\n"
                            . "Название: {$record->name}\n"
                            . "Цена: {$record->price}\n"
                            . "Ссылка: {$record->url}";

                        foreach ($chatIds as $chatId) {
                            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                'chat_id' => $chatId,
                                'text' => $text,
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Отправить товар в Telegram?')
                    ->modalSubheading('Сообщение будет отправлено всем выбранным пользователям.')
                    ->modalButton('Отправить'),


            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc') // сортировка по умолчанию
            ->paginated([10, 25, 50]); // пагинация по 15 записей
    }

}
