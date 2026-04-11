<?php

namespace App\Filament\Resources\OtpCodeResource\Pages;

use App\Filament\Resources\OtpCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtpCode extends CreateRecord
{
    protected static string $resource = OtpCodeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
