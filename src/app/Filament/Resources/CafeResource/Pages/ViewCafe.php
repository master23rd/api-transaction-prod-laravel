<?php

namespace App\Filament\Resources\CafeResource\Pages;

use App\Filament\Resources\CafeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCafe extends ViewRecord
{
    protected static string $resource = CafeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
