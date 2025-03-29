<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header("Content-Type: application/json");
include 'config.php';

function generateVerificationId($length = 16)
{
    return bin2hex(random_bytes($length / 2));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Get the input data
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'];
    $avatar = $data['avatar'];

    // Check if the username is already taken
    $sql = "SELECT * FROM control_center_chat_app_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username is already taken
        echo json_encode(['status' => 'error', 'message' => 'Username is already taken']);
    } else {
        // Generate a verification ID
        $verificationId = generateVerificationId();

        // Save the user to the database
        $sql = "INSERT INTO control_center_chat_app_users (username, verification_id, avatar) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $verificationId, $avatar);

        if ($stmt->execute() === TRUE) {
            echo json_encode(['status' => 'success', 'verification_id' => $verificationId]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>