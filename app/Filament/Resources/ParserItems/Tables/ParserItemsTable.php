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
                Action::make('runParsersNow')
                    ->label('▶ Запустить парсер сейчас')
                    ->color('danger')
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->action(function () {

                        $script = '/bin/bash /home/i/info90zj/info90zj.beget.tech/run_parsers_cron.sh';

                        $output = [];
                        $code = 0;

                        exec($script . ' 2>&1', $output, $code);

                        if ($code === 0) {
                            Notification::make()
                                ->title('Парсер запущен')
                                ->body('Скрипт успешно стартовал')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Ошибка запуска парсера')
                                ->body(implode("\n", $output))
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
        if (cache()->has('queue_worker_started')) {
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
            cache()->put('queue_worker_started', now(), now()->addMinutes(10));

            $command = 'cd ' . base_path() . ' && /usr/local/bin/php8.3 artisan queue:work --queue=parsers --sleep=3 --stop-when-empty > /dev/null 2>&1 &';

            if (function_exists('exec')) {
                exec($command, $output, $returnCode);
                Log::info('Воркер запущен через exec', ['return_code' => $returnCode]);
                return $returnCode === 0;
            }

            if (function_exists('shell_exec')) {
                shell_exec($command);
                Log::info('Воркер запущен через shell_exec');
                return true;
            }

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
