<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use core\Router;
use controllers\PatientController;
use controllers\DoctorController;
use controllers\AppointmentController;
use controllers\ClinicalFileController;
use controllers\PrescriptionController;
use controllers\AuthController;
use controllers\UserController;
use controllers\AuthTokenController;
use controllers\EmployeeController;

$router = new Router();

$router->get('/patients', [PatientController::class, 'index']);
$router->get('/patients/show', [PatientController::class, 'show']);
$router->get('/patients/create', [PatientController::class, 'create']);
$router->post('/patients/store', [PatientController::class, 'store']);
$router->get('/patients/edit', [PatientController::class, 'edit']);
$router->post('/patients/update', [PatientController::class, 'update']);
$router->get('/patients/delete', [PatientController::class, 'delete']);


$router->get('/doctors', [DoctorController::class, 'index']);
$router->get('/doctors/show', [DoctorController::class, 'show']);
$router->post('/doctors/store', [DoctorController::class, 'store']);
$router->post('/doctors/update', [DoctorController::class, 'update']);
$router->post('/doctors/employment', [DoctorController::class, 'employment']);


$router->get('/appointments', [AppointmentController::class, 'index']);
$router->get('/appointments/show', [AppointmentController::class, 'show']);
$router->post('/appointments/store', [AppointmentController::class, 'store']);
$router->post('/appointments/update', [AppointmentController::class, 'update']);
$router->get('/appointments/delete', [AppointmentController::class, 'delete']);


$router->get('/clinical/show', [ClinicalFileController::class, 'show']);
$router->post('/clinical/store', [ClinicalFileController::class, 'store']);
$router->get('/clinical/notes', [ClinicalFileController::class, 'notes']);
$router->post('/clinical/notes/add', [ClinicalFileController::class, 'addNote']);
$router->get('/clinical/notes/all', [ClinicalFileController::class, 'allNotes']);
$router->post('/clinical/notes/update', [ClinicalFileController::class, 'updateNote']);
$router->get('/clinical/notes/delete', [ClinicalFileController::class, 'deleteNote']);


$router->get('/prescriptions', [PrescriptionController::class, 'index']);
$router->get('/prescriptions/show', [PrescriptionController::class, 'show']);
$router->post('/prescriptions/store', [PrescriptionController::class, 'store']);
$router->post('/prescriptions/additem', [PrescriptionController::class, 'addItem']);
$router->get('/prescriptions/delete', [PrescriptionController::class, 'delete']);


$router->post('/auth/send-link', [AuthController::class, 'sendPasswordSetupLink']);
$router->post('/auth/set-password', [AuthController::class, 'setPassword']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

$router->post('/user/pass', [UserController::class, 'pass']);

$router->post('/employee/store', [EmployeeController::class, 'store']);
$router->get('/employee/show', [EmployeeController::class, 'show']);
$router->post('/employee/update', [EmployeeController::class, 'update']);
$router->post('/employee/change', [EmployeeController::class, 'change']);
$router->post('/employee/delete', [EmployeeController::class, 'delete']);
$router->get('/employees', [EmployeeController::class, 'index']);


$basePath = '/HospiCareDev/BACKEND/public';
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cleanUri = str_replace($basePath, '', $uriPath);

$router->dispatch($cleanUri, $_SERVER['REQUEST_METHOD']);