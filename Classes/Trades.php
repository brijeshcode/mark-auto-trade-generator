<?php

class Trades {
    protected $pdo;

    // Trades with this currency code count by `amount`; all others count by `total`.
    const AMOUNT_CURRENCY = '2';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Counted value per customer over the last $days days (today included).
    // No customer-code range filter: customer_code is a varchar, so BETWEEN would compare as text ('497' > '1100').
    public function usedByCustomer($days) {
        $stmt = $this->pdo->prepare(
            "SELECT customer_code, SUM(IF(currency_code = :cur, amount, total)) AS used
             FROM trades
             WHERE trade_date BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
             GROUP BY customer_code"
        );
        $stmt->execute([':cur' => self::AMOUNT_CURRENCY, ':days' => (int)$days]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Saves a generated batch once; a repeated batch_id is ignored. Returns false if already saved.
    public function saveBatch($batchId, $trades) {
        $this->pdo->beginTransaction();
        try {
            $check = $this->pdo->prepare("SELECT 1 FROM trades WHERE batch_id = :b LIMIT 1");
            $check->execute([':b' => $batchId]);
            if ($check->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO trades (batch_id, trade_date, trade_time, customer_code, transaction_type, currency_code, cv_currency, amount, rate, total)
                 VALUES (:batch_id, :trade_date, :trade_time, :customer_code, :type, :currency_code, :cv_currency, :amount, :rate, :total)"
            );
            foreach ($trades as $t) {
                $date = DateTime::createFromFormat('d-m-Y', $t['date']);
                $stmt->execute([
                    ':batch_id' => $batchId,
                    ':trade_date' => $date->format('Y-m-d'),
                    ':trade_time' => $t['time'],
                    ':customer_code' => $t['customer_code'],
                    ':type' => $t['buy'],
                    ':currency_code' => $t['currency_code'],
                    ':cv_currency' => $t['cv_currency'],
                    ':amount' => $t['amount'],
                    ':rate' => $t['rate'],
                    ':total' => $t['total'],
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Totals over all rows matching the filters (not just the current page).
    public function stats($f) {
        [$sql, $params] = $this->filter($f);
        $params[':cur'] = self::AMOUNT_CURRENCY;
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS trades, COUNT(DISTINCT customer_code) AS customers,
                    COALESCE(SUM(IF(currency_code = :cur, amount, total)), 0) AS total_amount
             FROM trades" . $sql
        );
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // Every row matching the filters (for CSV export).
    public function all($f) {
        [$sql, $params] = $this->filter($f);
        $stmt = $this->pdo->prepare("SELECT * FROM trades $sql ORDER BY trade_date, trade_time, id");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Per-customer trade count and counted amount. Returns [rows, customerCount].
    public function summary($f, $page, $perPage) {
        [$sql, $params] = $this->filter($f);

        $count = $this->pdo->prepare("SELECT COUNT(DISTINCT customer_code) FROM trades" . $sql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $params[':cur'] = self::AMOUNT_CURRENCY;
        $perPage = (int)$perPage;
        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $this->pdo->prepare(
            "SELECT customer_code, COUNT(*) AS trades,
                    SUM(IF(currency_code = :cur, amount, total)) AS total_amount
             FROM trades $sql
             GROUP BY customer_code
             ORDER BY total_amount DESC, customer_code
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        return [$stmt->fetchAll(), $total];
    }

    protected function filter($f) {
        $where = [];
        $params = [];
        if ($f['from_date'] !== '')     { $where[] = 'trade_date >= :from_date';       $params[':from_date'] = $f['from_date']; }
        if ($f['to_date'] !== '')       { $where[] = 'trade_date <= :to_date';         $params[':to_date'] = $f['to_date']; }
        if ($f['customer_code'] !== '') { $where[] = 'customer_code = :customer_code'; $params[':customer_code'] = $f['customer_code']; }
        if ($f['currency_code'] !== '') { $where[] = 'currency_code = :currency_code'; $params[':currency_code'] = $f['currency_code']; }
        if ($f['type'] !== '')          { $where[] = 'transaction_type = :type';       $params[':type'] = $f['type']; }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    // Filtered, paginated history. Returns [rows, totalCount].
    public function search($f, $page, $perPage) {
        [$sql, $params] = $this->filter($f);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM trades" . $sql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $perPage = (int)$perPage;
        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $this->pdo->prepare("SELECT * FROM trades $sql ORDER BY trade_date DESC, trade_time DESC, id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        return [$stmt->fetchAll(), $total];
    }
}
