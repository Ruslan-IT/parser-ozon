<x-filament::page>

    <h2 class="text-xl font-bold mb-4">Парсер Ozon</h2>

    <button
        class="fi-btn fi-color-primary fi-size-md mb-4"
        wire:click="parse"
    >
        <span wire:loading.remove wire:target="parse">Запустить парсер</span>
        <span wire:loading wire:target="parse">Загрузка...</span>
    </button>

    @if(!empty($items))
        <pre class="bg-gray-100 p-4 rounded text-xs overflow-auto max-h-[600px]">
{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
        </pre>
    @endif

</x-filament::page>
