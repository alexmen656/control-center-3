<?php

class SidebarController
{
    public function listSections(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $sections = query("SELECT * FROM project_sidebar_sections WHERE projectID='$projectID' ORDER BY order_index ASC");
        $result = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'];
            $toolCount = mysqli_num_rows(query("SELECT id FROM project_tools WHERE section_id='$sectionId'"));

            $result[] = [
                'id' => (int) $section['id'],
                'name' => $section['name'],
                'slug' => $section['slug'],
                'icon' => $section['icon'],
                'order_index' => (int) $section['order_index'],
                'is_default' => (bool) $section['is_default'],
                'is_collapsible' => (bool) $section['is_collapsible'],
                'show_add_button' => (bool) $section['show_add_button'],
                'add_button_route' => $section['add_button_route'],
                'info_route' => $section['info_route'],
                'manage_route' => $section['manage_route'],
                'tool_count' => $toolCount,
                'created_at' => $section['created_at'],
                'updated_at' => $section['updated_at']
            ];
        }

        $response->json(['sections' => $result]);
    }

    public function templates(Request $request, Response $response): void
    {
        $templates = query("SELECT * FROM sidebar_section_templates ORDER BY default_order ASC");
        $result = [];

        foreach ($templates as $template) {
            $result[] = [
                'id' => (int) $template['id'],
                'name' => $template['name'],
                'slug' => $template['slug'],
                'icon' => $template['icon'],
                'default_order' => (int) $template['default_order'],
                'description' => $template['description'],
                'is_system' => (bool) $template['is_system']
            ];
        }

        $response->json(['templates' => $result]);
    }

    public function createSection(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $name = escape_string($request->input('name', ''));
        if (empty($name)) {
            $response->error('Name is required', 400);
            return;
        }

        $icon = escape_string($request->input('icon', 'folder-outline'));
        $slug = escape_string($request->input('slug', strtolower(str_replace(' ', '-', $request->input('name', '')))));

        $maxOrder = fetch_assoc(query("SELECT MAX(order_index) as max_order FROM project_sidebar_sections WHERE projectID='$projectID'"));
        $orderIndex = ($maxOrder['max_order'] ?? 0) + 1;

        $isCollapsible = $request->has('is_collapsible') ? (int) $request->input('is_collapsible') : 1;
        $showAddButton = $request->has('show_add_button') ? (int) $request->input('show_add_button') : 1;
        $addButtonRoute = escape_string($request->input('add_button_route', ''));
        $infoRoute = escape_string($request->input('info_route', ''));
        $manageRoute = escape_string($request->input('manage_route', ''));

        $result = query("INSERT INTO project_sidebar_sections
            (projectID, name, slug, icon, order_index, is_collapsible, show_add_button, add_button_route, info_route, manage_route)
            VALUES ('$projectID', '$name', '$slug', '$icon', '$orderIndex', '$isCollapsible', '$showAddButton', '$addButtonRoute', '$infoRoute', '$manageRoute')");

        if (!$result) {
            $response->error('Failed to create section', 500);
            return;
        }

        $newId = mysqli_insert_id($GLOBALS['con']);
        $response->json([
            'success' => true,
            'section' => [
                'id' => $newId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'order_index' => $orderIndex
            ]
        ]);
    }

    public function updateSection(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $sectionId = (int) $request->params['id'];
        if (!$sectionId) {
            $response->error('Section ID is required', 400);
            return;
        }

        $updates = [];
        if ($request->has('name'))
            $updates[] = "name='" . escape_string($request->input('name')) . "'";
        if ($request->has('icon'))
            $updates[] = "icon='" . escape_string($request->input('icon')) . "'";
        if ($request->has('slug'))
            $updates[] = "slug='" . escape_string($request->input('slug')) . "'";
        if ($request->has('order_index'))
            $updates[] = "order_index=" . intval($request->input('order_index'));
        if ($request->has('is_collapsible'))
            $updates[] = "is_collapsible=" . intval($request->input('is_collapsible'));
        if ($request->has('show_add_button'))
            $updates[] = "show_add_button=" . intval($request->input('show_add_button'));
        if ($request->has('add_button_route'))
            $updates[] = "add_button_route='" . escape_string($request->input('add_button_route')) . "'";
        if ($request->has('info_route'))
            $updates[] = "info_route='" . escape_string($request->input('info_route')) . "'";
        if ($request->has('manage_route'))
            $updates[] = "manage_route='" . escape_string($request->input('manage_route')) . "'";

        if (empty($updates)) {
            $response->error('No fields to update', 400);
            return;
        }

        $updateStr = implode(', ', $updates);
        $result = query("UPDATE project_sidebar_sections SET $updateStr WHERE id='$sectionId' AND projectID='$projectID'");

        $response->json(['success' => (bool) $result]);
    }

    public function deleteSection(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $sectionId = (int) $request->params['id'];
        if (!$sectionId) {
            $response->error('Section ID is required', 400);
            return;
        }

        $section = fetch_assoc(query("SELECT is_default FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'"));
        if ($section && $section['is_default']) {
            $response->error('Cannot delete default section', 400);
            return;
        }

        query("UPDATE project_tools SET section_id = NULL WHERE section_id='$sectionId'");
        $result = query("DELETE FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'");

        $response->json(['success' => (bool) $result]);
    }

    public function reorderSections(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $order = $request->input('order');
        if (!is_array($order) || empty($order)) {
            $response->error('order is required', 400);
            return;
        }

        foreach ($order as $index => $sectionId) {
            $sectionId = intval($sectionId);
            $index = intval($index);
            query("UPDATE project_sidebar_sections SET order_index='$index' WHERE id='$sectionId' AND projectID='$projectID'");
        }

        $response->json(['success' => true]);
    }

    public function reorderSectionItems(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $projectName = escape_string($request->input('project', ''));
        $itemOrder = $request->input('item_order');
        if (!is_array($itemOrder) || empty($itemOrder)) {
            $response->error('item_order is required', 400);
            return;
        }

        foreach ($itemOrder as $item) {
            $itemId = intval($item['id']);
            $order = intval($item['order']);
            $type = escape_string($item['type']);

            if ($type === 'tool') {
                query("UPDATE project_tools SET `order`='$order' WHERE id='$itemId' AND projectID='$projectID'");
            } else if ($type === 'table') {
                query("UPDATE table_settings SET order_index='$order' WHERE table_id='$itemId' AND project='$projectName'");
            }
        }

        $response->json(['success' => true]);
    }

    public function reorderTools(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $toolOrder = $request->input('tool_order');
        if (!is_array($toolOrder) || empty($toolOrder)) {
            $response->error('tool_order is required', 400);
            return;
        }

        foreach ($toolOrder as $index => $toolId) {
            $toolId = intval($toolId);
            $index = intval($index);
            query("UPDATE project_tools SET `order`='$index' WHERE id='$toolId' AND projectID='$projectID'");
        }

        $response->json(['success' => true]);
    }

    public function assignToolToSection(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $toolId = (int) $request->input('tool_id', 0);
        $sectionId = (int) $request->input('section_id', 0);

        if (!$toolId) {
            $response->error('Tool ID is required', 400);
            return;
        }

        $tool = query("SELECT id FROM project_tools WHERE id='$toolId' AND projectID='$projectID'");
        if (mysqli_num_rows($tool) == 0) {
            $response->error('Tool not found in this project', 404);
            return;
        }

        if ($sectionId > 0) {
            $section = query("SELECT id FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'");
            if (mysqli_num_rows($section) == 0) {
                $response->error('Section not found in this project', 404);
                return;
            }
        }

        $sectionValue = $sectionId > 0 ? "'$sectionId'" : "NULL";
        $result = query("UPDATE project_tools SET section_id=$sectionValue WHERE id='$toolId' AND projectID='$projectID'");

        $response->json(['success' => (bool) $result]);
    }

    public function assignTableToSection(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $projectName = escape_string($request->input('project', ''));
        $formId = (int) $request->input('table_id', 0);
        $sectionId = (int) $request->input('section_id', 0);

        if (!$formId) {
            $response->error('Form ID is required', 400);
            return;
        }

        $form = query("SELECT table_id FROM table_settings WHERE table_id='$formId' AND project='$projectName'");
        if (mysqli_num_rows($form) == 0) {
            $response->error('Form not found in this project', 404);
            return;
        }

        if ($sectionId > 0) {
            $section = query("SELECT id FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'");
            if (mysqli_num_rows($section) == 0) {
                $response->error('Section not found in this project', 404);
                return;
            }
        }

        $sectionValue = $sectionId > 0 ? "'$sectionId'" : "NULL";
        $result = query("UPDATE table_settings SET section_id=$sectionValue WHERE table_id='$formId' AND project='$projectName'");

        $response->json(['success' => (bool) $result]);
    }

    public function updateFormSidebar(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $projectName = escape_string($request->input('project', ''));
        $formId = (int) $request->params['formId'];
        if (!$formId) {
            $response->error('Form ID is required', 400);
            return;
        }

        $updates = [];
        if ($request->has('icon'))
            $updates[] = "icon='" . escape_string($request->input('icon')) . "'";
        if ($request->has('order_index'))
            $updates[] = "order_index=" . intval($request->input('order_index'));
        if ($request->has('section_id')) {
            $sectionId = (int) $request->input('section_id');
            $updates[] = $sectionId > 0 ? "section_id='$sectionId'" : "section_id=NULL";
        }

        if (empty($updates)) {
            $response->error('No fields to update', 400);
            return;
        }

        $updateStr = implode(', ', $updates);
        $result = query("UPDATE table_settings SET $updateStr WHERE table_id='$formId' AND project='$projectName'");

        $response->json(['success' => (bool) $result]);
    }

    public function createDefaultSections(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $projectName = escape_string($request->input('project', ''));

        $existingSections = mysqli_num_rows(query("SELECT id FROM project_sidebar_sections WHERE projectID='$projectID'"));
        if ($existingSections > 0) {
            $response->error('Project already has sections', 409, ['existing_count' => $existingSections]);
            return;
        }

        $result = query("INSERT INTO project_sidebar_sections
            (projectID, name, slug, icon, order_index, is_default, is_collapsible, show_add_button, add_button_route)
            VALUES ('$projectID', 'Tools', 'tools', 'construct-outline', 1, 1, 1, 1, '/project/$projectName/new/tool/')");

        if (!$result) {
            $response->error('Failed to create default section', 500);
            return;
        }

        $toolsSectionId = mysqli_insert_id($GLOBALS['con']);
        query("UPDATE project_tools SET section_id='$toolsSectionId' WHERE projectID='$projectID' AND (section_id IS NULL OR section_id = 0)");

        $response->json([
            'success' => true,
            'section_id' => $toolsSectionId,
            'message' => 'Default Tools section created and existing tools assigned'
        ]);
    }

    public function updateTool(Request $request, Response $response): void
    {
        $projectID = $this->resolveProject($request, $response);
        if (!$projectID)
            return;

        $toolId = (int) $request->input('tool_id', 0);
        if (!$toolId) {
            $response->error('Tool ID is required', 400);
            return;
        }

        $name = escape_string($request->input('name', ''));
        $icon = escape_string($request->input('icon', ''));

        $result = query("UPDATE project_tools SET name='$name', icon='$icon' WHERE id='$toolId' AND projectID='$projectID'");

        $response->json(['success' => (bool) $result]);
    }

    public function getSidebar(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        if (empty($projectName)) {
            $response->error('Project is required', 400);
            return;
        }

        $projectData = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"));
        if (!$projectData) {
            $response->error('Project not found', 404);
            return;
        }
        $projectID = $projectData['projectID'];

        $json = [];
        $sections = query("SELECT * FROM project_sidebar_sections WHERE projectID='$projectID' ORDER BY order_index ASC");
        $json['sections'] = [];

        if (mysqli_num_rows($sections) > 0) {
            $sectionIndex = 0;
            foreach ($sections as $section) {
                $sectionId = $section['id'];
                $json['sections'][$sectionIndex] = [
                    'id' => $sectionId,
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'icon' => $section['icon'],
                    'order_index' => $section['order_index'],
                    'is_default' => (bool) $section['is_default'],
                    'is_collapsible' => (bool) $section['is_collapsible'],
                    'show_add_button' => (bool) $section['show_add_button'],
                    'add_button_route' => $section['add_button_route'],
                    'info_route' => $section['info_route'],
                    'manage_route' => $section['manage_route'],
                    'items' => []
                ];

                $sectionTools = query("SELECT *, 'tool' as item_type FROM project_tools WHERE projectID='$projectID' AND section_id='$sectionId' ORDER BY `order` ASC");
                $items = [];

                if (mysqli_num_rows($sectionTools) > 0) {
                    foreach ($sectionTools as $t) {
                        $items[] = [
                            'id' => $t['id'],
                            'item_type' => 'tool',
                            'icon' => $t['icon'],
                            'name' => $t['name'],
                            'link' => $t['link'],
                            'hasConfig' => $t['hasConfig'],
                            'order' => (int) $t['order'],
                            'section_id' => $sectionId
                        ];
                    }
                }

                $sectionForms = query("SELECT * FROM table_settings WHERE project='$projectName' AND section_id='$sectionId' ORDER BY order_index ASC");
                if (mysqli_num_rows($sectionForms) > 0) {
                    foreach ($sectionForms as $form) {
                        $items[] = [
                            'id' => 'table_' . $form['table_id'],
                            'table_id' => $form['table_id'],
                            'item_type' => 'table',
                            'icon' => $form['icon'] ?? 'list-outline',
                            'name' => $form['table_name'],
                            'link' => 'forms/' . $form['table_name'],
                            'hasConfig' => 0,
                            'order' => (int) ($form['order_index'] ?? 999),
                            'section_id' => $sectionId
                        ];
                    }
                }

                usort($items, function ($a, $b) {
                    return $a['order'] - $b['order'];
                });

                $json['sections'][$sectionIndex]['items'] = $items;

                $json['sections'][$sectionIndex]['tools'] = array_filter($items, fn($item) => $item['item_type'] === 'tool');
                $json['sections'][$sectionIndex]['tools'] = array_values($json['sections'][$sectionIndex]['tools']);

                $sectionIndex++;
            }
        }

        $tools = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND (section_id IS NULL OR section_id = 0) ORDER BY `project_tools`.`order` ASC");
        if (mysqli_num_rows($tools) == 0) {
            $json['tools'] = [];
        } else {
            $i = 0;
            foreach ($tools as $t) {
                $json['tools'][$i]["id"] = $t['id'];
                $json['tools'][$i]["icon"] = $t['icon'];
                $json['tools'][$i]["name"] = $t['name'];
                $json['tools'][$i]["link"] = $t['link'];
                $json['tools'][$i]["hasConfig"] = $t['hasConfig'];
                $json['tools'][$i]["order"] = $t['order'];
                $i++;
            }
        }

        $forms = query("SELECT * FROM table_settings WHERE project='$projectName' ORDER BY order_index ASC, created_at DESC");

        if (mysqli_num_rows($forms) == 0) {
            $json['forms'] = [];
        } else {
            $f = 0;
            foreach ($forms as $form) {
                $json['forms'][$f]["table_id"] = $form['table_id'];
                $json['forms'][$f]["table_name"] = $form['table_name'];
                $json['forms'][$f]["name"] = $form['table_name'];
                $json['forms'][$f]["icon"] = $form['icon'] ?? "list-outline";
                $sectionId = $form['section_id'];
                $json['forms'][$f]["section_id"] = ($sectionId === null || $sectionId === '' || $sectionId === '0' || $sectionId === 0) ? null : (int) $sectionId;
                $json['forms'][$f]["order_index"] = $form['order_index'] ?? 0;
                $json['forms'][$f]["created_at"] = $form['created_at'];
                $f++;
            }
        }

        $subscribed_apis = query("
            SELECT pas.*, ca.name, ca.slug, ca.icon, ca.category
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.projectID='$projectID' AND pas.is_enabled=1
            ORDER BY ca.category, ca.name ASC
        ");

        if (mysqli_num_rows($subscribed_apis) == 0) {
            $json['apis'] = [];
        } else {
            $a = 0;
            foreach ($subscribed_apis as $api) {
                $json['apis'][$a]["id"] = $api['api_id'];
                $json['apis'][$a]["subscription_id"] = $api['id'];
                $json['apis'][$a]["icon"] = $api['icon'];
                $json['apis'][$a]["name"] = $api['name'];
                $json['apis'][$a]["slug"] = $api['slug'];
                $json['apis'][$a]["category"] = $api['category'];
                $json['apis'][$a]["status"] = $api['is_enabled'] ? 'active' : 'inactive';
                $json['apis'][$a]["usage_count"] = $api['usage_count'];
                $a++;
            }
        }

        $codespaces = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");

        if (mysqli_num_rows($codespaces) == 0) {
            $json['codespaces'] = [];
        } else {
            $c = 0;
            foreach ($codespaces as $codespace) {
                $json['codespaces'][$c]["id"] = $codespace['id'];
                $json['codespaces'][$c]["name"] = $codespace['name'];
                $json['codespaces'][$c]["slug"] = $codespace['slug'];
                $json['codespaces'][$c]["description"] = $codespace['description'];
                $json['codespaces'][$c]["icon"] = $codespace['icon'];
                $json['codespaces'][$c]["language"] = $codespace['language'];
                $json['codespaces'][$c]["template"] = $codespace['template'];
                $json['codespaces'][$c]["status"] = $codespace['status'];
                $json['codespaces'][$c]["order_index"] = $codespace['order_index'];
                $c++;
            }
        }

        $response->json($json);
    }

    public function getGlobal(Request $request, Response $response): void
    {
        $userID = (int) $request->userID;

        $json = [];
        $tools = query("SELECT * FROM tools");
        $i = 0;
        foreach ($tools as $t) {
            $json['tools'][$i]["id"] = $t['id'];
            $json['tools'][$i]["icon"] = $t['icon'];
            $json['tools'][$i]["name"] = $t['name'];
            $i++;
        }

        $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
        $i = 0;
        foreach ($projects as $p) {
            $projectID = $p['projectID'];
            $project = query("SELECT * FROM projects WHERE projectID='$projectID'");
            if (mysqli_num_rows($project) == 1) {
                $project = fetch_assoc($project);
                if ($project['hidden'] != true) {
                    $json['projects'][$i]["id"] = $project['id'];
                    $json['projects'][$i]["icon"] = $project['icon'];
                    $json['projects'][$i]["name"] = $project['name'];
                    $json['projects'][$i]["link"] = $project['link'];
                    $i++;
                }
            }
        }

        $response->json($json);
    }

    private function resolveProject(Request $request, Response $response): ?string
    {
        $projectName = escape_string($request->input('project', ''));
        if (empty($projectName)) {
            $response->error('Project is required', 400);
            return null;
        }

        try {
            $projectID = getProjectID($projectName);
        } catch (Exception $e) {
            $response->error('Project not found', 404);
            return null;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('Access denied', 403);
            return null;
        }

        return $projectID;
    }
}
