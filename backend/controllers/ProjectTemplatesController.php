<?php

require_once __DIR__ . '/../project_templates_helper.php';

class ProjectTemplatesController
{
    public function list(Request $request, Response $response): void
    {
        $query = query("SELECT * FROM project_templates ORDER BY name");
        $templates = [];

        if (mysqli_num_rows($query) > 0) {
            while ($template = fetch_assoc($query)) {
                $templateId = $template['id'];

                $componentsQuery = query("SELECT * FROM project_template_components WHERE template_id = '$templateId' ORDER BY component_order");
                $components = [];

                while ($component = fetch_assoc($componentsQuery)) {
                    $components[] = [
                        'id' => $component['id'],
                        'name' => $component['name'],
                        'component_type' => $component['component_type'],
                        'icon' => $component['icon'],
                        'config' => json_decode($component['config'], true)
                    ];
                }

                $templates[] = [
                    'id' => $template['id'],
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'category' => $template['category'],
                    'thumbnail' => $template['thumbnail'],
                    'components' => $components
                ];
            }
        }

        $response->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    public function get(Request $request, Response $response): void
    {
        $templateId = intval($request->params['id']);

        if (!$templateId) {
            $response->error('Template ID is required', 400);
            return;
        }

        $query = query("SELECT * FROM project_templates WHERE id = '$templateId'");

        if (mysqli_num_rows($query) > 0) {
            $template = fetch_assoc($query);
            $templateId = $template['id'];

            $componentsQuery = query("SELECT * FROM project_template_components WHERE template_id = '$templateId' ORDER BY component_order");
            $components = [];

            while ($component = fetch_assoc($componentsQuery)) {
                $components[] = [
                    'id' => $component['id'],
                    'name' => $component['name'],
                    'component_type' => $component['component_type'],
                    'icon' => $component['icon'],
                    'config' => json_decode($component['config'], true)
                ];
            }

            $template['components'] = $components;

            $response->json([
                'success' => true,
                'template' => $template
            ]);
        } else {
            $response->error('Template not found', 404);
        }
    }

    public function create(Request $request, Response $response): void
    {
        $name = $request->input('name', '');
        $description = $request->input('description', '');
        $category = $request->input('category', 'general');
        $thumbnail = $request->input('thumbnail', '');
        $components = $request->input('components', []);
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

        if (empty($name)) {
            $response->error('Template name is required', 400);
            return;
        }

        query("INSERT INTO project_templates (name, description, category, thumbnail, created_at) VALUES ('$name', '$description', '$category', '$thumbnail', NOW())");
        $templateId = mysqli_insert_id($GLOBALS['con']);

        foreach ($components as $index => $component) {
            $componentName = $component['name'] ?? '';
            $componentType = $component['component_type'] ?? 'tool';
            $icon = $component['icon'] ?? '';
            $config = json_encode($component['config'] ?? []);

            query("INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order)
                   VALUES ('$templateId', '$componentName', '$componentType', '$icon', '$config', '$index')");
        }

        $response->json([
            'success' => true,
            'template_id' => $templateId,
            'message' => 'Template created successfully'
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $templateId = intval($request->params['id']);
        $name = $request->input('name', '');
        $description = $request->input('description', '');
        $category = $request->input('category', '');
        $thumbnail = $request->input('thumbnail', '');
        $components = $request->input('components', []);
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

        if (!$templateId) {
            $response->error('Template ID is required', 400);
            return;
        }

        query("UPDATE project_templates SET name = '$name', description = '$description', category = '$category', thumbnail = '$thumbnail', updated_at = NOW() WHERE id = '$templateId'");

        query("DELETE FROM project_template_components WHERE template_id = '$templateId'");

        foreach ($components as $index => $component) {
            $componentName = $component['name'] ?? '';
            $componentType = $component['component_type'] ?? 'tool';
            $icon = $component['icon'] ?? '';
            $config = json_encode($component['config'] ?? []);

            query("INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order)
                   VALUES ('$templateId', '$componentName', '$componentType', '$icon', '$config', '$index')");
        }

        $response->json([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);
    }

    public function delete(Request $request, Response $response): void
    {
        $templateId = intval($request->params['id']);

        if (!$templateId) {
            $response->error('Template ID is required', 400);
            return;
        }

        query("DELETE FROM project_template_components WHERE template_id = '$templateId'");

        query("DELETE FROM project_templates WHERE id = '$templateId'");

        $response->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    public function apply(Request $request, Response $response): void
    {
        $templateId = $request->input('template_id', 0);
        $projectName = $request->input('project_name', '');
        $projectIcon = $request->input('project_icon', 'folder-outline');

        $result = applyTemplate($templateId, $projectName, $projectIcon, $request->headers);
        $response->json($result);
    }
}
