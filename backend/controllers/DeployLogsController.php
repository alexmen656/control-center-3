<?php

require_once __DIR__ . '/../helpers/deploy.php';

class DeployLogsController
{
    public function show(Request $request, Response $response): void
    {
        $deploymentId = (int) $request->input('deployment', 0);
        $sig = (string) $request->input('sig', '');

        header('Content-Type: text/plain; charset=utf-8');

        if ($deploymentId <= 0 || !hash_equals(deploy_log_sig($deploymentId), $sig)) {
            http_response_code(403);
            echo 'forbidden';
            return;
        }

        $dep = deploy_get($deploymentId);
        if (!$dep) {
            http_response_code(404);
            echo 'deployment not found';
            return;
        }

        echo 'Deployment #' . $deploymentId . ' — status: ' . $dep['status'] . "\n";
        echo 'URL: ' . ($dep['url'] ?: '(pending)') . "\n";
        echo str_repeat('-', 60) . "\n";

        $logFile = $dep['build_log'];
        if ($logFile && file_exists($logFile)) {
            echo file_get_contents($logFile);
        } else {
            echo '(no build log yet)';
        }
    }
}
