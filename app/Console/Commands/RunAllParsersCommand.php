<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProductAlertController;
use App\Jobs\RunParserJob;
use App\Models\ParserItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class RunAllParsersCommand extends Command
{
    protected $signature = 'parsers:run-all';
    protected $description = 'Автоматический запуск парсера для всех моделей';

    public function handle()
    {
        Log::info('Автоматический запуск парсера для всех моделей');

        $items = ParserItem::all();

        if ($items->isEmpty()) {
            $this->info('Нет моделей для парсинга');
            Log::warning('Нет моделей для парсинга');
            return;
        }

        $jobs = $items->map(fn ($item) => new RunParserJob($item->id))->toArray();

        $batch = Bus::batch($jobs)
            ->name('Парсинг моделей (автоматический)')
            ->onQueue('parsers')
            ->then(function () {
                Log::info('Парсинг завершен, отправляем уведомления в Telegram');
                app(ProductAlertController::class)->sendAlerts();
            })
            ->catch(function ($batch, $e) {
                Log::error('Ошибка при автоматическом парсинге: ' . $e->getMessage());
            })
            ->dispatch();

        $this->info("Парсинг запущен. Batch ID: {$batch->id}, задач: {$batch->totalJobs}");
        Log::info("Автоматический парсинг запущен. Batch ID: {$batch->id}, задач: {$batch->totalJobs}");

        return 0;
    }
}
