<?php

namespace models;

use core\Model;
use models\Person;
use models\Employee;
use models\UserAccount;
use models\PasswordSetRequest;

class Doctor extends Model {
    protected $table = 'doctors';

public function createDoctor(array $personData, array $employeeData, array $doctorData, array $userData): array {
    // 1. Create person
    $personModel = new Person();
    $personId = $personModel->create($personData);

    // 2. Create employee
    $employeeModel = new Employee();
    $employeeId = $employeeModel->create([
        'person_id' => $personId,
        'email' => $employeeData['email'],
        'hire_date' => $employeeData['hire_date']
    ]);

    // 3. Create doctor
    $stmt = $this->db->prepare("
        INSERT INTO {$this->table} (doctor_id, license_no, specialty)
        VALUES (:doctor_id, :license_no, :specialty)
    ");
    $stmt->execute([
        'doctor_id' => $employeeId,
        'license_no' => $doctorData['license_no'],
        'specialty' => $doctorData['specialty']
    ]);

    // 4. Create user account
    $userModel = new UserAccount();
    $username = $userData['username'];
    $roleId = $userData['role_id'];

    $placeholderPassword = password_hash(bin2hex(random_bytes(6)), PASSWORD_BCRYPT);

    $userModel->create([
        'username' => $username,
        'employee_id' => $employeeId,
        'role_id' => $roleId,
        'pwd_hash' => $placeholderPassword
    ]);

    // 5. Create token and return link
    $token = bin2hex(random_bytes(32));
    $psrModel = new PasswordSetRequest();
    $psrModel->createToken($employeeId, $token);

    $link = $this->sendPasswordSetupEmail($employeeData['email'], $username, $token);

    return [
        'employee_id' => $employeeId,
        'username' => $username,
        'setup_link' => $link
    ];
}

public function getAllDoctors(): array {
    $stmt = $this->db->prepare("
        SELECT d.*, e.email, p.first_name, p.last_name, p.dni, p.birth_date, p.address, p.phone
        FROM doctors d
        JOIN employees e ON d.doctor_id = e.employee_id
        JOIN persons p ON e.person_id = p.person_id
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

public function sendPasswordSetupEmail(string $email, string $username, string $token): string {
    return "http://localhost:8000/auth/set-password?token=$token";
}

public function getDoctorById(int $id): array|false {
    $stmt = $this->db->prepare("
        SELECT d.*, e.email, p.first_name, p.last_name, p.dni, p.birth_date, p.address, p.phone
        FROM doctors d
        JOIN employees e ON d.doctor_id = e.employee_id
        JOIN persons p ON e.person_id = p.person_id
        WHERE d.doctor_id = :id
    ");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}
}