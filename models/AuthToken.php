<?php

namespace models;

use core\Model;
use DateTime;

class AuthToken extends Model {
    protected string $table = 'auth_tokens';

    public function create(array $data): int {
        if (!isset($data['token'], $data['user_id'], $data['expires_at'])) {
            throw new \Exception("Missing required fields for token creation.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (token, user_id, expires_at)
            VALUES (:token, :user_id, :expires_at)
        ");
        $stmt->execute([
            'token' => $data['token'],
            'user_id' => $data['user_id'], 
            'expires_at' => $data['expires_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

        $this->create([
            'token' => $token,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

public function getUserByToken(string $token): ?array {
    $sql = "SELECT ua.username, ua.role_id, ua.employee_id
            FROM auth_tokens at
            JOIN user_accounts ua ON ua.username = at.user_id
            WHERE at.token = :token AND at.expires_at > NOW()";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    return $user ?: null;
}

    public function invalidateToken(string $token): void {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }
}