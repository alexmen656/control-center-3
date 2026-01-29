<?php
include 'db_connection.php';
include 'functions.php';

$result = sendPush('Test Notification', 'This is a test push notification.', 79);
$result = sendPush('Test Notification', 'This is a test push notification.', 135);
