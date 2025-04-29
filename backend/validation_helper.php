<?php
/**
 * Validation helper functions
 */

/**
 * Validates that a template ID exists
 * 
 * @param mixed $templateId The template ID to validate
 * @return array|null Returns error array if invalid, null if valid
 */
function validateTemplateId($templateId) {
    if (!$templateId) {
        return [
            'success' => false,
            'message' => 'Template ID is required'
        ];
    }
    return null;
}

/**
 * Validates that a project name is not empty
 * 
 * @param string $projectName The project name to validate
 * @return array|null Returns error array if invalid, null if valid
 */
function validateProjectName($projectName) {
    if (empty($projectName)) {
        return [
            'success' => false,
            'message' => 'Project name is required'
        ];
    }
    return null;
}

/**
 * Performs multiple validations and returns the first error encountered
 * 
 * @param array $validations Array of validation results
 * @return array|null Returns the first error found or null if all valid
 */
function validateAll($validations) {
    foreach ($validations as $validation) {
        if ($validation !== null) {
            return $validation;
        }
    }
    return null;
}
?>
