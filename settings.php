<?php include 'header.php'; ?>
<?php include 'SettingsController.php'; ?>

<body class="container"> 
      
    <form action="/settings.php" method="post" class="mt-1">

        <div class="card ">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="/">Data entry</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="true" href="#">Settings</a>
                </li> 
                </ul>
            </div>
            
            <div class="card-body">
                <?php if (isset($_GET['success']) && $_GET['success'] == 1) {
                    echo '<div class="alert alert-success mt-3" role="alert">Settings saved successfully!</div>';
                }
                ?>
                <h5 class="card-title">Settings</h5>
                <p class="card-text">Here we add the settings to generate random trades.</p>

                <div class="row">
                    <div class="col">
                        <label for="exampleFormControlInput1" class="form-label">Date Range</label>
                        <div class="row">
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">From</label>
                                <input type="date" class="form-control" name="date_from" value="<?= $existingSettings['date_from'] ?? ''; ?>" >
                            </div>
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">To</label>
                                <input type="date" class="form-control" name="date_to" value="<?= $existingSettings['date_to'] ?? ''; ?>" >
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <label for="exampleFormControlInput1" class="form-label">Time Range</label>
                        <div class="row">
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">From</label>
                                <input type="time" class="form-control" name="time_from" value="<?= $existingSettings['time_from'] ?? ''; ?>"   >
                            </div>
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">To</label>
                                <input type="time" class="form-control" name="time_to" value="<?= $existingSettings['time_to'] ?? ''; ?>"   >
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <label for="exampleFormControlInput1" class="form-label">Customer code Range</label>
                        <div class="row">
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">From</label>
                                <input type="number" class="form-control" name="customer_code_from" value="<?= $existingSettings['customer_code_from'] ?? ''; ?>"  >
                            </div>
                            <div class="col">
                                <label for="exampleFormControlInput1" class="form-label">To</label>
                                <input type="number" class="form-control" name="customer_code_to" value="<?= $existingSettings['customer_code_to'] ?? ''; ?>"  >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    currency Settings
                    <table class="table table-primary  table-striped table-hover">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                                <th scope="col" class="px-2 py-2" >
                                    <span class="btn btn-outline-success btn-sm" onclick="appendRow()">Add</span>
                                </th>
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

                    <input type="hidden" id="currency_count" name="currency_count" value="<?= $currencyCount; ?>">
                </div>

            </div>
        </div> 
        
        
        <button type="submit" class="btn btn-primary mt-3" name="save_settings">Save Settings</button>
    </form>


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