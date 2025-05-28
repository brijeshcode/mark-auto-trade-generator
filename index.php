<?php
    include 'header.php'; 
    include 'DataEntryController.php';
 ?>

<body class="container mx-auto bg-gray-100">
    

    <div class="card ">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" aria-current="true" href="#">Data entry</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/settings.php">Settings</a>
            </li> 
            </ul>
        </div>
        <div class="card-body">
            <h5 class="card-title">Data Entry form</h5>
            <p class="card-text">Here we add the data paramenter to generate random trades.</p>
            <div id="errorsDisplay"></div>
            <form method="post" action="DataEntryController.php">
                <div class="row">
                    <div class="col">
                        <input type="hidden" id="entry_count" value="<?= count($entries) ?>">
                        <table class="table table-warning table-striped-columns  table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Currency Code</th>
                                    <th scope="col">Total Amount</th>
                                    <th scope="col">Rate</th>
                                    <th scope="col">Total Trades</th>
                                    <th scope="col" class="px-2 py-2" >
                                        <span class="btn btn-outline-success btn-sm" onclick="appendRow()">Add</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($entries as $key => $entry): ?>
                                    <tr id="entry-<?php echo $key + 1; ?>">
                                        <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][currency_code]" value="<?php echo htmlspecialchars($entry['currency_code']); ?>"></td>
                                        <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][total_amount]" value="<?php echo htmlspecialchars($entry['total_amount']); ?>"></td>
                                        <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][rate]" value="<?php echo htmlspecialchars($entry['rate']); ?>"></td>
                                        <td><input type="text" class="form-control" name="entry[<?php echo $key + 1; ?>][total_trades]" value="<?php echo htmlspecialchars($entry['total_trades']); ?>"></td>
                                        <td>
                                            <button type="button" class="btn-close" aria-label="Close" onclick="removeRow(<?php echo $key + 1; ?>)"></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <table>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col">
                        <button type="button" class="btn btn-primary" onclick="generateTradeCsv()">Generate trades</button>
                        <button type="submit" name="save_trades" class="btn btn-primary">Save trades</button>
                    </div>
                </div>
            </form>
        </div> 
    </div>

    <div class="container mt-5 d-none" id="previewContainer">
        <hr>
        <h2 class="mb-4">Transaction Preview 
            <button type="button" class="btn btn-primary mt-3" onclick="downloadCSV()">
            <i class="bi bi-download"></i> Download as CSV
            </button>
        </h2>
        
        <div class="table-responsive" id="previewTable"></div>
        
        
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
        };
        var errors = [];
        var dataEntry = <?= json_encode($entries); ?>;

        const columnOrder = [
            "date",
            "time",
            "customer_code",
            "currency_code",
            "cv_currency",
            "buy",
            "amount",
            "rate",
            "total"
        ];

        // Map keys to custom display names
        const columnHeaders = {
            date: "Date",
            time: "Time",
            customer_code: "Customer Code",
            currency_code: "Currency Code",
            cv_currency: "Cv Currency",
            buy: "Buy",
            amount: "Amount",
            rate: "Rate",
            total: "Total"
        };
        
        var finalTransactions = [];
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
                <td><input type="text" class="form-control" name="entry[${count}][total_trades]"></td>
                <td><button type="button" class="btn-close" aria-label="Close" onclick="removeRow(${count})"></button></td>
            `;
            tbody.appendChild(newRow);
        }

        function removeRow(count) {
            const row = document.getElementById(`entry-${count}`);
            if (row) {
                row.remove();
            }
        }
 

        function generateTradeCsv(){
            errors = [];
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

                trades.push(...tradesForEntry);
            });

            trades = shuffleArray(trades);
            trades = assignTimes(trades, settings.from_time, settings.to_time);
            trades = assignDates(trades, settings.from_date, settings.to_date);
            trades = assignCustomerCodes(trades, settings.from_customer_code, settings.to_customer_code);

            document.getElementById("previewContainer").classList.remove("d-none");
            console.log(trades);
            renderTable(trades);

            finalTransactions = trades; // Store final transactions for CSV download

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

            // Assign to transactions
            transactions.forEach((tx, index) => {
                tx.customer_code = availableCodes[index];
            });

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

            if (dayDiff <= 0) {
                errors.push("Invalid date range");
                displayErrors();
                throw new Error("Invalid date range");
            }

            for (let tx of transactions) {
                const randomDayOffset = Math.floor(Math.random() * dayDiff);
                const date = new Date(startDate);
                date.setDate(date.getDate() + randomDayOffset);
                tx.date = date.toISOString().split('T')[0]; // Format: "YYYY-MM-DD"
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

            // Step 1: Generate random unit weights
            let units = Array(transactionCount).fill(0).map(() => Math.random());
            const sum = units.reduce((a, b) => a + b, 0);
            units = units.map(u => Math.floor((u / sum) * totalUnits));

            // Step 2: Adjust rounding errors
            let unitSum = units.reduce((a, b) => a + b, 0);
            let diff = totalUnits - unitSum;

            // Distribute remaining units
            let i = 0;
            while (diff > 0) {
                units[i % transactionCount]++;
                i++;
                diff--;
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
                document.getElementById("previewTable").innerHTML = "<p>No data available.</p>";
                return;
            }

            let table = `<table class="table table-bordered table-hover table-sm align-middle">
                <thead class="table-light">
                <tr>${columnOrder.map(h => `<th>${h.replace(/_/g, " ").toUpperCase()}</th>`).join("")}</tr>
                </thead>
                <tbody>
                ${data.map(row => `
                    <tr>${columnOrder.map(key => `<td>${row[key]}</td>`).join("")}</tr>
                `).join("")}
                </tbody>
            </table>`;

            document.getElementById("previewTable").innerHTML = table;
        }
        
        // CSV download using columnOrder
        function downloadCSV() {
      
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

    </script>
        
</body>
     
 