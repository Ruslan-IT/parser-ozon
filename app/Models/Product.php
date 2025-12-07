<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['title', 'url', 'price', 'delivery'];

    // Мутатор для автоматической очистки при установке цены
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = $this->cleanPrice($value);
    }

    // Аксессор для форматированного отображения
    public function getFormattedPriceAttribute()
    {
        if (empty($this->price) || $this->price === '—') {
            return '—';
        }

        return number_format($this->price, 0, '', ' ') . ' ₽';
    }

    private function cleanPrice($value)
    {
        if ($value === '—' || empty($value)) {
            return null;
        }

        // Если уже число
        if (is_numeric($value)) {
            return (int)$value;
        }

        // Очищаем строку
        $cleaned = preg_replace('/[^\d]/u', '', (string)$value);

        return $cleaned ? (int)$cleaned : null;
    }
}
