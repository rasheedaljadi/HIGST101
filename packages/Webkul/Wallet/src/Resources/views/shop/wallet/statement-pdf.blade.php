<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HIGEST Wallet Statement</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #047857; padding-bottom: 10px; margin-bottom: 20px; }
        .summary-table, .txn-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-table td, .txn-table th, .txn-table td { border: 1px solid #ddd; padding: 8px; }
        .txn-table th { background-color: #f3f4f6; text-align: left; }
        .credit { color: #16a34a; font-weight: bold; }
        .debit { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>HIGEST WALLET STATEMENT</h2>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
    </div>

    <table class="summary-table">
        <tr>
            <td><strong>Customer:</strong> {{ auth()->guard('customer')->user()->name }}</td>
            <td><strong>Opening Balance:</strong> {{ core()->formatBasePrice($openingBalance) }}</td>
        </tr>
        <tr>
            <td><strong>Email:</strong> {{ auth()->guard('customer')->user()->email }}</td>
            <td><strong>Closing Balance:</strong> {{ core()->formatBasePrice($closingBalance) }}</td>
        </tr>
        <tr>
            <td><strong>Period Credits (+):</strong> {{ core()->formatBasePrice($periodCredits) }}</td>
            <td><strong>Period Debits (-):</strong> {{ core()->formatBasePrice($periodDebits) }}</td>
        </tr>
    </table>

    <h3>Transaction Detail</h3>
    <table class="txn-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Balance After</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $txn)
            <tr>
                <td>{{ $txn->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $txn->type_label }}</td>
                <td>{{ $txn->description }}</td>
                <td class="{{ $txn->isCredit() ? 'credit' : 'debit' }}">
                    {{ $txn->isCredit() ? '+' : '-' }}{{ core()->formatBasePrice($txn->amount) }}
                </td>
                <td>{{ core()->formatBasePrice($txn->running_balance) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
