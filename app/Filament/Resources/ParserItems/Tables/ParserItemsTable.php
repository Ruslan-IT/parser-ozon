<?php

namespace App\Filament\Resources\ParserItems\Tables;

use App\Http\Controllers\ProductAlertController;
use App\Jobs\RunParserJob;
use App\Models\ParserItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParserItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->sortable(),
                TextColumn::make('city')->label('Город')->sortable(),
                TextColumn::make('url')->label('Ссылка')->sortable()->searchable()->toggleable(),
                TextColumn::make('price')->label('Цена-мин')->sortable(),

                // Статус очереди (опционально)
                TextColumn::make('queue_status')
                    ->label('Статус очереди')
                    ->badge()
                    ->getStateUsing(function () {
                        return self::getQueueStatus();
                    })
                    ->color(fn ($state): string => match (true) {
                        str_contains($state, 'заданий') => 'warning',
                        str_contains($state, 'работает') => 'success',
                        default => 'gray',
                    }),
            ])

            ->headerActions([
                // Кнопка 1: Добавить задания в очередь (без запуска воркера)
                Action::make('addToQueue')
                    ->label('Добавить задания в очередь')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Добавить задания в очередь')
                    ->modalDescription('Задания будут добавлены в очередь для последующей обработки.')
                    ->modalSubmitActionLabel('Добавить')
                    ->action(function () {
                        try {
                            $items = ParserItem::all();

                            if ($items->isEmpty()) {
                                Notification::make()
                                    ->title('Нет моделей для парсинга')
                                    ->body('Сначала добавьте модели для парсинга.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $jobs = $items->map(fn ($item) => new RunParserJob($item->id))->toArray();

                            $batch = Bus::batch($jobs)
                                ->name('Парсинг моделей (ручной запуск)')
                                ->onQueue('parsers')
                                ->then(function () {
                                    // Отправляем уведомления в Telegram после завершения парсинга
                                    try {
                                        app(ProductAlertController::class)->sendAlerts();
                                    } catch (\Exception $e) {
                                        Log::error('Ошибка отправки в Telegram: ' . $e->getMessage());
                                    }

                                    Notification::make()
                                        ->title('Парсинг завершён')
                                        ->success()
                                        ->send();
                                })
                                ->catch(function ($batch, $e) {
                                    Log::error('Ошибка при парсинге: ' . $e->getMessage());

                                    Notification::make()
                                        ->title('Ошибка при парсинге')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                })
                                ->dispatch();

                            cache(['parser_batch_id' => $batch->id], now()->addHour());

                            Notification::make()
                                ->title('Задания добавлены в очередь')
                                ->body("Добавлено {$batch->totalJobs} заданий. Для обработки запустите воркер или дождитесь автоматического запуска через Cron.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Ошибка при добавлении заданий: ' . $e->getMessage());

                            Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Кнопка 2: Запустить воркер для обработки очереди
                Action::make('startWorker')
                    ->label('Запустить воркер')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Запустить воркер очереди')
                    ->modalDescription('Запустит обработку заданий, которые уже есть в очереди.')
                    ->modalSubmitActionLabel('Запустить')
                    ->action(function () {
                        try {
                            // Проверяем, есть ли задания в очереди
                            $jobCount = self::getJobCount();

                            if ($jobCount === 0) {
                                Notification::make()
                                    ->title('Очередь пуста')
                                    ->body('Нет заданий для обработки. Сначала добавьте задания в очередь.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Проверяем, не запущен ли уже воркер
                            if (self::isWorkerRunning()) {
                                Notification::make()
                                    ->title('Воркер уже запущен')
                                    ->body('Воркер уже работает и обрабатывает задания.')
                                    ->info()
                                    ->send();
                                return;
                            }

                            // Запускаем воркер в фоне
                            $result = self::startBackgroundWorker();

                            if ($result) {
                                Notification::make()
                                    ->title('Воркер запущен')
                                    ->body("Начинаем обработку {$jobCount} заданий. Это может занять несколько минут.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Не удалось запустить воркер')
                                    ->body('Попробуйте запустить вручную через SSH или проверьте настройки сервера.')
                                    ->danger()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            Log::error('Ошибка при запуске воркера: ' . $e->getMessage());

                            Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])

            ->filters([])
            ->bulkActions([])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }

    /**
     * Получить количество заданий в очереди parsers
     */
    private static function getJobCount(): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', 'parsers')
                ->count();
        } catch (\Exception $e) {
            Log::error('Ошибка при получении количества заданий: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Проверить, запущен ли воркер
     */
    private static function isWorkerRunning(): bool
    {
        // На shared-хостинге проверить процессы сложно,
        // поэтому будем считать, что воркер не запущен,
        // если только он не был запущен через наш метод
        if (cache()->has('queue_worker_started')) {
            // Проверяем, не истекло ли время (воркер мог завершиться)
            $startedAt = cache('queue_worker_started');
            if (now()->diffInMinutes($startedAt) < 5) {
                return true;
            }
        }

        return false;
    }

    /**
     * Запустить воркер в фоновом режиме
     */
    private static function startBackgroundWorker(): bool
    {
        try {
            // Сохраняем время запуска
            cache()->put('queue_worker_started', now(), now()->addMinutes(10));

            // Команда для запуска воркера
            $command = 'cd ' . base_path() . ' && /usr/local/bin/php8.3 artisan queue:work --queue=parsers --sleep=3 --stop-when-empty > /dev/null 2>&1 &';

            // Пытаемся запустить
            if (function_exists('exec')) {
                exec($command, $output, $returnCode);
                Log::info('Воркер запущен через exec', ['return_code' => $returnCode]);
                return $returnCode === 0;
            }

            // Альтернативный способ через shell_exec
            if (function_exists('shell_exec')) {
                shell_exec($command);
                Log::info('Воркер запущен через shell_exec');
                return true;
            }

            // Если не удалось, пытаемся через nohup
            $nohupCommand = 'nohup /usr/local/bin/php8.3 ' . base_path() . '/artisan queue:work --queue=parsers --sleep=3 --stop-when-empty > ' . storage_path('logs/worker.log') . ' 2>&1 &';

            if (function_exists('shell_exec')) {
                shell_exec($nohupCommand);
                Log::info('Воркер запущен через nohup');
                return true;
            }

            Log::warning('Не удалось запустить воркер: нет доступных функций');
            return false;

        } catch (\Exception $e) {
            Log::error('Ошибка при запуске воркера: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить статус очереди
     */
    private static function getQueueStatus(): string
    {
        $jobCount = self::getJobCount();

        if ($jobCount > 0) {
            return "{$jobCount} заданий в очереди";
        }

        if (self::isWorkerRunning()) {
            return 'Воркер работает';
        }

        return 'Очередь пуста';
    }
}
