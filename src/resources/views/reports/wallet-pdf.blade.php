<!DOCTYPE html>
<html>
    <head>
        <title>Wallet Report</title>
        <style>
            body { font-family: sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; }
            th { background: #f4f4f4; }
        </style>
    </head>
    <body>

    <h2>Wallet Report</h2>

    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Koperasi</th>
                <th>No Rekening</th>
                <th>Balance</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($wallets as $wallet)
                <tr>
                    <td>{{ $wallet->user->name }}</td>
                    <td>{{ $wallet->user->email }}</td>
                    <td>{{ $wallet->branch->name ?? '-' }}</td>
                    <td>{{ $wallet->account_number }}</td>
                    <td>Rp {{ number_format($wallet->balance, 0, ',', '.') }}</td>
                    <td>{{ $wallet->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    </body>
</html>