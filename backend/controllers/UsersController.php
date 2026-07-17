<?php

class UsersController
{
    /**
     * GET /v2/users
     */
    public function getAll(Request $request, Response $response): void
    {
        $labels = ["userID", "profileImg", "firstname", "lastname", "email", "password", "login_with_google", "account_status"];
        $data = [];

        $users = query("SELECT userID, profileImg, firstname, lastname, email, password, login_with_google, account_status FROM control_center_users");

        foreach ($users as $u) {
            $tr = [];
            foreach ($labels as $col) {
                if ($col === 'profileImg') {
                    $tr[] = $this->resolveProfileImage($u);
                } else {
                    $tr[] = $u[$col];
                }
            }
            $data[] = $tr;
        }

        $response->json(['labels' => $labels, 'data' => $data]);
    }

    /**
     * POST /v2/users
     */
    public function create(Request $request, Response $response): void
    {
        $firstName = escape_string($request->input('first_name', ''));
        $lastName = escape_string($request->input('last_name', ''));
        $email = escape_string($request->input('email_adress', ''));
        $password = escape_string($request->input('password', ''));
        $assignedProject = escape_string($request->input('assigned_project', ''));

        if (empty($firstName) || empty($email) || empty($password)) {
            $response->error('first_name, email_adress, and password are required', 400);
            return;
        }

        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(72));

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            if (query("INSERT INTO control_center_users (profileImg, firstname, lastname, email, password, login_with_google, loginToken, account_status) VALUES ('', '$firstName', '$lastName', '$email', '$password', 'false', '$token', 'active')")) {
                $userId = mysqli_insert_id($GLOBALS['con']);

                if (!empty($assignedProject)) {
                    query("INSERT INTO user_project_assignments (user_id, project_link, assigned_at) VALUES ('$userId', '$assignedProject', NOW())");
                }

                mysqli_commit($GLOBALS['con']);
                $response->success(['userID' => $userId], 'User created successfully');
            } else {
                throw new Exception('Failed to create user');
            }
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->error($e->getMessage(), 500);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * PUT /v2/users/{id}
     */
    public function update(Request $request, Response $response): void
    {
        $userID = escape_string($request->params['id']);

        $updateFields = [];

        $firstName = trim($request->input('first_name', ''));
        if ($firstName !== '') {
            $updateFields[] = "firstname='" . escape_string($firstName) . "'";
        }

        $lastName = trim($request->input('last_name', ''));
        if ($lastName !== '') {
            $updateFields[] = "lastname='" . escape_string($lastName) . "'";
        }

        $email = trim($request->input('email_adress', ''));
        if ($email !== '') {
            $updateFields[] = "email='" . escape_string($email) . "'";
        }

        $accountStatus = trim($request->input('account_status', ''));
        if ($accountStatus !== '') {
            $updateFields[] = "account_status='" . escape_string($accountStatus) . "'";
        }

        $password = $request->input('password');
        if (!empty(trim($password ?? ''))) {
            $hashed = password_hash(escape_string($password), PASSWORD_DEFAULT);
            $updateFields[] = "password='$hashed'";
        }

        if (empty($updateFields)) {
            $response->error('No fields to update', 400);
            return;
        }

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            $sql = "UPDATE control_center_users SET " . implode(', ', $updateFields) . " WHERE userID='$userID'";
            if (query($sql)) {
                mysqli_commit($GLOBALS['con']);
                $response->success([], 'User updated successfully');
            } else {
                throw new Exception('Failed to update user');
            }
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->error($e->getMessage(), 500);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * DELETE /v2/users/{id}
     */
    public function delete(Request $request, Response $response): void
    {
        $userID = escape_string($request->params['id']);

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            query("DELETE FROM user_project_assignments WHERE user_id='$userID'");

            if (query("DELETE FROM control_center_users WHERE userID='$userID'")) {
                mysqli_commit($GLOBALS['con']);
                $response->success([], 'User deleted successfully');
            } else {
                throw new Exception('Failed to delete user');
            }
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->error($e->getMessage(), 500);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * PUT /v2/users/{id}/deactivate
     */
    public function deactivate(Request $request, Response $response): void
    {
        $userID = escape_string($request->params['id']);

        if (query("UPDATE control_center_users SET account_status='inactive' WHERE userID='$userID'")) {
            $response->success([], 'User deaktiviert');
        } else {
            $response->error('Fehler beim Deaktivieren', 500);
        }
    }

    /**
     * PUT /v2/users/{id}/status
     */
    public function updateStatus(Request $request, Response $response): void
    {
        $userID = escape_string($request->params['id']);
        $newStatus = escape_string($request->input('newStatus', ''));

        if (empty($newStatus)) {
            $response->error('newStatus is required', 400);
            return;
        }

        if (query("UPDATE control_center_users SET account_status='$newStatus' WHERE userID='$userID'")) {
            $response->success([], 'Account status updated');
        } else {
            $response->error('Failed to update status', 500);
        }
    }

    /**
     * POST /v2/users/{id}/project
     */
    public function assignProject(Request $request, Response $response): void
    {
        $userID = escape_string($request->params['id']);
        $project = escape_string($request->input('project', ''));

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            query("DELETE FROM user_project_assignments WHERE user_id='$userID'");

            if (!empty($project)) {
                query("INSERT INTO user_project_assignments (user_id, project_link, assigned_at) VALUES ('$userID', '$project', NOW())");

                $projectQuery = query("SELECT projectID FROM projects WHERE link='$project'");
                if ($projectQuery && mysqli_num_rows($projectQuery) > 0) {
                    $projectData = fetch_assoc($projectQuery);
                    $projectID = $projectData['projectID'];

                    $permissionCheck = query("SELECT * FROM control_center_user_projects WHERE userID='$userID' AND projectID='$projectID'");
                    if (mysqli_num_rows($permissionCheck) == 0) {
                        query("INSERT INTO control_center_user_projects (userID, projectID, role) VALUES ('$userID', '$projectID', 1)");
                    }
                }
            }

            mysqli_commit($GLOBALS['con']);
            $response->success([], 'Project assignment updated successfully');
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->error($e->getMessage(), 500);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * GET /v2/users/assignments
     */
    public function getAssignments(Request $request, Response $response): void
    {
        $result = query("
            SELECT
                upa.user_id,
                upa.project_link,
                p.name as project_name,
                upa.assigned_at
            FROM user_project_assignments upa
            LEFT JOIN projects p ON p.link = upa.project_link
        ");

        $assignments = [];
        while ($row = fetch_assoc($result)) {
            $assignments[] = $row;
        }

        $response->json(['success' => true, 'assignments' => $assignments]);
    }

    public function getMe(Request $request, Response $response): void
    {
        $userID = intval($request->userID);

        $result = query("SELECT * FROM control_center_users WHERE userID='$userID'");
        if (!$result || mysqli_num_rows($result) !== 1) {
            $response->error('User not found', 404);
            return;
        }

        $data = fetch_assoc($result);

        $json = [];
        $json['profileImg'] = $this->resolveProfileImage($data);
        $json['firstName'] = $data['firstname'];
        $json['lastName'] = $data['lastname'];
        $json['email'] = $data['email'];
        $json['userID'] = $data['userID'];

        if ($data['login_with_google'] == 'true') {
            $json['login_with_google'] = true;
        } elseif ($data['login_with_google'] == 'false') {
            $json['login_with_google'] = false;
        } else {
            $json['login_with_google'] = $data['login_with_google'];
        }

        $json['accountStatus'] = $data['account_status'];
        $json['email_2fa_enabled'] = $data['email_2fa_enabled'] != '0';
        $json['isAdmin'] = $data['is_admin'] != '0';

        $response->json($json);
    }

    public function updateMe(Request $request, Response $response): void
    {
        $userID = intval($request->userID);

        $updateFields = [];

        $firstName = trim($request->input('firstName', ''));
        if ($firstName !== '') {
            $updateFields[] = "firstname='" . escape_string($firstName) . "'";
        }

        $lastName = trim($request->input('lastName', ''));
        if ($lastName !== '') {
            $updateFields[] = "lastname='" . escape_string($lastName) . "'";
        }

        $email = trim($request->input('email', ''));
        if ($email !== '') {
            $updateFields[] = "email='" . escape_string($email) . "'";
        }

        if (empty($updateFields)) {
            $response->error('No fields to update', 400);
            return;
        }

        $sql = "UPDATE control_center_users SET " . implode(', ', $updateFields) . " WHERE userID='$userID'";
        if (query($sql)) {
            $response->success([], 'Profile updated');
        } else {
            $response->error('Failed to update profile', 500);
        }
    }

    public function updateMyProfileImage(Request $request, Response $response): void
    {
        $userID = intval($request->userID);

        $baseData = $request->input('data');
        $fileName = $request->input('name');

        if (empty($baseData) || empty($fileName)) {
            $response->error('data and name are required', 400);
            return;
        }

        $safeName = escape_string($fileName);
        query("UPDATE control_center_users SET profileImg='$safeName' WHERE userID='$userID'");

        createFile(__DIR__ . '/../images/profileImages/' . $fileName, $baseData, 0777);

        $response->success([], 'Profile image updated');
    }

    public function updateMyLoginWithGoogle(Request $request, Response $response): void
    {
        $userID = intval($request->userID);

        $newValue = escape_string($request->input('newValue', ''));

        if (query("UPDATE control_center_users SET login_with_google='$newValue' WHERE userID='$userID'")) {
            $response->success([], 'Login with Google updated');
        } else {
            $response->error('Failed to update login with Google', 500);
        }
    }

    public function updateMyEmail2FA(Request $request, Response $response): void
    {
        $userID = intval($request->userID);

        $newValue = $request->input('newValue') === 'true' ? '1' : '0';

        if (query("UPDATE control_center_users SET email_2fa_enabled='$newValue' WHERE userID='$userID'")) {
            $response->success([], 'Email 2FA updated');
        } else {
            $response->error('Failed to update email 2FA', 500);
        }
    }

    // ── Private Helpers ─────────────────────────────────────

    private function resolveProfileImage(array $user): string
    {
        $img = $user['profileImg'];

        if ($img != "avatar" && $img != "google" && $img != "" && $img != null) {
            $imagePath = __DIR__ . '/../images/profileImages/' . $img;
            if (file_exists($imagePath)) {
                return file_get_contents($imagePath);
            }
            return $img;
        }

        if ($img == "google") {
            $userID = $user['userID'];
            $select = query("SELECT * FROM control_center_google_profile_images WHERE userID=$userID");
            if (mysqli_num_rows($select) == 1) {
                return fetch_assoc($select)['image'];
            }
            return $img;
        }

        return 'avatar';
    }
}
