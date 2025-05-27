<?php

// require_once 'Settings.php'; // Include your Settings class

class DataEntry {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function save($data) {

        // first delete existing entries
        $stmt = $this->pdo->prepare("DELETE FROM data_entry");
        $stmt->execute();


        $stmt = $this->pdo->prepare("INSERT INTO data_entry (currency_code, total_amount, rate, total_trades) VALUES (:currency_code, :total_amount, :rate, :total_trades)");
        
        foreach ($data as $entry) {
            $stmt->execute([
                ':currency_code' => $entry['currency_code'],
                ':total_amount' => $entry['total_amount'],
                ':rate' => $entry['rate'],
                ':total_trades' => $entry['total_trades']
            ]);
        }
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT * FROM data_entry");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}