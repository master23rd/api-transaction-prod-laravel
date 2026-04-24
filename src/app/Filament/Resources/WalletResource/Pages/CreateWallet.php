<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Wallet;


class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    protected function handleRecordCreation(array $data): Wallet
    {
        // ambil branches dulu
        $branches = $data['branches'] ?? [];
        unset($data['branches']);

        // create wallet
        $wallet = Wallet::create($data);

        // attach branch
        if (!empty($branches)) {
            $wallet->branches()->attach($branches);
        }

        return $wallet;
    }
}
