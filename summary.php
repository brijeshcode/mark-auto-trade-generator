<?php
include 'header.php';
require_once 'Classes/Trades.php';

$f = ['currency_code' => ''];
foreach (['from_date', 'to_date', 'customer_code', 'type'] as $k) {
    $f[$k] = trim($_GET[$k] ?? '');
}
// Default "from" = the restriction window start; a submitted (even empty) value wins.
$days = (int)(new Settings($pdo))->get('restriction_days');
if (!isset($_GET['from_date']) && $days > 0) {
    $f['from_date'] = date('Y-m-d', strtotime("-$days days"));
}
$sizes = [10, 25, 50, 100, 200, 500];
$perPage = in_array((int)($_GET['per_page'] ?? 100), $sizes) ? (int)($_GET['per_page'] ?? 100) : 100;
$page = max(1, (int)($_GET['page'] ?? 1));

$tradesObj = new Trades($pdo);
[$rows, $total] = $tradesObj->summary($f, $page, $perPage);
$stats = $tradesObj->stats($f);
$pages = max(1, (int)ceil($total / $perPage));
$query = fn($p) => '?' . http_build_query(array_merge(array_diff_key($f, ['currency_code' => 1]), ['per_page' => $perPage, 'page' => $p]));
?>

<body class="container mx-auto">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item"><a class="nav-link" href="/">Data entry</a></li>
                <li class="nav-item"><a class="nav-link" href="/history.php">History</a></li>
                <li class="nav-item"><a class="nav-link active" href="#">Summary</a></li>
                <li class="nav-item"><a class="nav-link" href="/settings.php">Settings</a></li>
            </ul>
        </div>
        <div class="card-body">
            <form method="get" class="bg-light border rounded p-3 mb-3"><div class="row g-3">
                <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">From date</label>
                    <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($f['from_date']) ?>"></div>
                <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">To date</label>
                    <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($f['to_date']) ?>"></div>
                <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Customer code</label>
                    <input type="text" class="form-control" name="customer_code" value="<?= htmlspecialchars($f['customer_code']) ?>"></div>
                <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All</option>
                        <?php foreach (['Buy', 'Sell'] as $t): ?>
                            <option <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Page size</label>
                    <select class="form-select" name="per_page">
                        <?php foreach ($sizes as $n): ?>
                            <option <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                    <button class="btn btn-primary px-4">Filter</button>
                    <a class="btn btn-outline-secondary" href="summary.php">Reset</a>
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
            <p class="text-muted small">Total amount counts <code>amount</code> for currency code 2, otherwise <code>total</code>. Sorted by total amount, highest first.</p>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead><tr><th>Customer Code</th><th>Trades</th><th>Total Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><a href="history.php?<?= htmlspecialchars(http_build_query(array_merge($f, ['customer_code' => $r['customer_code']]))) ?>"><?= htmlspecialchars($r['customer_code']) ?></a></td>
                            <td><?= $r['trades'] ?></td>
                            <td><?= number_format($r['total_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="3" class="text-center text-muted">No trades found</td></tr><?php endif; ?>
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
