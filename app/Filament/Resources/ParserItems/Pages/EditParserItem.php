<?php

namespace App\Filament\Resources\ParserItems\Pages;

use App\Filament\Resources\ParserItems\ParserItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditParserItem extends EditRecord
{
    protected static string $resource = ParserItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
