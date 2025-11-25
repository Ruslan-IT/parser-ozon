<x-filament-panels::page>
    <h2 class="text-xl font-bold mb-4">
        Тест Ozon API
    </h2>

    @if ($this->productInfo)
        <pre>{{ print_r($this->productInfo, true) }}</pre>
    @else
        <p>Нет данных</p>
    @endif
</x-filament-panels::page>
