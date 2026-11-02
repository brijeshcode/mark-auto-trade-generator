<?php
    include 'header.php'; 
    include 'DataEntryController.php';
    $dateFrom = new DateTime($settings['date_from']);
    $dateTo = new DateTime($settings['date_to']);
    $dayCount = $dateFrom->diff($dateTo)->days + 1; // inclusive
 ?>

<body>
<div class="app">
    <?php include 'nav.php'; ?>
    <main class="main">
        <header class="page-head">
            <h1>Generate trades</h1>
            <p>Enter what each currency should trade, then generate a preview and download it as CSV.</p>
        </header>

        <div class="notice">
            Trade dates <strong><?= $dateFrom->format('d-m-Y') ?></strong> to <strong><?= $dateTo->format('d-m-Y') ?></strong>
            &middot; <strong><?= $dayCount ?></strong> day<?= $dayCount == 1 ? '' : 's' ?>
        </div>
        <div id="errorsDisplay"></div>

        <form method="post" action="DataEntryController.php" class="panel">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="random_trades" onchange="applyRandomMode()">
                <label class="form-check-label" for="random_trades">Random trades (spread randomly across days)</label>
            </div>

            <input type="hidden" id="entry_count" value="<?= count($entries) ?>">
            <div class="table-wrap">
                <table class="table entry-table">
                    <thead>
                        <tr>
                            <th scope="col">Currency code</th>
                            <th scope="col">Total amount</th>
                            <th scope="col">Rate</th>
                            <th scope="col" class="per-day-col">Trades per day</th>
                            <th scope="col">Total trades</th>
                            <th scope="col" class="text-end">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="appendRow()">Add row</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($entries as $key => $entry): ?>
                            <tr id="entry-<?php echo $key + 1; ?>">
                                <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][currency_code]" value="<?php echo htmlspecialchars($entry['currency_code']); ?>"></td>
                                <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][total_amount]" value="<?php echo htmlspecialchars($entry['total_amount']); ?>"></td>
                                <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][rate]" value="<?php echo htmlspecialchars($entry['rate']); ?>"></td>
                                <td class="per-day-col"><input type="number" min="1" class="form-control per-day-input" oninput="onPerDayInput(this)"></td>
                                <td><input type="text" class="form-control total-trades-input" name="entry[<?php echo $key + 1; ?>][total_trades]" value="<?php echo htmlspecialchars($entry['total_trades']); ?>"></td>
                                <td class="text-end">
                                    <button type="button" class="btn-close" aria-label="Remove row" onclick="removeRow(<?php echo $key + 1; ?>)"></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel-actions">
                <button type="button" class="btn btn-primary" onclick="generateTradeCsv()">Generate trades</button>
                <button type="submit" name="save_trades" class="btn btn-outline-dark">Save entries</button>
                <span class="hint">Save entries keeps these rows for next time. Trades are saved when you download the CSV.</span>
            </div>
        </form>

        <section class="d-none" id="previewContainer">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="panel-title mb-0">Preview</h2>
                <button type="button" class="btn btn-primary" onclick="downloadCSV()">Download CSV</button>
            </div>
            <div class="table-wrap" id="previewTable"></div>
        </section>
    </main>
