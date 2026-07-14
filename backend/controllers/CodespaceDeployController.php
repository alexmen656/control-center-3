<?php

require_once __DIR__ . '/../helpers/deploy.php';

class CodespaceDeployController
{
    private function resolveCodespace(Request $request): ?array
    {
        $project = $request->input('project', '');
        $codespace = $request->input('codespace', 'main');

        if ($project === '') {
            return null;
        }

        $cs = deploy_resolve_codespace($project, $codespace);
        if (!$cs || !checkUserProjectPermission($request->userID, $cs['project_id'])) {
            return null;
        }

        return $cs;
    }

    public function trigger(Request $request, Response $response): void
    {
        $codespace = $this->resolveCodespace($request);
        if (!$codespace) {
            $response->error('Codespace not found or no permission', 404);
            return;
        }

        $codespaceId = (int) $codespace['id'];
        $bare = deploy_bare_repo($codespaceId);
        $commit = '';
        if (is_dir($bare)) {
            $out = trim((string) shell_exec('git --git-dir=' . escapeshellarg($bare) . ' rev-parse HEAD 2>/dev/null'));
            if ($out !== '') {
                $commit = $out;
            }
        }

        if ($commit === '') {
            $response->error('No committed code to deploy. Commit and push changes first.', 400);
            return;
        }

        $config = deploy_get_config($codespaceId);
        $runtime = ($config && $config['runtime'] === 'node') ? 'node' : 'static';
        $deploymentId = deploy_create($codespaceId, $commit, $runtime);

        $response->json([
            'success' => true,
            'deployment' => [
                'uid' => (string) $deploymentId,
                'url' => deploy_host($codespaceId),
                'readyState' => 'QUEUED',
                'created' => time() * 1000,
                'inspectorUrl' => 'https://api.fringelo.com/v2/deploy-logs?deployment=' . $deploymentId . '&sig=' . deploy_log_sig($deploymentId)
            ]
        ]);
    }

    public function list(Request $request, Response $response): void
    {
        $codespace = $this->resolveCodespace($request);
        if (!$codespace) {
            $response->error('Codespace not found or no permission', 404);
            return;
        }

        $response->json([
            'success' => true,
            'deployments' => deploy_list_for_frontend((int) $codespace['id'])
        ]);
    }
}
