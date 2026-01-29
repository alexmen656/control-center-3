<?php

interface Middleware
{
    public function handle(Request $request, Response $response, callable $next): void;
}

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Response $response, callable $next): void
    {
        global $jwt_secret;
        $token = $request->bearerToken();

        if (!$token) {
            $response->error('No valid token', 401);
            return;
        }

        $payload = SimpleJWT::verify($token, $jwt_secret);

        if (!$payload || empty($payload['sub'])) {
            $response->error('No valid token', 401);
            return;
        }

        $request->userID = intval($payload['sub']);
        $next($request, $response);
    }
}

class PermissionMiddleware implements Middleware
{
    private string $resource;
    private string $action;

    public function __construct(string $resource, string $action)
    {
        $this->resource = $resource;
        $this->action = $action;
    }

    public function handle(Request $request, Response $response, callable $next): void
    {
        require_once __DIR__ . '/../permission_middleware.php';

        $projectLink = $request->input('project');
        if (!$projectLink) {
            $response->error('Project parameter is required', 400);
            return;
        }

        $project = getProjectByLink(escape_string($projectLink));
        if (!$project) {
            $response->error('Project not found', 404);
            return;
        }

        if (!checkUserPermission($request->userID, $project['projectID'], $this->resource, $this->action)) {
            $response->error("You don't have permission to {$this->action} {$this->resource}", 403);
            return;
        }

        $next($request, $response);
    }
}
