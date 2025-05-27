<?php

require_once 'db.php'; // Include your database connection file
require_once 'Classes/Settings.php'; // Include your Settings class
require_once 'Classes/DataEntry.php'; // Include your Settings class



if (isset($_POST['save_trades'])) {
    $dataEntry = new DataEntry($pdo);
    
    $dataEntry->save($_POST['entry']);
    
    header("Location: index.php?success=1");
    exit;
}

$dataEntry = new DataEntry($pdo);
$entries = $dataEntry->all();

$settingsObj = new Settings($pdo);
$settings = $settingsObj->all();
