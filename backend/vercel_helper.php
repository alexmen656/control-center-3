<?php
if (!defined('VERCEL_HELPER_LOADED')) {
    define('VERCEL_HELPER_LOADED', true);

    class VercelAPIHelper
    {
        public function __construct($token = null, $teamId = null)
        {
        }
        public function getDeployments($projectName = null, $limit = 10)
        {
            return ['deployments' => []];
        }
        public function createDeployment($projectName, $files = [], $gitSource = null)
        {
            return ['success' => false, 'error' => 'vercel disabled'];
        }
        public function getDeploymentStatus($deploymentId)
        {
            return ['success' => false];
        }
        public function getProjects()
        {
            return ['projects' => []];
        }
        public function getProject($projectId)
        {
            return null;
        }
        public function getEnvironmentVariables($projectId)
        {
            return ['envs' => []];
        }
        public function getEnvironmentVariableValue($projectId, $envId)
        {
            return '';
        }
        public function getEnvironmentVariablesWithValues($projectId)
        {
            return [];
        }
        public function createEnvironmentVariable($projectId, $key, $value, $target = [])
        {
            return ['success' => true];
        }
        public function updateEnvironmentVariable($projectId, $envId, $key, $value, $target = [])
        {
            return ['success' => true];
        }
        public function deleteEnvironmentVariable($projectId, $envId)
        {
            return ['success' => true];
        }
        public function getDomains($projectId)
        {
            return ['domains' => []];
        }
        public function addDomain($projectId, $domain)
        {
            return ['success' => true];
        }
        public function removeDomain($projectId, $domain)
        {
            return ['success' => true];
        }
        public function verifyDomain($projectId, $domain)
        {
            return ['success' => true];
        }
    }

    class VercelHelper
    {
        public function __construct($userID = null)
        {
        }
        public function getVercelAPI()
        {
            return new VercelAPIHelper();
        }
        public function getDeployments($project, $codespace = 'main', $limit = 10)
        {
            return ['deployments' => []];
        }
        public function createDeployment($project, $codespace = 'main', $files = [], $gitSource = null)
        {
            return ['success' => false, 'error' => 'vercel disabled'];
        }
        public function getDeploymentStatus($deploymentId)
        {
            return ['success' => false];
        }
        public function getProjects()
        {
            return ['projects' => []];
        }
        public function getProject($project, $codespace = 'main')
        {
            return null;
        }
        public function getEnvironmentVariables($project, $codespace = 'main')
        {
            return [];
        }
        public function getEnvironmentVariablesWithValues($project, $codespace = 'main')
        {
            return [];
        }
        public function createEnvironmentVariable($project, $codespace, $key, $value, $target = [])
        {
            return ['success' => true];
        }
        public function updateEnvironmentVariable($project, $codespace, $envId, $key, $value, $target = [])
        {
            return ['success' => true];
        }
        public function deleteEnvironmentVariable($project, $codespace, $envId)
        {
            return ['success' => true];
        }
        public function setAPIKeyEnvironmentVariable($project, $codespace, $apiSlug, $apiKey)
        {
            return ['success' => true];
        }
        public function removeAPIKeyEnvironmentVariable($project, $codespace, $apiSlug)
        {
            return ['success' => true];
        }
        public function syncCodespaceAPIKeys($project, $codespace)
        {
            return ['success' => true];
        }
        public function addDomainToProject($project, $codespace, $domain)
        {
            return ['success' => true];
        }
        public function removeDomainFromProject($project, $codespace, $domain)
        {
            return ['success' => true];
        }
        public function getProjectDomains($project, $codespace)
        {
            return ['domains' => []];
        }
    }
}
