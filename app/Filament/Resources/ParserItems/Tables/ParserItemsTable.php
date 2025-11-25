<?php

namespace App\Filament\Resources\ParserItems\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

                TextInputColumn::make('price')
                    ->label('Цена')
                    //->numeric()
                    ->sortable(),
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
                        $chatId = env('TELEGRAM_CHAT_ID');

                        $text = "📦 *Новый товар:*\n"
                            . "Название: {$record->name}\n"
                            . "Цена: {$record->price}\n"
                            . "Ссылка: {$record->url}";

                        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $text,
                            //'parse_mode' => 'Markdown',
                        ]);
                    })

                    ->requiresConfirmation()
                    ->modalHeading('Отправить товар в Telegram?')
                    ->modalSubheading('Сообщение будет отправлено вашему боту.')
                    ->modalButton('Отправить'),

            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc') // сортировка по умолчанию
            ->paginated([10, 25, 50]); // пагинация по 15 записей
    }

}
