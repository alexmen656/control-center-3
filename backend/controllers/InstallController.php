<?php

class InstallController
{
    private function generateApiKey(): string
    {
        return 'API_' . bin2hex(random_bytes(32));
    }

    private function toLink(string $name): string
    {
        return strtolower(str_replace(['Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', ' '], ['a', 'a', 'u', 'u', 'o', 'o', '-'], $name));
    }

    private function toSlug(string $name): string
    {
        return str_replace([' ', 'ä', 'Ä', 'ü', 'Ü', 'ö', 'Ö'], ['-', 'a', 'a', 'u', 'u', 'o', 'o'], strtolower($name));
    }

    public function listModules(Request $request, Response $response): void
    {
        $modules = query("SELECT * FROM module_store_modules");
        $json = [];
        foreach ($modules as $module) {
            $json[] = [
                'id' => $module['id'],
                'name' => $module['name'],
                'display_name' => $module['display_name'],
                'description' => $module['description'] ?? "test",
                'icon' => $module['tool_icon'],
                'ref' => $module['ref'],
                'active' => isset($module['active']) ? (bool) $module['active'] : true
            ];
        }
        $response->json($json);
    }

    public function install(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $moduleID = escape_string($request->input('moduleID', ''));

        $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
        if (mysqli_num_rows($module) != 1) {
            $response->json(['success' => false, 'message' => 'module not found']);
            return;
        }

        $module = fetch_assoc($module);
        $toolName = $module['display_name'];
        $toolIcon = $module['tool_icon'];

        $projectID = query("SELECT * FROM projects WHERE link='$project'");
        if (mysqli_num_rows($projectID) != 1) {
            $response->json(['success' => false, 'message' => 'project not found']);
            return;
        }
        $projectID = fetch_assoc($projectID)['projectID'];

        $link = $this->toLink($toolName);
        $existing = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'");
        if (mysqli_num_rows($existing) > 0) {
            $response->json(['success' => false, 'message' => 'module already added']);
            return;
        }

        $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;
        $inserted = query("INSERT INTO project_tools (id, icon, name, link, hasConfig, `order`, projectID, section_id) VALUES (0,'$toolIcon','$toolName', '$link',0,'$order','$projectID', NULL)");

        if (!$inserted) {
            $response->json(['success' => false, 'message' => 'failed to add module']);
            return;
        }

        if ($toolName == "Chat App") {
            $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'"))['id'];
            $config = '{"api_key":"' . $this->generateApiKey() . '"}';
            query("INSERT INTO control_center_chat_app_config (config, toolID) VALUES ('$config','$toolID')");
        }

        $url = "project/" . $this->toSlug($project) . "/" . $this->toSlug($toolName);
        $config_url = $url . "/config";
        $config_name = $toolName . " Config";
        $true = "true";
        if ($toolName == "Mail") {
            $true = "false";
        }
        query("INSERT INTO control_center_pages VALUES (0,'$url', '$true','$toolIcon','$toolName', '', 0)");
        query("INSERT INTO control_center_pages VALUES (0,'$config_url', 'true','cog-outline','$config_name', '', 0)");

        if ($toolName == "Mail") {
            $mail_url = $url . "/email";
            query("INSERT INTO control_center_pages VALUES (0,'$mail_url', 'false','$toolIcon','$toolName', '', 0)");
        }

        $response->json(['success' => true, 'message' => 'success']);
    }

    public function uninstall(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $moduleID = escape_string($request->input('moduleID', ''));

        $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
        if (mysqli_num_rows($module) != 1) {
            $response->json(['success' => false, 'message' => 'module not found']);
            return;
        }

        $module = fetch_assoc($module);
        $toolName = $module['display_name'];
        $link = $this->toLink($toolName);

        $projectID = query("SELECT * FROM projects WHERE link='$project'");
        if (mysqli_num_rows($projectID) != 1) {
            $response->json(['success' => false, 'message' => 'project not found']);
            return;
        }
        $projectID = fetch_assoc($projectID)['projectID'];

        $tool = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'");
        if (mysqli_num_rows($tool) != 1) {
            $response->json(['success' => false, 'message' => 'module not found in project']);
            return;
        }
        $toolID = fetch_assoc($tool)['id'];

        if (!query("DELETE FROM project_tools WHERE id='$toolID'")) {
            $response->json(['success' => false, 'message' => 'failed to remove module']);
            return;
        }

        $url = "project/" . $this->toSlug($project) . "/" . $this->toSlug($toolName);
        query("DELETE FROM control_center_pages WHERE url LIKE '$url%'");

        $response->json(['success' => true, 'message' => 'success']);
    }

    public function createModule(Request $request, Response $response): void
    {
        $name = escape_string($request->input('name', ''));
        $display_name = escape_string($request->input('display_name', ''));
        $icon = escape_string($request->input('icon', 'cube-outline'));
        $ref = $this->toLink($name);

        $existing = query("SELECT * FROM module_store_modules WHERE name='$name' OR ref='$ref'");
        if (mysqli_num_rows($existing) > 0) {
            $response->json(['success' => false, 'message' => 'Module with this name already exists']);
            return;
        }

        $query = query("INSERT INTO module_store_modules (name, display_name, tool_icon, ref) VALUES ('$name', '$display_name', '$icon', '$ref')");

        if ($query) {
            $response->json(['success' => true, 'message' => 'Module created successfully']);
        } else {
            $response->json(['success' => false, 'message' => 'Failed to create module']);
        }
    }

    public function updateModule(Request $request, Response $response): void
    {
        $moduleID = escape_string($request->input('moduleID', ''));
        $name = escape_string($request->input('name', ''));
        $display_name = escape_string($request->input('display_name', ''));
        $icon = escape_string($request->input('icon', 'cube-outline'));
        $ref = $this->toLink($name);

        $existing = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
        if (mysqli_num_rows($existing) === 0) {
            $response->json(['success' => false, 'message' => 'Module not found']);
            return;
        }

        $query = query("UPDATE module_store_modules SET name='$name', display_name='$display_name', tool_icon='$icon', ref='$ref' WHERE id='$moduleID'");

        if ($query) {
            $response->json(['success' => true, 'message' => 'Module updated successfully']);
        } else {
            $response->json(['success' => false, 'message' => 'Failed to update module']);
        }
    }

    public function deleteModule(Request $request, Response $response): void
    {
        $moduleID = escape_string($request->input('moduleID', ''));

        $existing = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
        if (mysqli_num_rows($existing) === 0) {
            $response->json(['success' => false, 'message' => 'Module not found']);
            return;
        }

        $module = fetch_assoc($existing);
        $display_name = $module['display_name'];

        $link = $this->toLink($display_name);
        $inUse = query("SELECT COUNT(*) as count FROM project_tools WHERE name='$display_name' OR link='$link'");
        $count = fetch_assoc($inUse)['count'];

        if ($count > 0) {
            $response->json(['success' => false, 'message' => "Module is currently used in $count project(s). Remove from projects first."]);
            return;
        }

        $query = query("DELETE FROM module_store_modules WHERE id='$moduleID'");

        if ($query) {
            $response->json(['success' => true, 'message' => 'Module deleted successfully']);
        } else {
            $response->json(['success' => false, 'message' => 'Failed to delete module']);
        }
    }
}
