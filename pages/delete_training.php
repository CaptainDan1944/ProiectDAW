<?php
session_start();
include '../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$trainingId = $data['training_id'] ?? null;
$userId = $_SESSION['player_id'] ?? null;

$response = ['success' => false];

if ($trainingId && $userId) {
    // Check if the user is the creator
    $checkQuery = "SELECT trainer_id FROM training_sessions WHERE training_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $trainingId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $training = $result->fetch_assoc();

    if ($training && $training['trainer_id'] == $userId) {
        // Delete participants
        $deleteParticipantsQuery = "DELETE FROM training_participants WHERE training_id = ?";
        $deleteParticipantsStmt = $conn->prepare($deleteParticipantsQuery);
        $deleteParticipantsStmt->bind_param("i", $trainingId);
        $deleteParticipantsStmt->execute();

        // Delete training
        $deleteTrainingQuery = "DELETE FROM training_sessions WHERE training_id = ?";
        $deleteTrainingStmt = $conn->prepare($deleteTrainingQuery);
        $deleteTrainingStmt->bind_param("i", $trainingId);
        if ($deleteTrainingStmt->execute()) {
            $response['success'] = true;

            // Placeholder for sending emails
            // foreach ($participants as $participant) {
            //     mail($participant['email'], "Training Cancelled", "The training has been cancelled.");
            // }
        }
    }
}

echo json_encode($response); 