<?php

namespace models;

use core\Model;

class ConsultNote extends Model {
    protected $table = 'consult_notes';

    public function getByFileId($fileId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} WHERE file_id = :fid ORDER BY time DESC
        ");
        $stmt->execute(['fid' => $fileId]);
        return $stmt->fetchAll();
    }

    public function create($fileId, $doctorId, $text) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (file_id, doctor_id, text)
            VALUES (:fid, :did, :text)
        ");
        $stmt->execute([
            'fid' => $fileId,
            'did' => $doctorId,
            'text' => $text
        ]);
        return $this->db->lastInsertId();
    }

    public function getAllWithDetails() {
        $stmt = $this->db->prepare("
            SELECT cn.note_id, cn.time, cn.text, 
            p.first_name AS patient_first_name, p.last_name AS patient_last_name,
            dp.first_name AS doctor_first_name, dp.last_name AS doctor_last_name
            FROM consult_notes cn
            JOIN clinical_files cf ON cn.file_id = cf.file_id
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

    public function updateText($noteId, $text) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET text = :text WHERE note_id = :id");
        return $stmt->execute(['text' => $text, 'id' => $noteId]);
    }
}