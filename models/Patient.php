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
        $personId = $this->create($personData);

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

public function getPatientByMRN(string $medicalRecNo) {
    $stmt = $this->db->prepare("
        SELECT 
            p.*,
            pat.patient_id,
            pat.medical_rec_no,
            pat.blood_type
        FROM patients pat
        JOIN persons p ON pat.patient_id = p.person_id
        WHERE pat.medical_rec_no = :mrn
    ");
    $stmt->execute(['mrn' => $medicalRecNo]);
    return $stmt->fetch();
}

    public function updatePatient($id, array $data) {
    unset($data['dni'], $data['medical_rec_no']);

    $stmt1 = $this->db->prepare("
        UPDATE persons SET
            first_name = :first_name,
            last_name = :last_name,
            birth_date = :birth_date,
            address = :address,
            phone = :phone
        WHERE person_id = :id
    ");
    $stmt1->execute([
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'birth_date' => $data['birth_date'],
        'address' => $data['address'],
        'phone' => $data['phone'],
        'id' => $id
    ]);

    $stmt2 = $this->db->prepare("
        UPDATE {$this->patientTable} SET
            blood_type = :blood_type
        WHERE patient_id = :id
    ");
    $stmt2->execute([
        'blood_type' => $data['blood_type'],
        'id' => $id
    ]);

    return $stmt1->rowCount() > 0 || $stmt2->rowCount() > 0;
}
    
    public function deletePatientById($id) {
    $stmt = $this->db->prepare("DELETE FROM patients WHERE patient_id = :id");
    $stmt->execute(['id' => $id]);

    $stmt2 = $this->db->prepare("DELETE FROM persons WHERE person_id = :id");
    return $stmt2->execute(['id' => $id]);
    }
}