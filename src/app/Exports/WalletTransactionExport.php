<?php

namespace App\Exports;

use App\Models\WalletTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WalletTransactionExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with(['wallet.user', 'branch']);
    }

    public function headings(): array
    {
        return [
            'Customer',
            // 'Email',
            'No Rekening',
            'Koperasi',
            'Ref. Code',
            'Type',
            'Amount',
            'Status',
            'Date',
        ];
    }

    public function map($row): array
    {
        return [
            $row->wallet->user->name ?? '-',
            // $row->wallet->user->email ?? '-',
            $row->wallet->account_number ?? '-',
            $row->branch->name ?? '-',
            $row->reference_number ?? '-',
            strtoupper($row->type),
            $row->total_amount,
            strtoupper($row->status),
            $row->created_at,
        ];
    }
}
