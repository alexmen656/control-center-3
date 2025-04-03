import { getProjectData } from '@/services/projectDataService';

export async function fetchUserData(projectId: string) {
    const projectData = await getProjectData(projectId);
    const users = projectData.tables.users;
    console.log(users);
}
