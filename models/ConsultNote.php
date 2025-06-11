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
}