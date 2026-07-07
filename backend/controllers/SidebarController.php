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
