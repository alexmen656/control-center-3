<?php

require_once __DIR__ . '/../helpers/cloudflare.php';

class CodespaceDomainsController
{
    public function get(Request $request, Response $response): void
    {
        $codespace = $this->requireCodespace($request, $response);
        if (!$codespace) return;

        $codespaceId = (int) $codespace['id'];
        $result = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespaceId' LIMIT 1");

        $response->json(['domain' => fetch_assoc($result) ?: null]);
    }

    public function connect(Request $request, Response $response): void
    {
        $codespace = $this->requireCodespace($request, $response);
        if (!$codespace) return;

        $codespaceId = (int) $codespace['id'];
        $userID = $request->userID;

        $subdomain = strtolower(trim($request->input('subdomain', '')));
        $is_main = $request->input('is_main') === 'true' || $request->input('is_main') === true;

        if (!$is_main && (!$subdomain || !preg_match('/^[a-z0-9-]+$/', $subdomain))) {
            $response->error('Ungültiges Subdomain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.', 400);
            return;
        }

        $project_id = $codespace['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            $response->error('Projekt nicht gefunden', 404);
            return;
        }

        $project_link = $projectInfoRow['link'];

        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if (!$projectDomainRow = fetch_assoc($projectDomainResult)) {
            $response->error('Projekt hat keine Domain konfiguriert. Bitte zuerst in den Projekt-Einstellungen eine Domain einrichten.', 400);
            return;
        }

        $base_domain = $projectDomainRow['domain'];

        if ($is_main) {
            $full_domain = $base_domain;

            $existingMainResult = query("
                SELECT cd.id FROM codespace_domains cd
                JOIN project_codespaces pc ON cd.codespace_id = pc.id
                WHERE pc.project_id = '$project_id' AND cd.is_main = 1 AND cd.codespace_id != '$codespaceId'
            ");
            if (mysqli_num_rows($existingMainResult) > 0) {
                $response->error('Ein anderer Codespace verwendet bereits die Haupt-Domain. Bitte zuerst die Haupt-Domain des anderen Codespaces entfernen.', 409);
                return;
            }

            $webBuilderCheck = query("
                SELECT id FROM web_builder_domains
                WHERE projectID = '$project_link' AND domain = '$base_domain'
            ");
            if (mysqli_num_rows($webBuilderCheck) > 0) {
                $response->error('Die Main Domain wird bereits vom Web Builder verwendet. Bitte zuerst dort die Main Domain entfernen.', 409);
                return;
            }
        } else {
            $full_domain = $subdomain . '.' . $base_domain;
        }

        $exists = query("SELECT id FROM codespace_domains WHERE domain='$full_domain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $response->error('Domain bereits vergeben.', 409);
            return;
        }

        query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceId'");

        $insert = query("INSERT INTO codespace_domains (codespace_id, domain, is_main, user_id) VALUES ('$codespaceId', '$full_domain', " . ($is_main ? 1 : 0) . ", '$userID')");

        if (!$insert) {
            $response->error('Fehler beim Speichern der Domain', 500);
            return;
        }

        $cnameTarget = 'apps.fringelo.com';
        $cloudflareResult = cloudflare_createCNAMERecord($full_domain, $cnameTarget, false);

        if (!$cloudflareResult['success']) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceId'");
            $response->error('Cloudflare: ' . ($cloudflareResult['message'] ?? 'Cloudflare API Fehler'), 502);
            return;
        }

        $response->json([
            'success' => true,
            'domain' => $full_domain,
            'is_main' => $is_main,
            'cloudflare' => $cloudflareResult
        ]);
    }

    public function disconnect(Request $request, Response $response): void
    {
        $codespace = $this->requireCodespace($request, $response);
        if (!$codespace) return;

        $codespaceId = (int) $codespace['id'];
        $delete = query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceId'");

        if ($delete) {
            $response->json(['success' => true]);
        } else {
            $response->error('Failed to disconnect domain', 500);
        }
    }

    public function info(Request $request, Response $response): void
    {
        $codespace = $this->requireCodespace($request, $response);
        if (!$codespace) return;

        $project_id = $codespace['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            $response->error('Projekt nicht gefunden', 404);
            return;
        }

        $project_link = $projectInfoRow['link'];

        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if (!$projectDomainRow = fetch_assoc($projectDomainResult)) {
            $response->error('Projekt hat keine Domain konfiguriert', 400);
            return;
        }

        $base_domain = $projectDomainRow['domain'];

        $existingMainResult = query("
            SELECT cd.id, pc.name as codespace_name FROM codespace_domains cd
            JOIN project_codespaces pc ON cd.codespace_id = pc.id
            WHERE pc.project_id = '$project_id' AND cd.is_main = 1
        ");

        $main_domain_taken = false;
        $main_domain_codespace = null;
        if ($mainRow = fetch_assoc($existingMainResult)) {
            $main_domain_taken = true;
            $main_domain_codespace = $mainRow['codespace_name'];
        }

        $response->json([
            'base_domain' => $base_domain,
            'main_domain_taken' => $main_domain_taken,
            'main_domain_codespace' => $main_domain_codespace
        ]);
    }

    private function requireCodespace(Request $request, Response $response): ?array
    {
        $codespaceId = (int) $request->params['id'];
        if ($codespaceId <= 0) {
            $response->error('Invalid codespace', 400);
            return null;
        }

        $row = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceId'"));
        if (!$row) {
            $response->error('Codespace not found', 404);
            return null;
        }

        if (!checkUserProjectPermission($request->userID, $row['project_id'])) {
            $response->error('No permission for this codespace', 403);
            return null;
        }

        return $row;
    }
}
