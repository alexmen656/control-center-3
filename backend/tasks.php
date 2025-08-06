<?php
include 'head.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['initTasks'])) {
    $sql = "CREATE TABLE IF NOT EXISTS project_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                projectID INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                status ENUM('todo', 'in_progress', 'completed') DEFAULT 'todo',
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                assigned_to INT,
                created_by INT NOT NULL,
                due_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (projectID) REFERENCES projects(projectID) ON DELETE CASCADE,
                FOREIGN KEY (assigned_to) REFERENCES control_center_users(userID) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES control_center_users(userID) ON DELETE CASCADE
            )";

    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Tasks table created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create tasks table']);
    }
} elseif (isset($_POST['getTasks']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);

    if ($projectID) {
        $tasks = query("SELECT t.*, 
                               c.firstname as creator_firstname, c.lastname as creator_lastname,
                               a.firstname as assigned_firstname, a.lastname as assigned_lastname
                               FROM project_tasks t 
                               LEFT JOIN control_center_users c ON t.created_by = c.userID 
                               LEFT JOIN control_center_users a ON t.assigned_to = a.userID 
                               WHERE t.projectID = '$projectID' 
                               ORDER BY t.created_at DESC");

        $tasksList = [];
        while ($task = fetch_assoc($tasks)) {
            $task['creator_name'] = $task['creator_firstname'] . ' ' . $task['creator_lastname'];
            $task['assigned_name'] = $task['assigned_firstname'] ? $task['assigned_firstname'] . ' ' . $task['assigned_lastname'] : null;
            $tasksList[] = $task;
        }

        echo json_encode(['success' => true, 'tasks' => $tasksList]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
    }
} elseif (isset($_POST['createTask']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $title = escape_string($_POST['title']);
    $description = isset($_POST['description']) ? escape_string($_POST['description']) : '';
    $priority = isset($_POST['priority']) ? escape_string($_POST['priority']) : 'medium';
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] != '' ? escape_string($_POST['assigned_to']) : 'NULL';
    $due_date = isset($_POST['due_date']) && $_POST['due_date'] != '' ? "'" . escape_string($_POST['due_date']) . "'" : 'NULL';

    $projectID = getProjectID($projectName);

    if ($projectID && $title) {
        $insertQuery = "INSERT INTO project_tasks (projectID, title, description, priority, assigned_to, created_by, due_date) 
                               VALUES ('$projectID', '$title', '$description', '$priority', $assigned_to, '$userID', $due_date)";

        if (query($insertQuery)) {
            echo json_encode(['success' => true, 'message' => 'Task created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create task']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid project or missing title']);
    }
} elseif (isset($_POST['updateTask'])) {
    $taskID = escape_string($_POST['taskID']);
    $title = isset($_POST['title']) ? escape_string($_POST['title']) : null;
    $description = isset($_POST['description']) ? escape_string($_POST['description']) : null;
    $status = isset($_POST['status']) ? escape_string($_POST['status']) : null;
    $priority = isset($_POST['priority']) ? escape_string($_POST['priority']) : null;
    $assigned_to = isset($_POST['assigned_to']) ? escape_string($_POST['assigned_to']) : null;
    $due_date = isset($_POST['due_date']) ? escape_string($_POST['due_date']) : null;

    $updates = [];
    if ($title !== null) $updates[] = "title = '$title'";
    if ($description !== null) $updates[] = "description = '$description'";
    if ($status !== null) $updates[] = "status = '$status'";
    if ($priority !== null) $updates[] = "priority = '$priority'";
    if ($assigned_to !== null) $updates[] = $assigned_to != '' ? "assigned_to = '$assigned_to'" : "assigned_to = NULL";
    if ($due_date !== null) $updates[] = $due_date != '' ? "due_date = '$due_date'" : "due_date = NULL";

    if (!empty($updates)) {
        $updateQuery = "UPDATE project_tasks SET " . implode(', ', $updates) . " WHERE id = '$taskID'";

        if (query($updateQuery)) {
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update task']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
    }
} elseif (isset($_POST['deleteTask'])) {
    $taskID = escape_string($_POST['taskID']);

    if (query("DELETE FROM project_tasks WHERE id = '$taskID'")) {
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete task']);
    }
} elseif (isset($_POST['getProjectUsers']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);

    if ($projectID) {
        $users = query("SELECT u.userID, u.firstname, u.lastname, u.email 
                               FROM control_center_users u 
                               JOIN control_center_user_projects up ON u.userID = up.userID 
                               WHERE up.projectID = '$projectID'");

        $usersList = [];
        while ($user = fetch_assoc($users)) {
            $user['full_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $usersList[] = $user;
        }

        echo json_encode(['success' => true, 'users' => $usersList]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
