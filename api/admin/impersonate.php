<?php
require_once '../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$schoolId = $input['school_id'] ?? null;

if (!$schoolId) { http_response_code(400); echo json_encode(['error' => 'school_id required']); exit; }

$result = SuperAdminMiddleware::impersonateSchool($schoolId);
echo json_encode($result);
?>