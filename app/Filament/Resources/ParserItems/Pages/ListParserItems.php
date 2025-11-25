<?php

namespace App\Filament\Resources\ParserItems\Pages;

use App\Filament\Resources\ParserItems\ParserItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParserItems extends ListRecords
{
    protected static string $resource = ParserItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
