<?php

namespace controllers;

use core\Controller;
use models\Patient;
use middlewares\AuthMiddleware;

class PatientController extends Controller {
    private Patient $patientModel;

    public function __construct() {
        $this->patientModel = new Patient();
    }

    public function index() {
        $user = AuthMiddleware::getUserFromToken();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        // All roles can view patients
        $this->json($this->patientModel->getAll());
    }

    public function show() {
        $user = AuthMiddleware::getUserFromToken();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing patient ID'], 400);

        $data = $this->patientModel->getPatientById($id);
        return $data ? $this->json($data) : $this->json(['error' => 'Patient not found'], 404);
    }

public function store() {
    $user = AuthMiddleware::getUserFromToken();
    if (!$user) return $this->json(['error' => 'Forbidden'], 403);

    $body = json_decode(file_get_contents('php://input'), true);

    $required = ['dni', 'first_name', 'last_name', 'birth_date', 'address', 'phone', 'blood_type'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            return $this->json(['error' => "Missing field: $field"], 400);
        }
    }

    // Generate MRN
    $medicalRecNo = 'MRN' . date('YmdHis') . strtoupper(bin2hex(random_bytes(2)));

    // ─────────── NEW: create clinical file first ───────────
    $cfModel = new \models\ClinicalFile();
    $cfModel->create($medicalRecNo);
    // ────────────────────────────────────────────────────────

    $personData = [
        'dni'        => $body['dni'],
        'first_name' => $body['first_name'],
        'last_name'  => $body['last_name'],
        'birth_date' => $body['birth_date'],
        'address'    => $body['address'],
        'phone'      => $body['phone']
    ];

    $patientData = [
        'medical_rec_no' => $medicalRecNo,
        'blood_type'     => $body['blood_type']
    ];

    try {
        $newId = $this->patientModel->createPatient($personData, $patientData);

        return $this->json([
            'success'        => true,
            'patient_id'     => $newId,
            'medical_rec_no' => $medicalRecNo
        ], 201);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 500);
    }
}

    public function update() {
        $user = AuthMiddleware::getUserFromToken();
        if (!$user || ($user['role_id'] !== 3 && $user['role_id'] !== 1)) return $this->json(['error' => 'Forbidden'], 403);

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing patient ID'], 400);

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        try {
            $updated = $this->patientModel->updatePatient($id, $body);
            if ($updated) {
                $this->json(['success' => true, 'message' => "Patient $id updated."]);
            } else {
                $this->json(['error' => 'Nothing was updated'], 200);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        $user = AuthMiddleware::getUserFromToken();
        if (!$user || ($user['role_id'] !== 3 && $user['role_id'] !== 1)) return $this->json(['error' => 'Forbidden'], 403);

        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->json(['error' => 'Missing patient ID'], 400);
        }

        try {
            $deleted = $this->patientModel->deletePatientById($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => "Patient $id deleted."]);
            } else {
                $this->json(['error' => 'Patient not found or already deleted.'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}