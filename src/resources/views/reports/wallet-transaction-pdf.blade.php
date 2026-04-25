<!DOCTYPE html>
<html>
<head>
    <title>Transaction Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>

<h2>Transaction Report</h2>

<table>
    <thead>
        <tr>
            <th>Customer</th>
            {{-- <th>Email</th> --}}
            <th>No. Rekening</th>
            <th>Koperasi</th>
            <th>Ref. Code </th>
            <th>Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $row)
        <tr>
            <td>{{ $row->wallet->user->name ?? '-' }}</td>
            {{-- <td>{{ $row->wallet->user->email ?? '-' }}</td> --}}
            <td>{{ $row->wallet->account_number ?? '-' }}</td>
            <td>{{ $row->branch->name ?? '-' }}</td>
            <td>{{ $row->reference_code ?? '-' }}</td>
            <td>{{ strtoupper($row->type) }}</td>
            <td>Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
            <td>{{ strtoupper($row->status) }}</td>
            <td>{{ $row->created_at }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>