<?php

namespace models;

use core\Model;

class ConsultNote extends Model {
    protected $table = 'consult_notes';

    public function create($mrn, $doctorId, $text) {
    $stmt = $this->db->prepare("
        INSERT INTO {$this->table} (medical_rec_no, doctor_id, text)
        VALUES (:mrn, :did, :text)
    ");
    $stmt->execute([
        'mrn' => $mrn,
        'did' => $doctorId,
        'text' => $text
    ]);
    return $this->db->lastInsertId();
}

public function getByMRN($mrn) {
    $stmt = $this->db->prepare("
        SELECT * FROM {$this->table} WHERE medical_rec_no = :mrn ORDER BY time DESC
    ");
    $stmt->execute(['mrn' => $mrn]);
    return $stmt->fetchAll();
}

public function getAllWithDetails() {
    $stmt = $this->db->prepare("
        SELECT cn.note_id, cn.time, cn.text, 
        p.first_name AS patient_first_name, p.last_name AS patient_last_name,
        dp.first_name AS doctor_first_name, dp.last_name AS doctor_last_name
        FROM consult_notes cn
        JOIN clinical_files cf ON cn.medical_rec_no = cf.medical_rec_no
        JOIN patients pat ON cf.patient_id = pat.patient_id
        JOIN persons p ON pat.patient_id = p.person_id
        JOIN doctors d ON cn.doctor_id = d.doctor_id
        JOIN employees e ON d.doctor_id = e.employee_id
        JOIN persons dp ON e.person_id = dp.person_id
        ORDER BY cn.time DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

public function createByMRN($mrn, $doctorId, $text) {
    return $this->create($mrn, $doctorId, $text);
}

public function deleteById(int $noteId): bool {
    $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE note_id = :id");
    return $stmt->execute(['id' => $noteId]);
}
}