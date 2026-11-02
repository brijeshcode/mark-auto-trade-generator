<?php
ob_start(); // lets the CSV download discard the page header output
include 'header.php';
require_once 'Classes/Trades.php';

$f = [];
foreach (['from_date', 'to_date', 'customer_code', 'currency_code', 'type'] as $k) {
    $f[$k] = trim($_GET[$k] ?? '');
}
// Default "from" = the restriction window start; a submitted (even empty) value wins.
$days = (int)(new Settings($pdo))->get('restriction_days');
if (!isset($_GET['from_date']) && $days > 0) {
    $f['from_date'] = date('Y-m-d', strtotime("-$days days"));
}
$sizes =[10, 25, 50, 100, 200, 500];
$perPage = in_array((int)($_GET['per_page'] ?? 100), $sizes) ? (int)($_GET['per_page'] ?? 100) : 100;
$page = max(1, (int)($_GET['page'] ?? 1));

$tradesObj = new Trades($pdo);

if (isset($_GET['download'])) {
    ob_end_clean();
    $name = 'transactions_history_' . ($f['from_date'] ?: 'all') . ($f['to_date'] ? '_to_' . $f['to_date'] : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$name\"");
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Time', 'Customer Code', 'Buy', 'Currency Code', 'Cv Currency', 'Amount', 'Rate', 'Total']);
    foreach ($tradesObj->all($f) as $r) {
        fputcsv($out, [date('d-m-Y', strtotime($r['trade_date'])), substr($r['trade_time'], 0, 5), $r['customer_code'],
            $r['transaction_type'], $r['currency_code'], $r['cv_currency'], $r['amount'], $r['rate'], $r['total']]);
    }
    exit;
}

[$rows, $total] = $tradesObj->search($f, $page, $perPage);
$stats = $tradesObj->stats($f);
$pages = max(1, (int)ceil($total / $perPage));
$query = fn($p) => '?' . http_build_query(array_merge($f, ['per_page' => $perPage, 'page' => $p]));
?>

<body class="container mx-auto">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item"><a class="nav-link" href="/">Data entry</a></li>
                <li class="nav-item"><a class="nav-link active" href="#">History</a></li>
                <li class="nav-item"><a class="nav-link" href="/summary.php">Summary</a></li>
                <li class="nav-item"><a class="nav-link" href="/settings.php">Settings</a></li>
            </ul>
        </div>
        <div class="card-body">
            <form method="get" class="bg-light border rounded p-3 mb-3"><div class="row g-3">
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">From date</label>
                    <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($f['from_date']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">To date</label>
                    <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($f['to_date']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">Customer code</label>
                    <input type="text" class="form-control" name="customer_code" value="<?= htmlspecialchars($f['customer_code']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">Currency code</label>
                    <input type="text" class="form-control" name="currency_code" value="<?= htmlspecialchars($f['currency_code']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All</option>
                        <?php foreach (['Buy', 'Sell'] as $t): ?>
                            <option <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label small text-muted mb-1">Page size</label>
                    <select class="form-select" name="per_page">
                        <?php foreach ($sizes as $n): ?>
                            <option <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                    <button class="btn btn-primary px-4">Filter</button>
                    <a class="btn btn-outline-secondary" href="history.php">Reset</a>
                    <a class="btn btn-success ms-auto" href="<?= $query(1) ?>&download=1">Download CSV</a>
                </div>
            </form>

            <div class="row g-2 mb-3">
                <?php foreach (['Total amount' => number_format($stats['total_amount'], 2), 'Customers' => $stats['customers'], 'Trades' => $stats['trades']] as $label => $value): ?>
                    <div class="col"><div class="border rounded p-2 text-center">
                        <div class="text-muted small"><?= $label ?></div>
                        <div class="fs-4 fw-semibold"><?= $value ?></div>
                    </div></div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small">Total amount counts <code>amount</code> for currency code 2, otherwise <code>total</code>. Stats cover all filtered results, not just this page.</p>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead><tr>
                        <th>Date</th><th>Time</th><th>Customer Code</th><th>Buy</th><th>Currency Code</th>
                        <th>Cv Currency</th><th>Amount</th><th>Rate</th><th>Total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($r['trade_date'])) ?></td>
                            <td><?= substr($r['trade_time'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($r['customer_code']) ?></td>
                            <td><?= htmlspecialchars($r['transaction_type']) ?></td>
                            <td><?= htmlspecialchars($r['currency_code']) ?></td>
                            <td><?= htmlspecialchars($r['cv_currency']) ?></td>
                            <td><?= $r['amount'] ?></td>
                            <td><?= $r['rate'] ?></td>
                            <td><?= $r['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted">No trades found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav><ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page - 1) ?>">Previous</a></li>
                <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $pages ?></span></li>
                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page + 1) ?>">Next</a></li>
            </ul></nav>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
