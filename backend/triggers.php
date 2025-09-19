<?php
include "head.php";

// Trigger System für Form Events
class FormTriggers {
    
    public function __construct() {
        $this->initTriggerTable();
    }
    
    private function initTriggerTable() {
        $sql = "CREATE TABLE IF NOT EXISTS form_triggers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project VARCHAR(255) NOT NULL,
            form_name VARCHAR(255) NOT NULL,
            trigger_event ENUM('insert', 'update', 'delete') NOT NULL,
            notification_type ENUM('email', 'telegram', 'discord', 'sms') NOT NULL,
            notification_target TEXT NOT NULL,
            message_template TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        query($sql);
    }
    
    public function executeTriggers($project, $formName, $event, $data = []) {
        $project = escape_string($project);
        $formName = escape_string($formName);
        $event = escape_string($event);
        
        // Get all active triggers for this form and event
        $triggers = query("SELECT * FROM form_triggers 
                          WHERE project='$project' 
                          AND form_name='$formName' 
                          AND trigger_event='$event' 
                          AND is_active=1");
        
        while ($trigger = fetch_assoc($triggers)) {
            $this->sendNotification($trigger, $data);
        }
    }
    
    private function sendNotification($trigger, $data) {
        $message = $this->replacePlaceholders($trigger['message_template'], $data);
        
        switch($trigger['notification_type']) {
            case 'telegram':
                $this->sendTelegram($trigger['notification_target'], $message);
                break;
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
    
    private function replacePlaceholders($template, $data) {
        $message = $template;
        foreach ($data as $key => $value) {
            $message = str_replace("{" . $key . "}", $value, $message);
        }
        return $message;
    }
    
    private function sendTelegram($target, $message) {
        // Parse target (format: "token:chatid")
        $parts = explode(':', $target);
        if (count($parts) != 2) return false;
        
        $token = $parts[0];
        $chatId = $parts[1];
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $postData = json_encode([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postData
            ]
        ]);
        
        return file_get_contents($url, false, $context);
    }
    
    private function sendDiscord($webhookUrl, $message) {
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
    
    private function sendEmail($email, $message) {
        $subject = "Form Trigger Notification";
        $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    private function sendSMS($phoneNumber, $message) {
        // Placeholder für SMS API (z.B. Twilio)
        // Implementierung je nach gewähltem SMS-Provider
        return true;
    }
}

// API Endpoints
if (isset($_POST['create_trigger'])) {
    $project = escape_string($_POST['project']);
    $formName = escape_string($_POST['form_name']);
    $event = escape_string($_POST['trigger_event']);
    $type = escape_string($_POST['notification_type']);
    $target = escape_string($_POST['notification_target']);
    $template = escape_string($_POST['message_template']);
    
    $sql = "INSERT INTO form_triggers (project, form_name, trigger_event, notification_type, notification_target, message_template) 
            VALUES ('$project', '$formName', '$event', '$type', '$target', '$template')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Trigger created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create trigger']);
    }
}

if (isset($_POST['get_triggers'])) {
    $project = escape_string($_POST['project']);
    $formName = escape_string($_POST['form_name']);
    
    $triggers = query("SELECT * FROM form_triggers 
                      WHERE project='$project' AND form_name='$formName' 
                      ORDER BY created_at DESC");
    
    $result = [];
    while ($trigger = fetch_assoc($triggers)) {
        $result[] = $trigger;
    }
    
    echo json_encode($result);
}

if (isset($_POST['delete_trigger'])) {
    $triggerId = (int)$_POST['trigger_id'];
    
    if (query("DELETE FROM form_triggers WHERE id=$triggerId")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

if (isset($_POST['toggle_trigger'])) {
    $triggerId = (int)$_POST['trigger_id'];
    $isActive = (int)$_POST['is_active'];
    
    if (query("UPDATE form_triggers SET is_active=$isActive WHERE id=$triggerId")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// CSV Export functionality
if (isset($_POST['export_csv'])) {
    $project = escape_string($_POST['project']);
    $formName = escape_string($_POST['form_name']);
    
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
    
    // Get table structure
    $columns = query("SHOW COLUMNS FROM `$tableName`");
    $headers = [];
    while ($column = fetch_assoc($columns)) {
        $headers[] = $column['Field'];
    }
    
    // Get data
    $data = query("SELECT * FROM `$tableName`");
    
    // Generate CSV
    $filename = $project . "_" . $formName . "_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    while ($row = fetch_assoc($data)) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
    exit;
}
?>
