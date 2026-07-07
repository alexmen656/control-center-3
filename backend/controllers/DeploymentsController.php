<?php

class DeploymentsController
{
    public function listAll(Request $request, Response $response): void
    {
        $rows = query("
            SELECT
                d.id, d.codespace_id, d.commit_sha, d.status, d.runtime, d.url,
                d.error_msg, d.created_at, d.ready_at,
                pc.name AS codespace_name, pc.slug AS codespace_slug, pc.icon AS codespace_icon,
                pc.project_id,
                p.name AS project_name, p.link AS project_link, p.icon AS project_icon
            FROM deployments d
            JOIN project_codespaces pc ON d.codespace_id = pc.id
            LEFT JOIN projects p ON pc.project_id = p.projectID
            ORDER BY d.created_at DESC
        ");

        $deployments = [];

        if ($rows) {
            foreach ($rows as $r) {
                $deployments[] = [
                    'id' => (int) $r['id'],
                    'status' => $r['status'],
                    'runtime' => $r['runtime'],
                    'url' => $r['url'],
                    'commit_sha' => $r['commit_sha'],
                    'commit_short' => $r['commit_sha'] ? substr($r['commit_sha'], 0, 7) : null,
                    'error_msg' => $r['error_msg'],
                    'created_at' => $r['created_at'],
                    'ready_at' => $r['ready_at'],
                    'codespace' => [
                        'id' => (int) $r['codespace_id'],
                        'name' => $r['codespace_name'],
                        'slug' => $r['codespace_slug'],
                        'icon' => $r['codespace_icon'] ?: 'code-outline',
                    ],
                    'project' => [
                        'id' => $r['project_id'],
                        'name' => $r['project_name'],
                        'link' => $r['project_link'],
                        'icon' => $r['project_icon'] ?: 'folder-outline',
                    ],
                ];
            }
        }

        $response->json(['success' => true, 'deployments' => $deployments]);
    }
}
