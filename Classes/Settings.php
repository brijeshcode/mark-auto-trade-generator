<?php
// src/Settings.php

class Settings {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT setting_key, value FROM settings");
        $results = $stmt->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['value'];
        }
        return $settings;
    }

    public function get($key) {
        $stmt = $this->pdo->prepare("SELECT value FROM settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        return $stmt->fetchColumn();
    }

    public function save($settings) {
        $data['date_from'] = $settings['date_from'] ?? null;
        $data['date_to'] = $settings['date_to'] ?? null;
        $data['time_from'] = $settings['time_from'] ?? null;
        $data['time_to'] = $settings['time_to'] ?? null;
        $data['customer_code_from'] = $settings['customer_code_from'] ?? null;
        $data['customer_code_to'] = $settings['customer_code_to'] ?? null;
        $data['currency'] = json_encode($settings['currency'] ?? []);

        $stmt = $this->pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (:setting_key, :value) ON DUPLICATE KEY UPDATE value = :value_update");
        
        foreach ($data as $key => $value) {
            $stmt->execute([':setting_key' => $key, ':value' => $value, ':value_update' => $value]);
        }
    }
}
