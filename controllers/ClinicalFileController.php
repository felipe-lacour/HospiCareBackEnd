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
        $this->cfModel   = new ClinicalFile();
        $this->noteModel = new ConsultNote();
    }

    private function getCurrentUser() {
        $headers    = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid token header']);
            exit;
        }

        $token      = substr($authHeader, 7);
        $tokenModel = new AuthToken();
        $userData   = $tokenModel->getUserByToken($token);

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        $userModel = new UserAccount();
        return $userModel->findByUsername($userData['username']);
    }

    /**
     * GET /clinical-files?medical_rec_no={mrn}
     */
    public function show() {
        $user = $this->getCurrentUser();



        $mrn  = $_GET['medical_rec_no'] ?? null;
        if (!$mrn) {
            return $this->json(['error' => 'Missing medical_rec_no'], 400);
        }

        $file = $this->cfModel->getByMRN($mrn, $user['role_id']);
        return $this->json($file ?: ['error' => 'Not found'], $file ? 200 : 404);
    }

    /**
     * POST /clinical-files
     * Body JSON: { "medical_rec_no": "MRN..." }
     */
    public function store() {
        $user = $this->getCurrentUser();
        if ($user['role_id'] !== 2 && $user['role_id'] !== 1) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $mrn  = $body['medical_rec_no'] ?? null;
        if (!$mrn) {
            return $this->json(['error' => 'Missing medical_rec_no'], 400);
        }

        $fileNo = $this->cfModel->create($mrn);
        return $this->json(['success' => true, 'medical_rec_no' => $fileNo], 201);
    }

    /**
     * GET /clinical-files/notes?medical_rec_no={mrn}
     */
    public function notes() {
        $user = $this->getCurrentUser();
        if (!$user || ((int)$user['role_id'] !== 1 && (int)$user['role_id'] !== 2)) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $mrn  = $_GET['medical_rec_no'] ?? null;
        if (!$mrn) {
            return $this->json(['error' => 'Missing medical_rec_no'], 400);
        }

        $notes = $this->noteModel->getByMedicalRecNo($mrn);
        return $this->json($notes, 200);
    }

    /**
     * POST /clinical-files/notes
     * Body JSON: { "medical_rec_no": ..., "text": "..." }
     */
public function addNote() {
    $user = $this->getCurrentUser();
    // only doctors (2) and admins (1) can add
    if ($user['role_id'] !== 2 && $user['role_id'] !== 1) {
        return $this->json(['error' => 'Only doctors or admins can add notes'], 403);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || !isset($body['medical_rec_no'], $body['text'])) {
        return $this->json(['error' => 'Missing fields'], 400);
    }

    // 1) Determine which doctor_id to use:
    if ((int)$user['role_id'] === 2) {
        // doctors may only add under their own ID
        $doctorId = $user['employee_id'];
    } else {
        // admin must explicitly specify a valid doctor_id
        if (empty($body['doctor_id'])) {
            return $this->json(['error' => 'Missing doctor_id'], 400);
        }
        $doctorId = (int)$body['doctor_id'];
        // you could also verify it exists:
        // if (!$this->noteModel->doctorExists($doctorId)) { … }
    }

    $noteId = $this->noteModel->createByMRN(
        $body['medical_rec_no'],
        $doctorId,
        $body['text']
    );

    return $this->json(['success' => true, 'note_id' => $noteId], 201);
}

    /**
     * GET /clinical-files/all-notes
     */
    public function allNotes() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ((int)$user['role_id'] === 1 || (int)$user['role_id'] === 2) {
            $notes = $this->noteModel->getAllWithDetails();
            return $this->json($notes, 200);
        } else {
            return $this->json([], 200);
        }

        return $this->json(['error' => 'Access denied'], 403);
    }

    /**
     * PUT /clinical-files/notes
     * Body JSON: { "note_id": ..., "text": "..." }
     */
    public function updateNote() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ((int)$user['role_id'] !== 3) {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input || !isset($input['note_id'], $input['text'])) {
                return $this->json(['error' => 'Missing fields'], 400);
            }

            $updated = $this->noteModel->updateText($input['note_id'], $input['text']);
            return $this->json(['success' => $updated], $updated ? 200 : 404);
        }

        return $this->json(['error' => 'Access denied'], 403);
    }

    /**
 * DELETE /clinical-files/notes?id=NOTE_ID
 */
public function deleteNote() {
    $user = $this->getCurrentUser();
    if (!$user || ((int)$user['role_id'] !== 1 && (int)$user['role_id'] !== 2)) {
        return $this->json(['error' => 'Access denied'], 403);
    }

    $noteId = $_GET['id'] ?? null;
    if (!$noteId) {
        return $this->json(['error' => 'Missing note ID'], 400);
    }

    $deleted = $this->noteModel->deleteById((int)$noteId);
    return $this->json(['success' => $deleted], $deleted ? 200 : 404);
}
}







