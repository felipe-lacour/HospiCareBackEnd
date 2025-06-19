<?php

namespace models;

use core\Model;

class ClinicalFile extends Model {
    protected $table = 'clinical_files';


    public function getAll(): array {
        return $this->db->query(
            "SELECT * FROM {$this->table}"
        )->fetchAll();
    }


    public function getByMRN(string $medicalRecNo) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE medical_rec_no = :mrn"
        );
        $stmt->execute(['mrn' => $medicalRecNo]);
        return $stmt->fetch();
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