<x-filament-panels::page>
    <h2 class="text-xl font-bold mb-4">Запуск парсера смартфонов</h2>

    <button wire:click="runParser" class="fi-btn fi-btn-primary mb-4">
        Запустить парсер
    </button>

    @if(!empty($results))
        <table class="table-auto w-full border">
            <thead>
            <tr>
                <th class="border px-2 py-1">Название</th>
                <th class="border px-2 py-1">Цена</th>
                <th class="border px-2 py-1">Ссылка</th>
            </tr>
            </thead>
            <tbody>
            @foreach($results as $item)
                <tr>
                    <td class="border px-2 py-1">{{ $item['name'] }}</td>
                    <td class="border px-2 py-1">{{ $item['price'] }}</td>
                    <td class="border px-2 py-1">
                        @if($item['link'])
                            <a href="{{ $item['link'] }}" target="_blank">Перейти</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>Нет данных</p>
    @endif
</x-filament-panels::page>
