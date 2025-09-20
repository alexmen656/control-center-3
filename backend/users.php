<?php
include 'head.php';

if (isset($_POST['new_user']) && isset($_POST['first_name']) && isset($_POST['email_adress']) && isset($_POST['password'])) {
    $first_name = escape_string($_POST['first_name']);
    $last_name = escape_string($_POST['last_name']);
    $email_adress = escape_string($_POST['email_adress']);
    $password = escape_string($_POST['password']);
    $assigned_project = isset($_POST['assigned_project']) ? escape_string($_POST['assigned_project']) : '';
    
    $password = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(72));
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        // Create user
        if (query("INSERT INTO control_center_users VALUES(0, '', '$first_name', '$last_name', '$email_adress', '$password', 'false', '$token', 'active')")) {
            $user_id = mysqli_insert_id($GLOBALS['con']);
            
            // Assign project if provided
            if (!empty($assigned_project)) {
                query("INSERT INTO user_project_assignments (user_id, project_link, assigned_at) VALUES ('$user_id', '$assigned_project', NOW())");
            }
            
            mysqli_commit($GLOBALS['con']);
            echo echoJson(['success' => true, 'message' => 'User created successfully']);
        } else {
            throw new Exception('Failed to create user');
        }
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo echoJson(['success' => false, 'message' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
    
} elseif (isset($_REQUEST['updateAccountStatus']) && isset($_REQUEST['userID']) && isset($_REQUEST['newStatus'])) {
    $userID = escape_string($_REQUEST['userID']);
    $new_status = escape_string($_REQUEST['newStatus']);

    if (query("UPDATE control_center_users SET account_status='$new_status' WHERE userID='$userID'")) {
        echo "Account status updated";
    }

} elseif (isset($_REQUEST['deactivateUser']) && isset($_REQUEST['userID'])) {
    $userID = escape_string($_REQUEST['userID']);
    if (query("UPDATE control_center_users SET account_status='inactive' WHERE userID='$userID'")) {
        echo "User deaktiviert";
    } else {
        echo "Fehler beim Deaktivieren";
    }
    
} elseif (isset($_REQUEST['deleteUser']) && isset($_REQUEST['userID'])) {
    $userID = escape_string($_REQUEST['userID']);
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        // Delete user assignments first
        query("DELETE FROM user_project_assignments WHERE user_id='$userID'");
        
        // Delete user
        if (query("DELETE FROM control_center_users WHERE userID='$userID'")) {
            mysqli_commit($GLOBALS['con']);
            echo echoJson(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception('Failed to delete user');
        }
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo echoJson(['success' => false, 'message' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
    
} elseif (isset($_REQUEST['assignUserProject']) && isset($_REQUEST['userID'])) {
    $userID = escape_string($_REQUEST['userID']);
    $project = isset($_REQUEST['project']) ? escape_string($_REQUEST['project']) : '';
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        // First remove existing assignments
        query("DELETE FROM user_project_assignments WHERE user_id='$userID'");
        
        // Add new assignment if project is specified
        if (!empty($project)) {
            query("INSERT INTO user_project_assignments (user_id, project_link, assigned_at) VALUES ('$userID', '$project', NOW())");
        }
        
        mysqli_commit($GLOBALS['con']);
        echo echoJson(['success' => true, 'message' => 'Project assignment updated successfully']);
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo echoJson(['success' => false, 'message' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
    
} elseif (isset($_REQUEST['getUserAssignments'])) {
    $assignments = query("
        SELECT 
            upa.user_id,
            upa.project_link,
            p.name as project_name,
            upa.assigned_at
        FROM user_project_assignments upa
        LEFT JOIN projects p ON p.link = upa.project_link
    ");
    
    echo echoJson(['success' => true, 'assignments' => $assignments]);
    
} elseif (isset($_REQUEST['getAllUsers'])) {
    $json = [];
    $labels = [
        "userID",
        "profileImg",
        "firstname",
        "lastname",
        "email",
        "password",
        "login_with_google",
       // "loginToken",
        "account_status"
    ];
    $json['labels'] = $labels;
    $json['data'] = [];
    $users = query("SELECT userID, profileImg, firstname, lastname, email, password, login_with_google, account_status FROM control_center_users");// loginToken,
    foreach ($users as $u) {
        $tr = [];
        foreach ($labels as $col) {
            $tr[] = $u[$col];
        }
        $json['data'][] = $tr;
    }
    echo echoJson($json);
}

?>