</div>

    <?php include 'footer.php'; ?>

    <script>
        var settings = {
            from_date : '<?= $settings['date_from']; ?>', 
            to_date : '<?= $settings['date_to']; ?>', 
            from_time : '<?= $settings['time_from']; ?>', 
            to_time : '<?= $settings['time_to']; ?>', 
            from_customer_code : '<?= $settings['customer_code_from']; ?>', 
            to_customer_code : '<?= $settings['customer_code_to']; ?>', 
            currency : <?= $settings['currency']; ?>,
            amount_limit : <?= (float)($settings['amount_limit'] ?? 0); ?>,
            restriction_days : <?= (int)($settings['restriction_days'] ?? 0); ?>,
            used_amounts : <?= json_encode((object)$usedAmounts); ?>,
        };
        var errors = [];
        var dataEntry = <?= json_encode($entries); ?>;

        const columnOrder = [
            "date",
            "time",
            "customer_code",
            "buy",
            "currency_code",
            "cv_currency",
            "amount",
            "rate",
            "total"
        ];

        // Map keys to custom display names
        const columnHeaders = {
            date: "Date",
            time: "Time",
            customer_code: "Customer Code",
            buy: "Buy",
            currency_code: "Currency Code",
            cv_currency: "Cv Currency",
            amount: "Amount",
            rate: "Rate",
            total: "Total"
        };
        
        var finalTransactions = [];
        var batchId = null;
        // console.log(settings, dataEntry);
         
        function appendRow() {
            const entryCount = document.getElementById('entry_count');
            const count = parseInt(entryCount.value) + 1;
            entryCount.value = count;

            const tbody = document.querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.id = `entry-${count}`;
            newRow.innerHTML = `
                <td><input type="text" class="form-control" name="entry[${count}][currency_code]"></td>
                <td><input type="text" class="form-control" name="entry[${count}][total_amount]"></td>
                <td><input type="text" class="form-control" name="entry[${count}][rate]"></td>
                <td class="per-day-col"><input type="number" min="1" class="form-control per-day-input" oninput="onPerDayInput(this)"></td>
                <td><input type="text" class="form-control total-trades-input" name="entry[${count}][total_trades]"></td>
                <td><button type="button" class="btn-close" aria-label="Close" onclick="removeRow(${count})"></button></td>
            `;
            tbody.appendChild(newRow);
            applyRandomMode();
        }

        function removeRow(count) {
            const row = document.getElementById(`entry-${count}`);
            if (row) {
                row.remove();
            }
        }

        // Number of days in the settings date range (inclusive). 0 if invalid/unset.
        function getDayCount() {
            if (!settings.from_date || !settings.to_date) return 0;
            const startDate = new Date(settings.from_date);
            const endDate = new Date(settings.to_date);
            const dayDiff = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            return dayDiff > 0 ? dayDiff : 0;
        }

        // Fixed mode: typing trades-per-day recomputes that row's total trades.
        function onPerDayInput(input) {
            const row = input.closest('tr');
            const totalInput = row.querySelector('.total-trades-input');
            const days = getDayCount();
            const perDay = parseInt(input.value) || 0;
            totalInput.value = (perDay >= 1 && days > 0) ? perDay * days : '';
        }

        // Sync a single row's per-day <-> total for fixed mode.
        function initFixedRow(row) {
            const days = getDayCount();
            const perDayInput = row.querySelector('.per-day-input');
            const totalInput = row.querySelector('.total-trades-input');
            if (!perDayInput || !totalInput) return;

            let perDay = parseInt(perDayInput.value) || 0;
            const total = parseInt(totalInput.value) || 0;

            // Derive per-day from an existing (saved) total when per-day is empty.
            if (perDay < 1 && total > 0 && days > 0) {
                perDay = Math.max(1, Math.round(total / days));
                perDayInput.value = perDay;
            }
            if (perDay >= 1 && days > 0) {
                totalInput.value = perDay * days;
            }
        }

        // Apply the Random checkbox state to the whole form.
        function applyRandomMode() {
            const isRandom = document.getElementById('random_trades').checked;

            // Show/hide the Trades Per Day column.
            document.querySelectorAll('.per-day-col').forEach(el => {
                el.style.display = isRandom ? 'none' : '';
            });

            // Total Trades is editable in random mode, computed (read-only) in fixed mode.
            document.querySelectorAll('.total-trades-input').forEach(inp => {
                if (isRandom) {
                    inp.removeAttribute('readonly');
                } else {
                    inp.setAttribute('readonly', 'readonly');
                }
            });

            if (!isRandom) {
                document.querySelectorAll('tbody tr').forEach(row => initFixedRow(row));
            }
        }


        function generateTradeCsv(){
            errors = [];
            const isRandom = document.getElementById('random_trades').checked;
            let trades = [];
            dataEntry.forEach(entry => {
                let tradesForEntry = [];
                let currencySetting = getCurrencySettings(entry.currency_code);
                
                if (!currencySetting) { 
                    errors.push(`No currency setting found for ${entry.currency_code}`);
                    displayErrors();
                    console.error(`No currency setting found for ${entry.currency_code}`);
                    return;
                }
 
                let tradeAmounts = generateIntervalBasedAmounts(entry.total_amount, entry.total_trades, currencySetting.interval);
                
                tradeAmounts.forEach(amount => {
                    let transactionType = entry.currency_code === currencySetting.buy_code ? 'Buy' : 'Sell';
                    let rate = validateRate(currencySetting, entry.rate, transactionType) ? entry.rate : 0;
                    let total = currencySetting.calculation_type.toLowerCase() === 'multiplication' ?  rate * amount : amount / rate ;
                    console.log(currencySetting.calculation_type.toLowerCase() === 'multiplication', currencySetting.calculation_type.toLowerCase());
                    let trade = {
                        currency_code: currencySetting.currency_code,
                        type: currencySetting.calculation_type.toLowerCase(),
                        cv_currency: currencySetting.cv_currency,
                        buy: transactionType,
                        amount: amount,
                        rate: rate,
                        total: formatNumber(total),
                        // total: total,
                    };

                    tradesForEntry.push(trade);
                });

                // Fixed mode: give each day this row's per-day count (even split).
                if (!isRandom) {
                    tradesForEntry = assignDatesEvenly(tradesForEntry, settings.from_date, settings.to_date);
                }

                trades.push(...tradesForEntry);
            });

            trades = shuffleArray(trades);
            trades = assignTimes(trades, settings.from_time, settings.to_time);
            if (isRandom) {
                trades = assignDates(trades, settings.from_date, settings.to_date);
            }
            trades = assignCustomerCodes(trades, settings.from_customer_code, settings.to_customer_code);

            document.getElementById("previewContainer").classList.remove("d-none");
            console.log(trades);
            renderTable(trades);

            finalTransactions = trades; // Store final transactions for CSV download
            batchId = Date.now() + '-' + Math.random().toString(36).slice(2); // one id per generation so re-downloads aren't saved twice

            displayErrors(); // Display any errors encountered during processing
        }

        function formatNumber(num) {
            const fixed = num.toFixed(2);
            return fixed.endsWith('.00') ? fixed.slice(0, -3) : fixed;
        }

        function assignCustomerCodes(transactions, customerCodeFrom, customerCodeTo) {
            const totalAvailable = customerCodeTo - customerCodeFrom + 1;

            if (totalAvailable < transactions.length) {
                errors.push("Customer code range is too small for the number of transactions.");
                displayErrors();
                throw new Error("Customer code range is too small for the number of transactions.");
            }

            // Generate all possible codes
            let availableCodes = [];
            for (let i = customerCodeFrom; i <= customerCodeTo; i++) {
                availableCodes.push(i);
            }

            // Shuffle codes
            for (let i = availableCodes.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [availableCodes[i], availableCodes[j]] = [availableCodes[j], availableCodes[i]];
            }

            const limited = settings.amount_limit > 0 && settings.restriction_days > 0;
            if (!limited) {
                transactions.forEach((tx, index) => {
                    tx.customer_code = availableCodes[index];
                });
                return transactions;
            }

            // Biggest trades pick first so they get the customers with the most room left.
            // ponytail: greedy, not an optimal matching; can report "no customer" where a perfect packing exists.
            const counted = tx => parseFloat(tx.currency_code) === 2 ? parseFloat(tx.amount) : parseFloat(tx.total);
            const order = transactions.map((_, i) => i).sort((a, b) => counted(transactions[b]) - counted(transactions[a]));

            for (const i of order) {
                const tx = transactions[i];
                const value = counted(tx);
                const pick = availableCodes.findIndex(code => value <= settings.amount_limit - (parseFloat(settings.used_amounts[code]) || 0));
                if (pick === -1) {
                    const msg = `No customer is available in range ${customerCodeFrom}-${customerCodeTo} for the limit ${settings.amount_limit} over the last ${settings.restriction_days} days (${transactions.length} trades to place, a ${tx.buy} of ${value} could not be assigned).`;
                    errors.push(msg);
                    displayErrors();
                    throw new Error(msg);
                }
                tx.customer_code = availableCodes.splice(pick, 1)[0];
            }

            return transactions;
        }

        function assignTimes(transactions, startTimeStr, endTimeStr) {
            const start = new Date(`1970-01-01T${startTimeStr}:00`);
            const end = new Date(`1970-01-01T${endTimeStr}:00`);
            const totalMinutes = Math.floor((end - start) / 60000);

            if (transactions.length > totalMinutes) {
                errors.push("Not enough time slots for 1-minute gaps.");
                displayErrors();
                throw new Error("Not enough time slots for 1-minute gaps.");
            }

            // Generate all available 1-minute slots
            const availableMinutes = Array.from({ length: totalMinutes }, (_, i) => i);

            // Shuffle the available minutes
            for (let i = availableMinutes.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [availableMinutes[i], availableMinutes[j]] = [availableMinutes[j], availableMinutes[i]];
            }

            // Assign random time slots to each transaction
            for (let i = 0; i < transactions.length; i++) {
                const minuteOffset = availableMinutes[i];
                const time = new Date(start.getTime() + minuteOffset * 60000);
                transactions[i].time = time.toTimeString().substring(0, 5); // Format "HH:MM"
            }

            return transactions;
        }

        function assignDates(transactions, startDateStr, endDateStr) {
            const startDate = new Date(startDateStr);
            const endDate = new Date(endDateStr);
            const dayDiff = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            const total = transactions.length;

            if (dayDiff <= 0) {
                errors.push("Invalid date range");
                displayErrors();
                throw new Error("Invalid date range");
            }

            if (total < dayDiff) {
                errors.push(`Not enough trades (${total}) to give each of the ${dayDiff} days at least 1 trade. Increase total trades or reduce the date range.`);
                displayErrors();
                throw new Error("Not enough trades for all days.");
            }

            const basePerDay = total / dayDiff;
            const maxPerDay = Math.ceil(basePerDay * 1.25); // allow up to 25% above base

            // Give every day at least 1 trade, then distribute the rest
            let dayCounts = Array(dayDiff).fill(1);
            let remaining = total - dayDiff;

            let attempts = 0;
            const maxAttempts = total * 200;
            while (remaining > 0 && attempts < maxAttempts) {
                const randomDay = Math.floor(Math.random() * dayDiff);
                if (dayCounts[randomDay] < maxPerDay) {
                    dayCounts[randomDay]++;
                    remaining--;
                }
                attempts++;
            }

            // Fallback: force-place any leftovers if maxPerDay was too tight
            let dayIdx = 0;
            while (remaining > 0) {
                dayCounts[dayIdx % dayDiff]++;
                remaining--;
                dayIdx++;
            }

            // Shuffle dayCounts so that "heavier" days are not always the first ones
            for (let i = dayCounts.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [dayCounts[i], dayCounts[j]] = [dayCounts[j], dayCounts[i]];
            }

            // Assign dates to transactions in groups
            let txIndex = 0;
            for (let d = 0; d < dayDiff; d++) {
                const date = new Date(startDate);
                date.setDate(date.getDate() + d);
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                const dateStr = `${dd}-${mm}-${yyyy}`;

                for (let i = 0; i < dayCounts[d]; i++) {
                    transactions[txIndex].date = dateStr;
                    txIndex++;
                }
            }

            return transactions;
        }

        // Fixed mode: distribute a row's trades evenly across all days.
        // Each day gets floor(total/days); the remainder is spread one-per-day.
        function assignDatesEvenly(transactions, startDateStr, endDateStr) {
            const startDate = new Date(startDateStr);
            const endDate = new Date(endDateStr);
            const dayDiff = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            if (dayDiff <= 0) {
                errors.push("Invalid date range");
                displayErrors();
                throw new Error("Invalid date range");
            }

            const total = transactions.length;
            const base = Math.floor(total / dayDiff);
            const remainder = total % dayDiff;

            // base per day, plus one extra for the first `remainder` days
            let dayCounts = Array.from({ length: dayDiff }, (_, d) => base + (d < remainder ? 1 : 0));

            // Shuffle so the "+1" days are not always the first ones
            for (let i = dayCounts.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [dayCounts[i], dayCounts[j]] = [dayCounts[j], dayCounts[i]];
            }

            let txIndex = 0;
            for (let d = 0; d < dayDiff; d++) {
                const date = new Date(startDate);
                date.setDate(date.getDate() + d);
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                const dateStr = `${dd}-${mm}-${yyyy}`;

                for (let i = 0; i < dayCounts[d] && txIndex < transactions.length; i++) {
                    transactions[txIndex].date = dateStr;
                    txIndex++;
                }
            }

            return transactions;
        }


        function shuffleArray(array) {
            return array.sort(() => Math.random() - 0.5);
        }

        function validateRate(setting, rate, transactionType)
        {
            if (transactionType === 'Buy') {
                return rate >= setting.buy_from && rate <= setting.buy_to;
            } else if (transactionType === 'Sell') {
                return rate >= setting.sell_from && rate <= setting.sell_to;
            }
            return false;
        }
        
        function generateIntervalBasedAmounts(totalAmount, transactionCount, interval) {
            const totalUnits = totalAmount / interval;

            if (!Number.isInteger(totalUnits)) {
                errors.push("Total amount must be divisible by interval.");
                displayErrors();
                throw new Error("Total amount must be divisible by interval.");
            }

            if (transactionCount > totalUnits) {
                errors.push("Transaction count too high for the given total and interval.");
                displayErrors();
                throw new Error("Transaction count too high for the given total and interval.");
            }

            // Step 1: Ensure each unit gets at least 1
            let units = Array(transactionCount).fill(1);
            let remainingUnits = totalUnits - transactionCount;

            // Step 2: Distribute remaining units randomly
            while (remainingUnits > 0) {
                const randomIndex = Math.floor(Math.random() * transactionCount);
                units[randomIndex]++;
                remainingUnits--;
            }

            // Step 3: Convert units to amounts
            return units.map(u => u * interval);
        }


        function getCurrencySettings(currencyCode){
            const settingsArray = Object.values(settings.currency);
            return settingsArray.find(c => c.buy_code === currencyCode || c.sell_code === currencyCode);

        } 


        function renderTable1(data) {
            if (!data || data.length === 0) {
                document.getElementById("previewTable").innerHTML = "<p>No data available.</p>";
                return;
            }

            const headers = Object.keys(data[0]);
            let table = `<table class="table table-bordered table-hover table-sm align-middle">
                <thead class="table-light">
                <tr>${headers.map(h => `<th>${h.replace(/_/g, " ").toUpperCase()}</th>`).join("")}</tr>
                </thead>
                <tbody>
                ${data.map(row => `
                    <tr>${headers.map(key => `<td>${row[key]}</td>`).join("")}</tr>
                `).join("")}
                </tbody>
            </table>`;

            document.getElementById("previewTable").innerHTML = table;
        }

        function renderTable(data) {
            if (!data || data.length === 0) {
                document.getElementById("previewTable").innerHTML = "<p class='p-3 mb-0 text-muted'>No trades to show. Check the entries and settings, then generate again.</p>";
                return;
            }

            const numeric = ["amount", "rate", "total"];
            const cell = (key, value) => key === "buy"
                ? `<td><span class="tag tag-${String(value).toLowerCase()}">${value}</span></td>`
                : `<td${numeric.includes(key) ? ' class="num"' : ''}>${value}</td>`;

            let table = `<table class="table table-hover align-middle">
                <thead>
                <tr>${columnOrder.map(h => `<th${numeric.includes(h) ? ' class="num"' : ''}>${columnHeaders[h] || h}</th>`).join("")}</tr>
                </thead>
                <tbody>
                ${data.map(row => `
                    <tr>${columnOrder.map(key => cell(key, row[key])).join("")}</tr>
                `).join("")}
                </tbody>
            </table>`;

            document.getElementById("previewTable").innerHTML = table;
        }
        
        // CSV download using columnOrder
        async function downloadCSV() {
            try {
                const res = await fetch('save_trades_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ batch_id: batchId, trades: finalTransactions })
                });
                const body = await res.json();
                if (!res.ok) throw new Error(body.error || res.statusText);
                if (body.saved) { // keep this page's usage current for the next generation
                    finalTransactions.forEach(tx => {
                        const v = parseFloat(tx.currency_code) === 2 ? parseFloat(tx.amount) : parseFloat(tx.total);
                        settings.used_amounts[tx.customer_code] = (parseFloat(settings.used_amounts[tx.customer_code]) || 0) + v;
                    });
                }
            } catch (e) {
                errors.push('Trades were not saved, CSV not downloaded: ' + e.message);
                displayErrors();
                return;
            }

            const csvRows = [
                // Use custom header names for CSV first row
                columnOrder.map(col => `"${columnHeaders[col] || col}"`).join(","),
                ...finalTransactions.map(row =>
                    columnOrder.map(key => `"${row[key]}"`).join(",")
                )
            ];

            const csvContent = csvRows.join("\n");
            const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");

            const startDate = settings.from_date;
            const endDate = settings.to_date;
            const filename = (startDate === endDate)
                ? `transactions_${startDate}.csv`
                : `transactions_${startDate}_to_${endDate}.csv`;

                
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function displayErrors(){
            const errorsDisplay = document.getElementById("errorsDisplay");
            errorsDisplay.innerHTML = ""; // Clear previous errors

            if (errors.length > 0) {
                const errorList = document.createElement("ul");
                errorList.className = "list-group list-group-flush";
                errors.forEach(error => {
                    const errorItem = document.createElement("li");
                    errorItem.className = "list-group-item list-group-item-danger";
                    errorItem.textContent = error;
                    errorList.appendChild(errorItem);
                });
                errorsDisplay.appendChild(errorList);
            } else {
                errorsDisplay.innerHTML = "";
                // errorsDisplay.innerHTML = "<p class='text-success'>No errors found.</p>";
            }
        }

        // Initialize the form to match the Random checkbox default (unchecked = fixed per-day mode).
        applyRandomMode();

    </script>
        
</body>
     
 