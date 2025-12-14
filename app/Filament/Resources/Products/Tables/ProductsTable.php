<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;


class ProductsTable
{
    protected static bool $sent = false;

    public static function configure(Table $table): Table
    {
        //self::checkAndSendPrices();

        return $table
            ->columns([
                TextColumn::make('price')
                    ->label('Цена')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('delivery')
                    ->label('Доставка')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Название')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('min_price')
                    ->label('Минимальная цена')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Дата / время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->toolbarActions([]);
    }

    protected static function checkAndSendPrices()
    {
        if (session()->has('products_sent')) {
            return;
        }

        session()->put('products_sent', true);

        $token = env('TELEGRAM_BOT_TOKEN');
        $chatIds = [env('TELEGRAM_CHAT_ID'), 955149250];

        Product::chunk(50, function ($products) use ($token, $chatIds) {
            foreach ($products as $product) {
                if ($product->price >= $product->min_price) continue;

                $text = "📉 *Цена снизилась!*\n"
                    . "Название: {$product->title}\n"
                    . "Цена: {$product->price}\n"
                    . "Мин. цена: {$product->min_price}\n"
                    . "Ссылка: {$product->url}";

                foreach ($chatIds as $chatId) {
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'Markdown',
                    ]);
                }
            }
        });
    }
}
