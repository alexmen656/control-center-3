<?php
include "head.php";

if (isset($_GET['action']) && $_GET['action'] === 'get_video' && isset($_GET['project_id']) && isset($_GET['video_id'])) {
    $projectId = escape_string($_GET['project_id']);
    $videoId = escape_string($_GET['video_id']);
    
    // Mock video data - in real implementation, this would fetch from database
    $mockVideo = [
        'id' => $videoId,
        'title' => 'Beispiel Video #' . $videoId,
        'description' => 'Das ist eine ausführliche Beschreibung des Videos mit wichtigen Informationen über den Inhalt und die Zielgruppe.',
        'thumbnail_url' => null,
        'file_size' => 45678901, // ~43MB
        'duration' => '3:24',
        'status' => 'published',
        'platforms' => ['youtube', 'instagram', 'tiktok'],
        'tags' => 'marketing,social media,engagement,video content',
        'created_at' => '2024-09-20 10:30:00',
        'updated_at' => '2024-09-20 14:45:00',
        'scheduled_at' => null,
        'project_id' => $projectId
    ];
    
    showJSON(['success' => true, 'video' => $mockVideo]);
} else {
    showJSON(['error' => 'Invalid request']);
}