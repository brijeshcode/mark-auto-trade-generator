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

if (!empty($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
    $n = $tradesObj->delete($_POST['delete_ids']);
    parse_str($_POST['return'] ?? '', $back);
    unset($back['deleted']);
    ob_end_clean();
    header('Location: history.php?' . http_build_query($back + ['deleted' => $n]));
    exit;
}

[$rows, $total] = $tradesObj->search($f, $page, $perPage);
$stats = $tradesObj->stats($f);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) { // e.g. the last page was just emptied by a delete
    $page = $pages;
    [$rows] = $tradesObj->search($f, $page, $perPage);
}
$query = fn($p) => '?' . http_build_query(array_merge($f, ['per_page' => $perPage, 'page' => $p]));
?>

<body>
<div class="app">
    <?php include 'nav.php'; ?>
    <main class="main">
        <header class="page-head">
            <h1>History</h1>
            <p>Every trade saved from a CSV download. Filter it, then download what you see.</p>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice" role="status"><?= (int)$_GET['deleted'] ?> trade<?= (int)$_GET['deleted'] === 1 ? '' : 's' ?> deleted.</div>
        <?php endif; ?>

        <form method="get" class="panel">
            <div class="row g-3">
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-from">From date</label>
                    <input id="f-from" type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($f['from_date']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-to">To date</label>
                    <input id="f-to" type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($f['to_date']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-cust">Customer code</label>
                    <input id="f-cust" type="text" class="form-control" name="customer_code" value="<?= htmlspecialchars($f['customer_code']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-cur">Currency code</label>
                    <input id="f-cur" type="text" class="form-control" name="currency_code" value="<?= htmlspecialchars($f['currency_code']) ?>"></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-type">Type</label>
                    <select id="f-type" class="form-select" name="type">
                        <option value="">All</option>
                        <?php foreach (['Buy', 'Sell'] as $t): ?>
                            <option <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="f-size">Page size</label>
                    <select id="f-size" class="form-select" name="per_page">
                        <?php foreach ($sizes as $n): ?>
                            <option <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div class="panel-actions">
                <button class="btn btn-primary px-4">Apply filters</button>
                <a class="btn btn-outline-dark" href="history.php">Reset</a>
                <a class="btn btn-outline-dark ms-auto" href="<?= $query(1) ?>&download=1">Download CSV</a>
            </div>
        </form>

        <div class="stats">
            <?php foreach (['Total amount' => number_format($stats['total_amount'], 2), 'Customers' => number_format($stats['customers']), 'Trades' => number_format($stats['trades'])] as $label => $value): ?>
                <div class="stat"><div class="stat-label"><?= $label ?></div><div class="stat-value"><?= $value ?></div></div>
            <?php endforeach; ?>
        </div>
        <p class="stat-note">Total amount counts <code>amount</code> for currency code 2, otherwise <code>total</code>. Stats cover every filtered trade, not just this page.</p>

        <form method="post" id="del-form">
        <input type="hidden" name="return" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">
        <div class="d-flex justify-content-end mb-2">
            <button type="submit" class="btn btn-outline-danger btn-sm" id="del-btn" disabled>Delete selected</button>
        </div>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr>
                    <th><input type="checkbox" class="form-check-input" id="check-all" aria-label="Select all trades on this page"></th>
                    <th>Date</th><th>Time</th><th>Customer</th><th>Type</th><th>Currency</th>
                    <th>Cv currency</th><th class="num">Amount</th><th class="num">Rate</th><th class="num">Total</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input row-check" name="delete_ids[]" value="<?= (int)$r['id'] ?>" aria-label="Select trade"></td>
                        <td><?= date('d-m-Y', strtotime($r['trade_date'])) ?></td>
                        <td class="num text-start"><?= substr($r['trade_time'], 0, 5) ?></td>
                        <td class="num text-start"><?= htmlspecialchars($r['customer_code']) ?></td>
                        <td><span class="tag tag-<?= strtolower($r['transaction_type']) ?>"><?= htmlspecialchars($r['transaction_type']) ?></span></td>
                        <td><?= htmlspecialchars($r['currency_code']) ?></td>
                        <td><?= htmlspecialchars($r['cv_currency']) ?></td>
                        <td class="num"><?= $r['amount'] ?></td>
                        <td class="num"><?= $r['rate'] ?></td>
                        <td class="num"><?= $r['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">No trades match these filters. Widen the dates or reset the filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>

        <nav aria-label="Pages"><ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page - 1) ?>">Previous</a></li>
            <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $pages ?></span></li>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page + 1) ?>">Next</a></li>
        </ul></nav>
    </main>
</div>
<script>
    const delForm = document.getElementById('del-form');
    const checks = () => [...delForm.querySelectorAll('.row-check')];
    function syncSelection() {
        const n = checks().filter(c => c.checked).length;
        const btn = document.getElementById('del-btn');
        btn.disabled = n === 0;
        btn.textContent = n ? 'Delete selected (' + n + ')' : 'Delete selected';
        document.getElementById('check-all').checked = n > 0 && n === checks().length;
    }
    document.getElementById('check-all').addEventListener('change', e => {
        checks().forEach(c => c.checked = e.target.checked);
        syncSelection();
    });
    delForm.addEventListener('change', e => e.target.classList.contains('row-check') && syncSelection());
    delForm.addEventListener('submit', e => {
        const n = checks().filter(c => c.checked).length;
        if (!confirm('Delete ' + n + ' selected trade' + (n === 1 ? '' : 's') + '? This cannot be undone.')) e.preventDefault();
    });
</script>
<?php include 'footer.php'; ?>
</body>
