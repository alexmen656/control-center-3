<?php

class TriggersController
{
    public function create(Request $request, Response $response): void
    {
        $project = escape_string((string) $request->input('project', ''));
        $tableName = escape_string((string) $request->input('table_name', ''));
        $event = escape_string((string) $request->input('trigger_event', ''));
        $type = escape_string((string) $request->input('notification_type', ''));
        $target = escape_string((string) $request->input('notification_target', ''));
        $template = escape_string((string) $request->input('message_template', ''));

        $sql = "INSERT INTO table_triggers (project, table_name, trigger_event, notification_type, notification_target, message_template)
                VALUES ('$project', '$tableName', '$event', '$type', '$target', '$template')";

        if (query($sql)) {
            $response->json(['success' => true, 'message' => 'Trigger created successfully']);
        } else {
            $response->json(['success' => false, 'message' => 'Failed to create trigger']);
        }
    }

    public function list(Request $request, Response $response): void
    {
        $project = escape_string((string) $request->input('project', ''));
        $tableName = escape_string((string) $request->input('table_name', ''));

        $triggers = query("SELECT * FROM table_triggers
                          WHERE project='$project' AND table_name='$tableName'
                          ORDER BY created_at DESC");

        $result = [];
        while ($trigger = fetch_assoc($triggers)) {
            $result[] = $trigger;
        }

        $response->json($result);
    }

    public function delete(Request $request, Response $response): void
    {
        $triggerId = (int) $request->input('trigger_id', 0);

        if (query("DELETE FROM table_triggers WHERE id=$triggerId")) {
            $response->json(['success' => true]);
        } else {
            $response->json(['success' => false]);
        }
    }

    public function toggle(Request $request, Response $response): void
    {
        $triggerId = (int) $request->input('trigger_id', 0);
        $isActive = (int) $request->input('is_active', 0);

        if (query("UPDATE table_triggers SET is_active=$isActive WHERE id=$triggerId")) {
            $response->json(['success' => true]);
        } else {
            $response->json(['success' => false]);
        }
    }

    public function exportCsv(Request $request, Response $response): void
    {
        $project = escape_string((string) $request->input('project', ''));
        $tableName = escape_string((string) $request->input('table_name', ''));
        $tableName = createTableName($project . "_" . $tableName);
        $columns = query("SHOW COLUMNS FROM `$tableName`");
        $headers = [];

        while ($column = fetch_assoc($columns)) {
            $headers[] = $column['Field'];
        }

        $data = query("SELECT * FROM `$tableName`");
        $filename = $project . "_" . $tableName . "_" . date('Y-m-d_H-i-s') . ".csv";

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        while ($row = fetch_assoc($data)) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $response->download($content, $filename, 'text/csv');
    }
}
