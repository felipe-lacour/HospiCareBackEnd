<?php

namespace models;

use core\Model;

class ClinicalFile extends Model {
    protected $table = 'clinical_files';

    public function getAll() {
        return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
    }

    public function getByPatientId($patientId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE patient_id = :pid");
        $stmt->execute(['pid' => $patientId]);
        return $stmt->fetch();
    }

    public function create($patientId) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (patient_id, open_date)
            VALUES (:pid, CURDATE())
        ");
        $stmt->execute(['pid' => $patientId]);
        return $this->db->lastInsertId();
    }

    public function delete($fileId) {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE file_id = :id")
            ->execute(['id' => $fileId]);
    }
}