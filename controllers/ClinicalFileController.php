<?php

namespace controllers;

use core\Controller;
use models\ClinicalFile;
use models\ConsultNote;
use models\AuthToken;
use models\UserAccount;

class ClinicalFileController extends Controller {
    protected ClinicalFile $cfModel;
    protected ConsultNote $noteModel;

    public function __construct() {
        $this->cfModel = new ClinicalFile();
        $this->noteModel = new ConsultNote();
    }

    private function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid token header']);
            exit;
        }

        $token = substr($authHeader, 7);
        $tokenModel = new AuthToken();
        $userData = $tokenModel->getUserByToken($token);

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        $userModel = new UserAccount();
        return $userModel->findByUsername($userData['username']);
    }

    public function show() {
        $user = $this->getCurrentUser();
        $patientId = $_GET['patient_id'] ?? null;
        if (!$patientId) return $this->json(['error' => 'Missing patient_id'], 400);

        $file = $this->cfModel->getByPatientId($patientId);
        $this->json($file ?: ['error' => 'Not found'], $file ? 200 : 404);
    }

    public function store() {
        $user = $this->getCurrentUser();
        if ($user['role_id'] !== 3 && $user['role_id'] !== 1) return $this->json(['error' => 'Unauthorized'], 403);

        $body = json_decode(file_get_contents('php://input'), true);
        $pid = $body['patient_id'] ?? null;
        if (!$pid) return $this->json(['error' => 'Missing patient_id'], 400);

        $fileId = $this->cfModel->create($pid);
        $this->json(['success' => true, 'file_id' => $fileId]);
    }

    public function notes() {
        $user = $this->getCurrentUser();
        $fileId = $_GET['file_id'] ?? null;
        if (!$fileId) return $this->json(['error' => 'Missing file_id'], 400);

        $notes = $this->noteModel->getByFileId($fileId);
        $this->json($notes);
    }

    public function addNote() {
        $user = $this->getCurrentUser();
        if ($user['role_id'] !== 2 && $user['role_id'] !== 1) return $this->json(['error' => 'Only doctors can add notes'], 403);

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['file_id'], $body['text']))
            return $this->json(['error' => 'Missing fields'], 400);

        $id = $this->noteModel->create($body['file_id'], $user['employee_id'], $body['text']);
        $this->json(['success' => true, 'note_id' => $id]);
    }

    public function allNotes() {
        $user = $this->getCurrentUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        if ((int)$user['role_id'] === 1) {
            $notes = $this->noteModel->getAllWithDetails();
            $this->json($notes);
        } else return $this->json(['error' => 'Access denied'], 403);
    }

    public function updateNote() {
        $user = $this->getCurrentUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        if ((int)$user['role_id'] != 3) {
            $input = json_decode(file_get_contents("php://input"), true);

            if (!$input || !isset($input['note_id'], $input['text'])) {
                return $this->json(['error' => 'Missing fields'], 400);
            }

            $updated = $this->noteModel->updateText($input['note_id'], $input['text']);

            echo json_encode(['success' => $updated]);
        } else return $this->json(['error' => 'Access denied'], 403);
    }
}






