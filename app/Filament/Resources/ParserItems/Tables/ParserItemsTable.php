<?php

namespace App\Filament\Resources\ParserItems\Tables;

use App\Http\Controllers\ProductAlertController;
use App\Jobs\RunParserJob;
use App\Jobs\SendTelegramAlertsJob;
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
use Illuminate\Support\Facades\Bus;
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
                    ->label('Запустить парсер для всех моделей')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {

                        $items = ParserItem::all();

                        $jobs = $items->map(fn ($item) => new RunParserJob($item->id))->toArray();

                        $batch = Bus::batch($jobs)
                            ->name('Парсинг моделей')
                            ->onQueue('parsers')
                            ->then(function () {
                                // Все задачи выполнены - отправляем уведомления в Telegram
                                app(ProductAlertController::class)->sendAlerts();

                                Notification::make()
                                    ->title('Парсинг завершён')
                                    ->success()
                                    ->send();
                            })
                            ->catch(function () {
                                Notification::make()
                                    ->title('Ошибка при парсинге')
                                    ->danger()
                                    ->send();
                            })
                            ->dispatch();

                        // Сохраняем ID batch, если нужно для прогресса
                        cache(['parser_batch_id' => $batch->id], now()->addHour());

                        Notification::make()
                            ->title('Парсинг запущен')
                            ->body("Количество задач в очереди: {$batch->totalJobs}")
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
