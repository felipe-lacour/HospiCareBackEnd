<?php

namespace controllers;

use core\Controller;
use models\UserAccount;
use models\PasswordSetRequest;

class AuthController extends Controller {
public function sendPasswordSetupLink() {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!isset($body['employee_id'], $body['username'])) {
        return $this->json(['error' => 'Missing employee_id or username'], 400);
    }

    $employeeId = $body['employee_id'];
    $username = $body['username'];

    $token = bin2hex(random_bytes(32));

    $psrModel = new PasswordSetRequest();
    $psrModel->createToken($employeeId, $token);

    $link = "http://localhost:8000/auth/set-password?token=$token";

    return $this->json([
        'success' => true,
        'username' => $username,
        'setup_link' => $link
    ]);
}

public function setPassword() {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!isset($body['token'], $body['new_password'])) {
        return $this->json(['error' => 'Missing data'], 400);
    }

    $token = $body['token'];
    $newPassword = $body['new_password'];

    if (strlen($newPassword) < 8) {
        return $this->json(['error' => 'Password must be at least 8 characters'], 400);
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $psrModel = new PasswordSetRequest();
    $request = $psrModel->getByToken($token);  // Only returns if valid, not used, not expired

    if (!$request) {
        return $this->json(['error' => 'Invalid or expired token'], 400);
    }

    $uaModel = new UserAccount();
    $uaModel->setPassword($request['employee_id'], $hashedPassword);

    $psrModel->markUsed($token);

    return $this->json(['success' => true]);
}
}

