<?php

class ProjectDomainController
{
    public function connect(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $user_id = escape_string($request->input('user_id', ''));
        $domain = strtolower(trim($request->input('domain', '')));
        $domain_type = $request->input('domain_type', 'subdomain');

        if (!$project || !$user_id) {
            $response->json(['error' => 'Invalid request']);
            return;
        }

        $fullDomain = '';
        $customBaseDomain = $request->input('custom_base_domain', '');

        $isSuperAdmin = ($request->userID == 152);

        if ($domain_type === 'custom' && $isSuperAdmin) {
            if (!$customBaseDomain) {
                $response->json(['error' => 'Custom Base Domain fehlt.']);
                return;
            }

            if ($domain && strlen($domain) > 0) {
                $fullDomain = $domain . '.' . $customBaseDomain;
            } else {
                $fullDomain = $customBaseDomain;
            }
        } else {
            if (!$domain) {
                $response->json(['error' => 'Domain ist erforderlich.']);
                return;
            }
            if (!preg_match('/^[a-z0-9-]+$/', $domain)) {
                $response->json(['error' => 'Ungültiges Domain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.']);
                return;
            }
            $fullDomain = $domain . '.sites.control-center.eu';
        }

        $exists = query("SELECT id FROM control_center_project_domains WHERE domain='$fullDomain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $response->json(['error' => 'Domain bereits vergeben.']);
            return;
        }

        $insert = query("INSERT INTO control_center_project_domains (project, domain, user_id) VALUES ('$project', '$fullDomain', '$user_id')");

        if ($insert) {
            $response->json(['success' => true, 'domain' => $fullDomain]);
        } else {
            $response->json(['error' => 'Insert failed']);
        }
    }

    public function get(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));

        if (!$project) {
            $response->json(['error' => 'Invalid request']);
            return;
        }

        $res = query("SELECT * FROM control_center_project_domains WHERE project='$project' LIMIT 1");
        if ($row = fetch_assoc($res)) {
            $response->json(['domain' => $row['domain']]);
        } else {
            $response->json(['domain' => null]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));

        if (!$project) {
            $response->json(['error' => 'Project ID/Link required']);
            return;
        }

        $delete = query("DELETE FROM control_center_project_domains WHERE project='$project'");

        if ($delete) {
            $response->json(['success' => true]);
        } else {
            $response->json(['error' => 'Delete failed']);
        }
    }
}
