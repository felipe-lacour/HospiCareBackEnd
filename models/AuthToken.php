<?php

namespace models;

use core\Model;
use DateTime;

class AuthToken extends Model {
    protected string $table = 'auth_tokens';

    public function createToken(string $username): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (token, username, expires_at) VALUES (:token, :username, :expires_at)");
        $stmt->execute([
            'token' => $token,
            'username' => $username,
            'expires_at' => $expiresAt
        ]);

        return $token;
    }

    public function getUserByToken(string $token): ?array {
        $stmt = $this->db->prepare("SELECT username FROM {$this->table} WHERE token = :token AND expires_at > NOW()");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function invalidateToken(string $token): void {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }
}