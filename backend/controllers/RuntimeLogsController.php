<?php

require_once __DIR__ . '/../helpers/deploy.php';

class RuntimeLogsController
{
    public function get(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $project = $request->input('project', '');
        $codespace = $request->input('codespace', '');

        if (!$project || !$codespace) {
            $response->error('project and codespace are required', 400);
            return;
        }

        try {
            requireExistingCodespace($userID, $project, $codespace);
        } catch (Exception $e) {
            $response->error($e->getMessage(), 404);
            return;
        }

        $cs = deploy_resolve_codespace($project, $codespace);
        if (!$cs) {
            $response->error('Codespace not found', 404);
            return;
        }

        $logFile = DEPLOY_RUNTIME_LOG_ROOT . '/cs-' . (int) $cs['id'] . '.log';
        if (!file_exists($logFile)) {
            $response->json([
                'success' => true,
                'logs' => '',
                'running' => false,
            ]);
            return;
        }

        $response->json([
            'success' => true,
            'logs' => file_get_contents($logFile),
            'running' => true,
            'updatedAt' => filemtime($logFile),
        ]);
    }
}
