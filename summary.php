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

<body>
<div class="app">
    <?php include 'nav.php'; ?>
    <main class="main">
        <header class="page-head">
            <h1>Summary</h1>
            <p>Trade count and total amount per customer. Select a customer to see their trades.</p>
        </header>

        <form method="get" class="panel">
            <div class="row g-3">
                <div class="col-6 col-md-3"><label class="form-label" for="f-from">From date</label>
                    <input id="f-from" type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($f['from_date']) ?>"></div>
                <div class="col-6 col-md-3"><label class="form-label" for="f-to">To date</label>
                    <input id="f-to" type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($f['to_date']) ?>"></div>
                <div class="col-6 col-md-2"><label class="form-label" for="f-cust">Customer code</label>
                    <input id="f-cust" type="text" class="form-control" name="customer_code" value="<?= htmlspecialchars($f['customer_code']) ?>"></div>
                <div class="col-6 col-md-2"><label class="form-label" for="f-type">Type</label>
                    <select id="f-type" class="form-select" name="type">
                        <option value="">All</option>
                        <?php foreach (['Buy', 'Sell'] as $t): ?>
                            <option <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-6 col-md-2"><label class="form-label" for="f-size">Page size</label>
                    <select id="f-size" class="form-select" name="per_page">
                        <?php foreach ($sizes as $n): ?>
                            <option <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div class="panel-actions">
                <button class="btn btn-primary px-4">Apply filters</button>
                <a class="btn btn-outline-dark" href="summary.php">Reset</a>
            </div>
        </form>

        <div class="stats">
            <?php foreach (['Total amount' => number_format($stats['total_amount'], 2), 'Customers' => number_format($stats['customers']), 'Trades' => number_format($stats['trades'])] as $label => $value): ?>
                <div class="stat"><div class="stat-label"><?= $label ?></div><div class="stat-value"><?= $value ?></div></div>
            <?php endforeach; ?>
        </div>
        <p class="stat-note">Total amount counts <code>amount</code> for currency code 2, otherwise <code>total</code>. Highest total first.</p>

        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr><th>Customer</th><th class="num">Trades</th><th class="num">Total amount</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="num text-start"><a href="history.php?<?= htmlspecialchars(http_build_query(array_merge($f, ['customer_code' => $r['customer_code']]))) ?>"><?= htmlspecialchars($r['customer_code']) ?></a></td>
                        <td class="num"><?= $r['trades'] ?></td>
                        <td class="num"><?= number_format($r['total_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="3" class="text-center text-muted py-4">No trades match these filters. Widen the dates or reset the filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav aria-label="Pages"><ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page - 1) ?>">Previous</a></li>
            <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $pages ?></span></li>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $query($page + 1) ?>">Next</a></li>
        </ul></nav>
    </main>
</div>
<?php include 'footer.php'; ?>
</body>
