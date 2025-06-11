<?php

namespace models;

use models\Person;

class Patient extends Person {
    protected $patientTable = 'patients';

    public function getAll() {
        $stmt = $this->db->query("
            SELECT p.*, pat.medical_rec_no, pat.blood_type
            FROM persons p
            INNER JOIN patients pat ON p.person_id = pat.patient_id
        ");
        return $stmt->fetchAll();
    }

    public function createPatient(array $personData, array $patientData) {
        // Create a new person entry first
        $personId = $this->create($personData);

        // Now add patient-specific details
        $stmt = $this->db->prepare("
            INSERT INTO {$this->patientTable} (patient_id, medical_rec_no, blood_type)
            VALUES (:patient_id, :medical_rec_no, :blood_type)
        ");

        $stmt->execute([
            'patient_id' => $personId,
            'medical_rec_no' => $patientData['medical_rec_no'],
            'blood_type' => $patientData['blood_type']
        ]);

        return $personId;
    }

    public function getPatientById($patientId) {
        $stmt = $this->db->prepare("
            SELECT p.*, pat.medical_rec_no, pat.blood_type
            FROM {$this->table} p
            INNER JOIN {$this->patientTable} pat ON p.person_id = pat.patient_id
            WHERE pat.patient_id = :id
        ");
        $stmt->execute(['id' => $patientId]);
        return $stmt->fetch();
    }
    
    public function deletePatientById($id) {
    $stmt = $this->db->prepare("DELETE FROM patients WHERE patient_id = :id");
    $stmt->execute(['id' => $id]);

    $stmt2 = $this->db->prepare("DELETE FROM persons WHERE person_id = :id");
    return $stmt2->execute(['id' => $id]);
    }
}