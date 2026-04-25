<?php

namespace App\Exports;

use App\Models\Wallet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WalletExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Email',
            'Koperasi',
            'No Rekening',
            'Balance',
            'Created At',
        ];
    }

    public function map($wallet): array
    {
        return [
            $wallet->user->name,
            $wallet->user->email,
            $wallet->branch->name ?? '-',
            $wallet->account_number,
            $wallet->balance,
            $wallet->created_at,
        ];
    }
}
