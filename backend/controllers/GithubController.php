<?php

class GithubController
{
    private function getGitHubCredentials($project, $userID)
    {
        $tokenResult = query("SELECT github_token FROM control_center_github_tokens WHERE userID = $userID");

        if (!$tokenResult || mysqli_num_rows($tokenResult) == 0) {
            throw new Exception("No GitHub token found for user");
        }

        $tokenData = fetch_assoc($tokenResult);
        $repoResult = query("SELECT repo_full_name FROM control_center_project_repos WHERE project = '" . escape_string($project) . "'");

        if (!$repoResult || mysqli_num_rows($repoResult) == 0) {
            throw new Exception("No repository found for project: " . $project);
        }

        $repoData = fetch_assoc($repoResult);
        $repoParts = explode('/', $repoData['repo_full_name']);

        if (count($repoParts) !== 2) {
            throw new Exception("Invalid repository format");
        }

        return [
            'token' => $tokenData['github_token'],
            'owner' => $repoParts[0],
            'repo' => $repoParts[1]
        ];
    }

    private function githubRequest($token, $owner, $repo, $endpoint, $method = 'GET', $data = null)
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/" . ltrim($endpoint, '/');

        $headers = [
            'Authorization: token ' . $token,
            'User-Agent: ControlCenter-App/1.0',
            'Accept: application/vnd.github.v3+json'
        ];

        $context_options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 30
            ]
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $headers[] = 'Content-Type: application/json';
            $context_options['http']['content'] = json_encode($data);
            $context_options['http']['header'] = implode("\r\n", $headers);
        }

        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception("GitHub API request failed: Unable to connect");
        }

        $http_response_header = $http_response_header ?? [];
        $httpCode = 200;

        if (!empty($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception("GitHub API request failed: HTTP {$httpCode} - {$response}");
        }
    }

    private function getCommits($token, $owner, $repo, $per_page = 10)
    {
        return $this->githubRequest($token, $owner, $repo, "commits?per_page={$per_page}");
    }

    private function getRepoStatus($token, $owner, $repo)
    {
        $commits = $this->getCommits($token, $owner, $repo, 1);
        $branches = $this->githubRequest($token, $owner, $repo, 'branches');

        return [
            'latest_commit' => $commits[0] ?? null,
            'branches' => $branches,
            'default_branch' => $branches[0]['name'] ?? 'main'
        ];
    }

    private function createCommit($message, $files = [])
    {
        return [
            'sha' => substr(md5($message . time()), 0, 40),
            'message' => $message,
            'author' => [
                'name' => 'Fringelo User',
                'email' => 'user@example.com'
            ],
            'created_at' => date('c')
        ];
    }

    public function api(Request $request, Response $response): void
    {
        try {
            $project = $request->input('project');
            if (!$project) {
                throw new Exception('Project parameter is required');
            }

            $userID = $request->userID;
            $credentials = $this->getGitHubCredentials($project, $userID);
            $token = $credentials['token'];
            $owner = $credentials['owner'];
            $repo = $credentials['repo'];

            if ($request->method === 'GET') {
                $action = $request->input('action');
                if ($action) {
                    switch ($action) {
                        case 'commits':
                            $commits = $this->getCommits($token, $owner, $repo);
                            $response->json(['success' => true, 'commits' => $commits]);
                            break;

                        case 'status':
                            $status = $this->getRepoStatus($token, $owner, $repo);
                            $response->json(['success' => true, 'status' => $status]);
                            break;

                        default:
                            throw new Exception('Unknown action');
                    }
                } else {
                    $commits = $this->getCommits($token, $owner, $repo);
                    $response->json(['success' => true, 'commits' => $commits]);
                }
            } else if ($request->method === 'POST') {
                $action = $request->input('action');

                if ($action) {
                    switch ($action) {
                        case 'commit':
                            $message = $request->input('message', '');
                            $files = $request->input('files', []);

                            if (empty($message)) {
                                throw new Exception('Commit message is required');
                            }

                            $commit = $this->createCommit($message, $files);
                            $response->json(['success' => true, 'commit' => $commit]);
                            break;

                        default:
                            throw new Exception('Unknown action');
                    }
                } else {
                    throw new Exception('Action parameter is required');
                }
            }
        } catch (Exception $e) {
            $response->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function repos(Request $request, Response $response): void
    {
        $user_id = $request->userID;
        if (!$user_id) {
            $response->json(['error' => 'No user_id provided']);
            return;
        }

        $res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if ($row = fetch_assoc($res)) {
            $token = $row['github_token'];
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $repos = @file_get_contents('https://api.github.com/user/repos?per_page=100', false, $context);
            if ($repos !== false) {
                $response->json(json_decode($repos, true));
            } else {
                $response->json(['error' => 'Could not fetch repos']);
            }
        } else {
            $response->json(['error' => 'No token found']);
        }
    }

    public function tokenInfo(Request $request, Response $response): void
    {
        $userID = intval($request->userID);
        if ($userID > 0) {
            $res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
            if ($res && $row = fetch_assoc($res)) {
                $token = $row['github_token'];
                $opts = [
                    'http' => [
                        'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\n"
                    ]
                ];
                $context = stream_context_create($opts);
                $info = @file_get_contents('https://api.github.com/user', false, $context);
                if ($info) {
                    $response->json(json_decode($info, true));
                    return;
                }
            }
        }
        $response->json(['error' => 'not_connected']);
    }

    public function tokenStatus(Request $request, Response $response): void
    {
        $userID = intval($request->userID);
        if ($userID > 0) {
            $res = query("SELECT id FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) {
                $response->json(['connected' => true]);
                return;
            }
        }
        $response->json(['connected' => false]);
    }
}
