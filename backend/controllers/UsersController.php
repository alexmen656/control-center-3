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
            if (query("INSERT INTO control_center_users VALUES(0, '', '$firstName', '$lastName', '$email', '$password', 'false', '$token', 'active')")) {
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
        $firstName = escape_string($request->input('first_name', ''));
        $lastName = escape_string($request->input('last_name', ''));
        $email = escape_string($request->input('email_adress', ''));
        $accountStatus = escape_string($request->input('account_status', ''));

        $updateFields = [
            "firstname='$firstName'",
            "lastname='$lastName'",
            "email='$email'",
            "account_status='$accountStatus'"
        ];

        $password = $request->input('password');
        if (!empty(trim($password ?? ''))) {
            $hashed = password_hash(escape_string($password), PASSWORD_DEFAULT);
            $updateFields[] = "password='$hashed'";
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
