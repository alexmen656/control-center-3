<?php
include 'head.php';
header('Content-Type: application/json');

$table = 'control_center_notepad';

// POST: Create, Update, or Delete a note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action type
    $action = isset($_POST['action']) ? $_POST['action'] : 'create';
    
    // Debug incoming data
    error_log("POST data received: " . print_r($_POST, true) . ", Action: " . $action);
    
    // Handle different actions
    switch ($action) {
        case 'delete':
            // Delete a note
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Invalid note ID."
                ]);
                exit;
            }
            
            $sql = "DELETE FROM $table WHERE id = $id";
            $result = query($sql);
            
            if ($result) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Note deleted successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Could not delete note. SQL Error: " . mysqli_error($conn)
                ]);
            }
            break;
            
        case 'update':
            // Update an existing note
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $header = isset($_POST['header']) ? escape_string($_POST['header']) : "";
            $image = isset($_POST['image']) ? escape_string($_POST['image']) : "";
            $content = isset($_POST['content']) ? $_POST['content'] : "";
            $content = escape_string($content);
            
            if ($id <= 0) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Invalid note ID."
                ]);
                exit;
            }
            
            // Validate data: ensure we have at least a header or content
            if (empty($header) && empty($content)) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Note must have either a header or content."
                ]);
                exit;
            }
            
            $sql = "UPDATE $table SET 
                    header = '$header', 
                    image = '$image', 
                    content = '$content', 
                    updated_at = NOW() 
                    WHERE id = $id";
                    
            $result = query($sql);
            
            if ($result) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Note updated successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Could not update note. SQL Error: " . mysqli_error($conn)
                ]);
            }
            break;
            
        default:
            // Create a new note (default action)
            $header = isset($_POST['header']) ? escape_string($_POST['header']) : "";
            $image = isset($_POST['image']) ? escape_string($_POST['image']) : "";
            $content = isset($_POST['content']) ? $_POST['content'] : "";
            $content = escape_string($content);
            
            // Debug escaped content
            error_log("Content length before saving: " . strlen($content));
            
            // Validate data: ensure we have at least a header or content
            if (empty($header) && empty($content)) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Note must have either a header or content."
                ]);
                exit;
            }
            
            // Insert new note record
            $sql = "INSERT INTO $table (header, image, content, created_at) 
                    VALUES ('$header', '$image', '$content', NOW())";
            $result = query($sql);
            
            if ($result) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Note saved successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Could not save note. SQL Error: " . mysqli_error($conn)
                ]);
            }
            break;
    }
    
    exit;
}

// GET: Retrieve all notes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, header, image, content FROM $table ORDER BY created_at DESC";
    $result = query($sql);
    $notes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notes[] = $row;
    }
    echo json_encode(["status" => "success", "notes" => $notes]);
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid request."]);
?>