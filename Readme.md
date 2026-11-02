# Trade Desk (auto trades creator)

Generates trades for a set of currencies inside a date/time window, assigns each trade a customer, and exports them as CSV. Every downloaded trade is saved, so a per-customer amount limit can be enforced across runs.

## Run

Start MySQL and PHP:

```
php -S 127.0.0.1:8080
```

Open http://127.0.0.1:8080. Database credentials are in `db.php`; the table definitions (`settings`, `data_entry`, `trades`) are in the comment block at the bottom of that file.

## Pages

| Page | What it does |
|------|--------------|
| **Generate trades** (`index.php`) | Enter currency code, total amount, rate and trades per day (or total trades). Generate a preview, then download the CSV. |
| **History** (`history.php`) | Every saved trade, with filters, pagination, stats and CSV download. |
| **Summary** (`summary.php`) | Trade count and total amount per customer. A customer code links to History with the same filters. |
| **Settings** (`settings.php`) | Trade dates and times, customer code range, customer amount upper limit, look-back days and the currency table. |

The sidebar shows the active rules (limit, trade dates, customer codes) on every page.

## Generating trades

- **Fixed mode** (default): each row's trades per day is spread evenly across the date range.
- **Random mode**: trades are spread randomly across days, with at least one per day.
- Amounts are split in multiples of the currency's interval. Rates must fall inside the buy/sell range set for the currency.
- Times get a unique minute slot inside the time window. Each trade gets a unique customer code from the range.
- **Save entries** only stores the entry rows for next time. It does not save trades.

## Customer amount upper limit

Set in Settings: **Customer amount upper limit** and **Look-back period (days)**. A limit of 0 turns it off.

- A customer cannot trade more than the limit, buys and sells combined, within the look-back period.
- The window counts back from today and includes today.
- The counted value is `amount` when the trade's currency code is `2`, otherwise `total`.
- A customer without enough room left is skipped. If no customer in the range can take a trade, generation stops with an error naming the range, limit and days.
- Customers are picked greedily (largest trade first), so in a rare case a perfect packing might exist where an error is shown.
- The limit is checked in the browser at generation time. The save endpoint does not re-check it.

## Saving trades

Trades are saved to the `trades` table when you click **Download CSV** on the Generate page, not when you generate.

- Each generation has one batch id, so downloading the same generation twice saves it once.
- If saving fails, the CSV is not downloaded and an error is shown.
- Generating again creates a new batch.

## History and Summary

- **Filters**: from date, to date, customer code, currency code (History only), type (Buy/Sell) and page size (default 100). From date defaults to the start of the look-back window.
- **Stats**: total amount (same counting rule as the limit), distinct customers and trade count, over every filtered trade.
- **Download CSV** (History): exports all trades matching the current applied filters, with the same columns as the Generate CSV.

## Files

```
index.php, history.php, summary.php, settings.php   pages
nav.php, header.php, footer.php                      shared layout
save_trades_ajax.php                                 saves a downloaded batch
Classes/Trades.php                                   usage, save, search, stats, summary queries
Classes/Settings.php, Classes/DataEntry.php          settings and entry rows
assets/app.css                                       look and feel, over Bootstrap
```
