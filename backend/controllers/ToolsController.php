<?php

class ToolsController
{
    public function create(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('projectName', ''));
        $toolName = escape_string($request->input('toolName', ''));
        $toolIcon = escape_string($request->input('toolIcon', ''));
        $sectionId = $request->input('sectionId') !== null ? intval($request->input('sectionId')) : null;
        $link = strtolower(str_replace(['Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', ' '], ['a', 'a', 'u', 'u', 'o', 'o', '-'], $toolName));
        $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];
            $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;
            $sectionValue = $sectionId ? "'$sectionId'" : "NULL";
            $insert = query("INSERT INTO project_tools (id, icon, name, link, hasConfig, `order`, projectID, section_id) VALUES (0,'$toolIcon','$toolName', '$link',0,'$order','$projectID', $sectionValue)");

            if ($insert) {
                if ($toolName == "Chat App") {
                    $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'"))['id'];
                    $config = '{"api_key":"' . $this->generateApiKey() . '"}';
                    query("INSERT INTO control_center_chat_app_config (config, toolID) VALUES ('$config','$toolID')");
                }
                $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($projectName)) . "/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($toolName));
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
                $response->json("success");
            } else {
                $response->json("error 2");
            }
        } else {
            $response->json("error 1");
        }
    }

    public function saveConfig(Request $request, Response $response): void
    {
        $json = (string) $request->input('config', '');
        $projectName = escape_string($request->input('project', ''));
        $tool = escape_string($request->input('tool', ''));
        $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];
            $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$tool'"))['id'];
            if (mysqli_num_rows(query("SELECT * FROM project_tools_config WHERE tool_id='$toolID'")) == 0) {
                $result = query("INSERT INTO project_tools_config (config_json, tool_id) VALUES ('$json','$toolID')");
            } else {
                $result = query("UPDATE project_tools_config SET config_json='$json' WHERE tool_id='$toolID'");
            }

            if ($result) {
                $response->json("success");
            } else {
                $response->json("error 2");
            }
        } else {
            $response->json("error 1");
        }
    }

    public function getConfig(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $tool = escape_string($request->input('tool', ''));
        $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];
            $toolID_query = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$tool'");
            if ($toolID_query && mysqli_num_rows($toolID_query) > 0) {
                $toolID = fetch_assoc($toolID_query)['id'];
                $configQuery = query("SELECT * FROM project_tools_config WHERE tool_id='$toolID'");
                if (mysqli_num_rows($configQuery) == 1) {
                    $config = fetch_assoc($configQuery)['config_json'];
                    $response->json(json_decode($config));
                } else {
                    $response->json([]);
                }
            } else {
                $response->json("error 1");
            }
        } else {
            $response->json("error 1");
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $toolID = escape_string($request->params['id']);
        $result = query("DELETE FROM project_tools WHERE id=$toolID");
        if ($result) {
            $response->json("success");
        } else {
            $response->json("error 2");
        }
    }

    public function getProjectTools(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];
            $tools = query("SELECT * FROM project_tools WHERE projectID='$projectID'");
            $json = [];
            foreach ($tools as $tool) {
                $json[] = [
                    'id' => $tool['id'],
                    'icon' => $tool['icon'],
                    'name' => $tool['name'],
                    'link' => $tool['link']
                ];
            }
            $response->json($json);
        } else {
            $response->json([]);
        }
    }

    private function generateApiKey(): string
    {
        return 'API_' . bin2hex(random_bytes(32));
    }
}
