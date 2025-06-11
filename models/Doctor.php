<?php

namespace models;

use core\Model;
use models\Person;
use models\Employee;
use models\UserAccount;
use models\PasswordSetRequest;

class Doctor extends Model {
    protected $table = 'doctors';

public function createDoctor(array $personData, array $doctorData, array $userData): array {
    // 1. Create employee, person, and user account together
    $employeeModel = new Employee();
    $employeeResult = $employeeModel->createWithAccount($personData, $userData['role_id']);

    $employeeId = $employeeResult['employee_id'];
    $username = $employeeResult['username'];
    $link = $employeeResult['setup_link'];

    // 2. Create doctor
    $stmt = $this->db->prepare("
        INSERT INTO {$this->table} (doctor_id, license_no, specialty)
        VALUES (:doctor_id, :license_no, :specialty)
    ");
    $stmt->execute([
        'doctor_id' => $employeeId,
        'license_no' => $doctorData['license_no'],
        'specialty' => $doctorData['specialty']
    ]);

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