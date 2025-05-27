<?php

$host = 'localhost';
$db   = 'auto_trade_generator';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // enable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch as associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepares
];


try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

function prd($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    die;
}

function pr($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

/*


CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(200) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `auto_trade_generator`.`data_entry` (
    `currency_code` VARCHAR(20) NOT NULL , 
    `total_amount` VARCHAR(40) NOT NULL , 
    `rate` VARCHAR(20) NOT NULL , 
    `total_trades` INT NOT NULL 
    ) ENGINE = InnoDB;


*/