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
        $isSuperAdmin = ($userID == 152);

        $domainType = $request->input('domain_type', 'subdomain');
        $subdomain = strtolower(trim($request->input('subdomain', '')));

        if ($subdomain !== '' && !preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            $response->error('Ungültiges Subdomain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.', 400);
            return;
        }

        if ($domainType === 'custom') {
            if (!$isSuperAdmin) {
                $response->error('Custom Domains sind nur für Super Admins verfügbar.', 403);
                return;
            }

            $customBaseDomain = strtolower(trim($request->input('custom_base_domain', '')));
            if (!$customBaseDomain) {
                $response->error('Custom Base Domain fehlt.', 400);
                return;
            }

            $customBaseDomain = escape_string($customBaseDomain);
            $full_domain = $subdomain !== '' ? "$subdomain.$customBaseDomain" : $customBaseDomain;
            $is_main = $subdomain === '' ? 1 : 0;
        } else {
            if ($subdomain === '') {
                $response->error('Subdomain ist erforderlich.', 400);
                return;
            }

            $full_domain = "$subdomain.sites.control-center.eu";
            $is_main = 0;
        }

        $exists = query("SELECT id FROM codespace_domains WHERE domain='$full_domain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $response->error('Domain bereits vergeben.', 409);
            return;
        }

        $this->queueTeardownForExisting($codespaceId);

        $insert = query("INSERT INTO codespace_domains (codespace_id, domain, is_main, user_id, status) VALUES ('$codespaceId', '$full_domain', " . ($is_main ? 1 : 0) . ", '$userID', 'pending')");

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
            'status' => 'pending',
            'cloudflare' => $cloudflareResult
        ]);
    }

    public function disconnect(Request $request, Response $response): void
    {
        $codespace = $this->requireCodespace($request, $response);
        if (!$codespace) return;

        $codespaceId = (int) $codespace['id'];
        $this->queueTeardownForExisting($codespaceId);
        $delete = query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceId'");

        if ($delete) {
            $response->json(['success' => true]);
        } else {
            $response->error('Failed to disconnect domain', 500);
        }
    }

    private function queueTeardownForExisting(int $codespaceId): void
    {
        $existing = fetch_assoc(query("SELECT domain FROM codespace_domains WHERE codespace_id='$codespaceId' LIMIT 1"));
        if ($existing) {
            $domain = escape_string($existing['domain']);
            query("INSERT INTO codespace_domain_teardowns (domain) VALUES ('$domain')");
        }
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
