<?php
session_start();
include '../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$trainingId = $data['training_id'] ?? null;
$userId = $_SESSION['player_id'] ?? null;

$response = ['success' => false];

if ($trainingId && $userId) {
    // user already registered
    $checkQuery = "SELECT * FROM training_participants WHERE training_id = ? AND player_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $trainingId, $userId);
    $checkStmt->execute();
    $isAlreadyRegistered = $checkStmt->get_result()->num_rows > 0;

    if (!$isAlreadyRegistered) {
        $query = "INSERT INTO training_participants (training_id, player_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $trainingId, $userId);
        if ($stmt->execute()) {
            $response['success'] = true;
        }
    }
}

echo json_encode($response); 