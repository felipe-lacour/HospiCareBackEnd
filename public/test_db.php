<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path if needed

use models\Patient;

// Instantiate the model
$patient = new Patient();

try {
    // Test method: fetch all patients
    $result = $patient->getPatientById(1); // Replace with an actual ID in your DB

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}