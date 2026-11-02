<?php include 'header.php'; ?>
<?php include 'SettingsController.php'; ?>

<body>
<div class="app">
    <?php include 'nav.php'; ?>
    <main class="main">
        <header class="page-head">
            <h1>Settings</h1>
            <p>The rules every generation follows: trade dates and times, customer codes, the per-customer limit and your currencies.</p>
        </header>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="notice" role="status">Settings saved.</div>
        <?php endif; ?>

        <form action="/settings.php" method="post">
            <section class="panel">
                <h2 class="panel-title">Trade window</h2>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><label class="form-label" for="date_from">Date from</label>
                        <input id="date_from" type="date" class="form-control" name="date_from" value="<?= $existingSettings['date_from'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="date_to">Date to</label>
                        <input id="date_to" type="date" class="form-control" name="date_to" value="<?= $existingSettings['date_to'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="time_from">Time from</label>
                        <input id="time_from" type="time" class="form-control" name="time_from" value="<?= $existingSettings['time_from'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="time_to">Time to</label>
                        <input id="time_to" type="time" class="form-control" name="time_to" value="<?= $existingSettings['time_to'] ?? ''; ?>"></div>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">Customers</h2>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><label class="form-label" for="customer_code_from">Customer code from</label>
                        <input id="customer_code_from" type="number" class="form-control" name="customer_code_from" value="<?= $existingSettings['customer_code_from'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="customer_code_to">Customer code to</label>
                        <input id="customer_code_to" type="number" class="form-control" name="customer_code_to" value="<?= $existingSettings['customer_code_to'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="amount_limit">Customer amount upper limit</label>
                        <input id="amount_limit" type="number" min="0" step="0.01" class="form-control" name="amount_limit" value="<?= $existingSettings['amount_limit'] ?? ''; ?>"></div>
                    <div class="col-6 col-md-3"><label class="form-label" for="restriction_days">Look-back period (days)</label>
                        <input id="restriction_days" type="number" min="1" class="form-control" name="restriction_days" value="<?= $existingSettings['restriction_days'] ?? ''; ?>"></div>
                </div>
                <p class="hint mt-3 mb-0">A customer cannot trade more than this total amount, buys and sells combined, within the look-back period. Set the amount to 0 for no limit.</p>
            </section>

            <section class="panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="panel-title mb-0">Currencies</h2>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="appendRow()">Add currency</button>
                </div>
                <div class="table-wrap">
                    <table class="table settings-table">
                        <thead>
                            <tr>
                                <th scope="col" class="px-2 py-2"><abbr title="currency code">Curr Code</abbr></th>
                                <th scope="col" class="px-2 py-2">Name</th>
                                <th scope="col" class="px-2 py-2">Buy code</th>
                                <th scope="col" class="px-2 py-2">Sell code</th>
                                <th scope="col" class="px-2 py-2">Cal. type</th>
                                <th scope="col" class="px-2 py-2">Buy from</th>
                                <th scope="col" class="px-2 py-2">Buy to</th>
                                <th scope="col" class="px-2 py-2">Sell from</th>
                                <th scope="col" class="px-2 py-2">Sell to</th>
                                <th scope="col" class="px-2 py-2">Interval</th>
                                <th scope="col" class="px-2 py-2">CV curr.</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            if(isset($existingSettings['currency']) && !empty($existingSettings['currency'])) {
                                $currencies = json_decode($existingSettings['currency'], true);
                                foreach ($currencies as $index => $currency) {
                                    echo "<tr id='currency_row_" . ($index + 1) . "'>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][currency_code]' type='text' value='" . htmlspecialchars($currency['currency_code']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][currency_name]' type='text' value='" . htmlspecialchars($currency['currency_name']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][buy_code]' type='text' value='" . htmlspecialchars($currency['buy_code']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][sell_code]' type='text' value='" . htmlspecialchars($currency['sell_code']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' list='datalistOptions' name='currency[" . ($index + 1) . "][calculation_type]' value='" . htmlspecialchars($currency['calculation_type']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][buy_from]' type='text' value='" . htmlspecialchars($currency['buy_from']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][buy_to]' type='text' value='" . htmlspecialchars($currency['buy_to']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][sell_from]' type='text' value='" . htmlspecialchars($currency['sell_from']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][sell_to]' type='text' value='" . htmlspecialchars($currency['sell_to']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][interval]' type='text' value='" . htmlspecialchars($currency['interval']) . "'></td>";
                                    echo "<td><input class='form-control form-control-sm' name='currency[" . ($index + 1) . "][cv_currency]' type='text' value='" . htmlspecialchars($currency['cv_currency']) . "'></td>";
                                    echo "<td><span class='btn-close' aria-label='Close' onclick='removeRow(" . ($index + 1) . ")'></span></td>";
                                    echo "</tr>";
                                }

                            } else {
                            ?>

                            <tr id="currency_row_1">
                                <td><input class="form-control form-control-sm" name="currency[1][currency_code]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][currency_name]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][buy_code]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][sell_code]" type="text" ></td>
                                <td>
                                    <input class="form-control form-control-sm" list="datalistOptions" name="currency[1][calculation_type]" id="exampleDataList" >
                                    <datalist id="datalistOptions">
                                    <option value="Multiplication">
                                    <option value="Division">
                                    </datalist>
                                </td>
                                <td><input class="form-control form-control-sm" name="currency[1][buy_from]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][buy_to]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][sell_from]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][sell_to]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][interval]" type="text" ></td>
                                <td><input class="form-control form-control-sm" name="currency[1][cv_currency]" type="text" ></td>
                                <td>
                                    <!-- <button type="button" class="btn-close" aria-label="Close"></button> -->
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="currency_count" name="currency_count" value="<?= $currencyCount; ?>">
            </section>

            <button type="submit" class="btn btn-primary px-4" name="save_settings">Save settings</button>
        </form>
    </main>
</div>


<script>

    function appendRow() {
        let currencyCount = document.getElementById('currency_count');
        let count = parseInt(currencyCount.value) + 1;
        const table = document.querySelector('table tbody');
        const newRow = document.createElement('tr');
        newRow.id = `currency_row_${count}`;
        // Create a new row with inputs for the new currency

       newRow.innerHTML = `
            <td><input class="form-control form-control-sm" name="currency[${count}][currency_code]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][currency_name]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][buy_code]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][sell_code]" type="text" ></td>
            <td>
                <input class="form-control form-control-sm" list="datalistOptions" name="currency[${count}][calculation_type]" id="exampleDataList">
                <datalist id="datalistOptions">
                    <option value="Multiplication">
                    <option value="Division">
                </datalist>
            </td>
            <td><input class="form-control form-control-sm" name="currency[${count}][buy_from]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][buy_to]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][sell_from]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][sell_to]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][interval]" type="text" ></td>
            <td><input class="form-control form-control-sm" name="currency[${count}][cv_currency]" type="text" ></td>
            <td>
                <span class='btn-close' aria-label='Close' onclick="removeRow(${count})"></span>
            </td>`;

        table.appendChild(newRow);
        currencyCount.value = count;

    }

    function removeRow(count) {
        const row = document.getElementById(`currency_row_${count}`);
        if (row) {
            row.remove();
            
        }
    }
</script>

    <?php include 'footer.php'; ?>

 </body>