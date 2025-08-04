export const vercelConfig = {
    name: 'Vercel Environment Variables',
    description: 'Manage environment variables for your Vercel projects',
    version: '1.0.0',
    author: 'Control Center',
    category: 'deployment',
    
    routes: {
        environmentVariables: '/vercel/environment-variables',
        env: '/vercel/env'
    },
    
    api: {
        baseUrl: '/backend/vercel_api.php',
        endpoints: {
            projects: '?action=projects',
            project: '?action=project',
            envVariables: '?action=env',
            createEnv: '?project={{projectId}}',
            updateEnv: '?project={{projectId}}',
            deleteEnv: '?project={{projectId}}'
        }
    },
    
    features: {
        addEnvironmentVariables: true,
        editEnvironmentVariables: true,
        deleteEnvironmentVariables: true,
        targetEnvironments: ['production', 'preview', 'development'],
        valuesMasking: true
    }
};

export default vercelConfig;
