<?php

require_once 'db.php'; // Include your database connection file
require_once 'Classes/Settings.php'; // Include your Settings class

if(isset($_POST['save_settings'])) {
    // Assuming you have a function to save settings
    $settings = new Settings($pdo);
    $settings->save($_POST);
    header("Location: settings.php?success=1");
    exit;
}

// Fetch existing settings to pre-fill the form
$settings = new Settings($pdo);
$existingSettings = $settings->all();

if(isset($existingSettings['currency']) && !empty($existingSettings['currency'])) {
    $currencyCount = count(json_decode($existingSettings['currency'], true));
} else {
    $currencyCount = 1; // Default to 1 if no currencies are set
}
?>
