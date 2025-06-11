<?php

namespace controllers;

use core\Controller;
use models\ClinicalFile;
use models\ConsultNote;

class ClinicalFileController extends Controller {
    protected ClinicalFile $cfModel;
    protected ConsultNote $noteModel;

    public function __construct() {
        $this->cfModel = new ClinicalFile();
        $this->noteModel = new ConsultNote();
    }

    public function show() {
        $patientId = $_GET['patient_id'] ?? null;
        if (!$patientId) return $this->json(['error' => 'Missing patient_id'], 400);

        $file = $this->cfModel->getByPatientId($patientId);
        $this->json($file ?: ['error' => 'Not found'], $file ? 200 : 404);
    }

    public function store() {
        $body = json_decode(file_get_contents('php://input'), true);
        $pid = $body['patient_id'] ?? null;
        if (!$pid) return $this->json(['error' => 'Missing patient_id'], 400);

        $fileId = $this->cfModel->create($pid);
        $this->json(['success' => true, 'file_id' => $fileId]);
    }

    public function notes() {
        $fileId = $_GET['file_id'] ?? null;
        if (!$fileId) return $this->json(['error' => 'Missing file_id'], 400);

        $notes = $this->noteModel->getByFileId($fileId);
        $this->json($notes);
    }

    public function addNote() {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['file_id'], $body['doctor_id'], $body['text']))
            return $this->json(['error' => 'Missing fields'], 400);

        $id = $this->noteModel->create($body['file_id'], $body['doctor_id'], $body['text']);
        $this->json(['success' => true, 'note_id' => $id]);
    }
}