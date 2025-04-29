<?php
/**
 * Header Template for generated HTML pages
 * 
 * Verwendung: include mit Variablen $pageTitle und $pageMetaDescription
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Website'); ?></title>
    <?php if (!empty($pageMetaDescription)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($pageMetaDescription); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="styles.css">
</head>
<body>