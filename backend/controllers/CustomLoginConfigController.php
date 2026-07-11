<?php

class CustomLoginConfigController
{
    public function get(Request $request, Response $response): void
    {
        $domain = escape_string($request->input('domain', ''));

        if (empty($domain)) {
            $response->json(['success' => false, 'message' => 'Domain parameter required']);
            return;
        }

        $result = query("SELECT * FROM custom_login_domains WHERE domain='$domain' AND is_enabled=1");

        if ($row = fetch_assoc($result)) {
            $projectResult = query("SELECT name FROM projects WHERE projectID='{$row['projectID']}'");
            $project = fetch_assoc($projectResult);

            $response->json([
                'success' => true,
                'config' => [
                    'domain' => $row['domain'],
                    'primary_color' => $row['primary_color'],
                    'logo_url' => $row['logo_url'],
                    'company_name' => $row['company_name'] ?: ($project['name'] ?? 'Fringelo'),
                    'project_name' => $project['name'] ?? 'Fringelo'
                ]
            ]);
        } else {
            $response->json(['success' => false, 'message' => 'Domain nicht gefunden oder nicht aktiviert']);
        }
    }
}
