<?php

namespace controllers;

use core\Controller;
use models\Doctor;

class DoctorController extends Controller {
    protected Doctor $doctorModel;

    public function __construct() {
        $this->doctorModel = new Doctor();
    }

    public function index() {
        $doctors = $this->doctorModel->getAllDoctors();
        $this->json($doctors);
    }

    public function show() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing doctor ID'], 400);

        $doctor = $this->doctorModel->getDoctorById($id);
        if ($doctor) {
            $this->json($doctor);
        } else {
            $this->json(['error' => 'Doctor not found'], 404);
        }
    }

public function store() {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

try {
    $personData = [
        'dni' => $body['dni'],
        'first_name' => $body['first_name'],
        'last_name' => $body['last_name'],
        'birth_date' => $body['birth_date'],
        'address' => $body['address'],
        'phone' => $body['phone']
    ];

    $employeeData = [
        'email' => $body['email'],
        'hire_date' => date('Y-m-d H:i:s'),
    ];

    $doctorData = [
        'license_no' => $body['license_no'],
        'specialty' => $body['specialty']
    ];

    $userData = [
        'username' => $body['username'],
        'role_id' => $body['role_id'] ?? 2
    ];

    $result = $this->doctorModel->createDoctor($personData, $employeeData, $doctorData, $userData);

    $this->json([
        'success' => true,
        'employee_id' => $result['employee_id'],
        'username' => $result['username'],
        'setup_link' => $result['setup_link'] // link for admin to copy and send
    ]);
} catch (\Exception $e) {
    $this->json(['error' => $e->getMessage()], 500);
}
}
}