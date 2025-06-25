<?php

namespace models;

use core\Model;
use models\Patient;
class ClinicalFile extends Model {
    protected $table = 'clinical_files';


    public function getAll(): array {
        return $this->db->query(
            "SELECT * FROM {$this->table}"
        )->fetchAll();
    }


public function getByMRN(string $medicalRecNo, int $role) {
    // Obtener la historia clínica
    $stmt = $this->db->prepare(
        "SELECT * FROM {$this->table} WHERE medical_rec_no = :mrn"
    );
    $stmt->execute(['mrn' => $medicalRecNo]);
    $clinicalFile = $stmt->fetch();

    if (!$clinicalFile) return null;

    if($role == 2 || $role == 1){
        $notesStmt = $this->db->prepare(
            "SELECT * FROM consult_notes WHERE medical_rec_no = :mrn ORDER BY time DESC"
        );
        $notesStmt->execute(['mrn' => $medicalRecNo]);
        $notes = $notesStmt->fetchAll();

        $clinicalFile['consult_notes'] = $notes;
    }

    // Obtener datos del paciente desde el modelo Patient
    $patientModel = new Patient();
    $patient = $patientModel->getPatientByMRN($medicalRecNo);

    // Agregar info combinada
    $clinicalFile['patient'] = $patient;

    return $clinicalFile;
}


    public function create(string $medicalRecNo): string {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (medical_rec_no, open_date)
             VALUES (:mrn, CURDATE())"
        );
        $stmt->execute(['mrn' => $medicalRecNo]);
        return $medicalRecNo;
    }


    public function delete(string $medicalRecNo): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE medical_rec_no = :mrn"
        );
        return $stmt->execute(['mrn' => $medicalRecNo]);
    }
}