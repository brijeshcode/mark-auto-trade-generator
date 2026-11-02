<?php
// Sidebar shared by every page. Needs $pdo and Settings (both loaded by header.php).
$rule = (new Settings($pdo))->all();
$navPage = basename($_SERVER['SCRIPT_NAME']);
$links = ['index.php' => 'Generate trades', 'history.php' => 'History', 'summary.php' => 'Summary', 'settings.php' => 'Settings'];
$ruleLimit = (float)($rule['amount_limit'] ?? 0);
$ruleDays = (int)($rule['restriction_days'] ?? 0);
$short = fn($d) => $d ? date('d M', strtotime($d)) : '—';
?>
<aside class="sidebar">
    <a class="brand" href="/">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
            <rect width="28" height="28" rx="7" fill="#0b7a75"/>
            <path d="M7 10h13m0 0-3.5-3.5M20 10l-3.5 3.5M21 18H8m0 0 3.5-3.5M8 18l3.5 3.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="brand-name">Trade Desk</span>
    </a>

    <nav class="side-nav" aria-label="Main">
        <?php foreach ($links as $file => $label): ?>
            <a href="/<?= $file === 'index.php' ? '' : $file ?>" <?= $navPage === $file ? 'class="active" aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </nav>

    <section class="rules" aria-label="Active generation rules">
        <h2>Active rules</h2>
        <dl>
            <div><dt>Customer amount upper limit</dt>
                <dd class="<?= $ruleLimit > 0 && $ruleDays > 0 ? '' : 'off' ?>"><?= $ruleLimit > 0 && $ruleDays > 0 ? number_format($ruleLimit, 2) . ' / ' . $ruleDays . ' days' : 'Not set' ?></dd></div>
            <div><dt>Trade dates</dt><dd><?= $short($rule['date_from'] ?? '') ?> → <?= $short($rule['date_to'] ?? '') ?></dd></div>
            <div><dt>Customer codes</dt><dd><?= htmlspecialchars(($rule['customer_code_from'] ?? '—') . ' – ' . ($rule['customer_code_to'] ?? '—')) ?></dd></div>
        </dl>
        <a href="/settings.php">Change rules</a>
    </section>
</aside>
