<?php
require_once "head.php";

class TableTriggers
{

    public function __construct()
    {
        $this->initTriggerTable();
    }

    private function initTriggerTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS table_triggers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project VARCHAR(255) NOT NULL,
            table_name VARCHAR(255) NOT NULL,
            trigger_event ENUM('insert', 'update', 'delete') NOT NULL,
            notification_type ENUM('email', 'discord', 'sms') NOT NULL,
            notification_target TEXT NOT NULL,
            message_template TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        query($sql);
    }

    public function executeTriggers($project, $tableName, $event, $data = [])
    {
        $project = escape_string($project);
        $tableName = escape_string($tableName);
        $event = escape_string($event);

        $triggers = query("SELECT * FROM table_triggers 
                          WHERE project='$project' 
                          AND table_name='$tableName' 
                          AND trigger_event='$event' 
                          AND is_active=1");

        while ($trigger = fetch_assoc($triggers)) {
            $this->sendNotification($trigger, $data);
        }
    }

    private function sendNotification($trigger, $data)
    {
        $message = $this->replacePlaceholders($trigger['message_template'], $data);

        switch ($trigger['notification_type']) {
            case 'discord':
                $this->sendDiscord($trigger['notification_target'], $message);
                break;
            case 'email':
                $this->sendEmail($trigger['notification_target'], $message);
                break;
            case 'sms':
                $this->sendSMS($trigger['notification_target'], $message);
                break;
        }
    }

    private function replacePlaceholders($template, $data)
    {
        $message = $template;
        foreach ($data as $key => $value) {
            $message = str_replace("{" . $key . "}", $value, $message);
        }
        return $message;
    }

    private function sendDiscord($webhookUrl, $message)
    {
        $postData = json_encode([
            'content' => $message,
            'username' => 'Form Trigger Bot'
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postData
            ]
        ]);

        return file_get_contents($webhookUrl, false, $context);
    }

    private function sendEmail($email, $message)
    {
        $subject = "Form Trigger Notification";
        $result = sendMail($email, $subject, $message);
        return $result['success'];
    }

    private function sendSMS($phoneNumber, $message)
    {
        return true;
    }

    public function renameTableTriggers($project, $oldTableName, $newTableName)
    {
        $project = escape_string($project);
        $oldTableName = escape_string($oldTableName);
        $newTableName = escape_string($newTableName);

        $sql = "UPDATE table_triggers SET table_name='$newTableName' 
                WHERE project='$project' AND table_name='$oldTableName'";

        return query($sql);
    }
}

if (isset($_POST['create_trigger'])) {
    $project = escape_string($_POST['project']);
    $tableName = escape_string($_POST['table_name']);
    $event = escape_string($_POST['trigger_event']);
    $type = escape_string($_POST['notification_type']);
    $target = escape_string($_POST['notification_target']);
    $template = escape_string($_POST['message_template']);

    $sql = "INSERT INTO table_triggers (project, table_name, trigger_event, notification_type, notification_target, message_template) 
            VALUES ('$project', '$tableName', '$event', '$type', '$target', '$template')";

    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Trigger created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create trigger']);
    }
}

if (isset($_POST['get_triggers'])) {
    $project = escape_string($_POST['project']);
    $tableName = escape_string($_POST['table_name']);

    $triggers = query("SELECT * FROM table_triggers 
                      WHERE project='$project' AND table_name='$tableName' 
                      ORDER BY created_at DESC");

    $result = [];
    while ($trigger = fetch_assoc($triggers)) {
        $result[] = $trigger;
    }

    echo json_encode($result);
}

if (isset($_POST['delete_trigger'])) {
    $triggerId = (int) $_POST['trigger_id'];

    if (query("DELETE FROM table_triggers WHERE id=$triggerId")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

if (isset($_POST['toggle_trigger'])) {
    $triggerId = (int) $_POST['trigger_id'];
    $isActive = (int) $_POST['is_active'];

    if (query("UPDATE table_triggers SET is_active=$isActive WHERE id=$triggerId")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

if (isset($_POST['export_csv'])) {
    $project = escape_string($_POST['project']);
    $tableName = escape_string($_POST['table_name']);
    $tableName = createTableName($project . "_" . $tableName);
    $columns = query("SHOW COLUMNS FROM `$tableName`");
    $headers = [];

    while ($column = fetch_assoc($columns)) {
        $headers[] = $column['Field'];
    }

    $data = query("SELECT * FROM `$tableName`");
    $filename = $project . "_" . $tableName . "_" . date('Y-m-d_H-i-s') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);

    while ($row = fetch_assoc($data)) {
        fputcsv($output, array_values($row));
    }

    fclose($output);
    exit;
}
?>