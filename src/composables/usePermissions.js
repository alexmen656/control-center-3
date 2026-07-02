import { ref, computed } from 'vue';
import axios from 'axios';
import qs from 'qs';

export function usePermissions(project) {
    const userRole = ref(null);
    const userPermissions = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const loadPermissions = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(
                'roles.php',
                qs.stringify({
                    getUserRole: 'getUserRole',
                    project: project.value || project
                })
            );
            if (response.data.role) {//response.data.success && 
                userRole.value = response.data.role;
                userPermissions.value = response.data.role.permissions;
            }
        } catch (err) {
            error.value = err;
            console.error('Failed to load permissions:', err);
        } finally {
            loading.value = false;
        }
    };

    const hasPermission = (resource, action) => {
        if (!userPermissions.value) return false;
        return userPermissions.value[resource]?.[action] === true;
    };

    const hasRole = (roleSlug) => {
        if (!userRole.value) return false;
        if (Array.isArray(roleSlug)) {
            return roleSlug.includes(userRole.value.slug);
        }
        return userRole.value.slug === roleSlug;
    };

    const hasAnyPermission = (permissions) => {
        return permissions.some(([resource, action]) =>
            hasPermission(resource, action)
        );
    };

    const hasAllPermissions = (permissions) => {
        return permissions.every(([resource, action]) =>
            hasPermission(resource, action)
        );
    };

    const canViewProject = computed(() => hasPermission('project', 'view'));
    const canEditProject = computed(() => hasPermission('project', 'edit'));
    const canDeleteProject = computed(() => hasPermission('project', 'delete'));
    const canManageUsers = computed(() => hasPermission('project', 'manage_users'));
    const canManageRoles = computed(() => hasPermission('project', 'manage_roles'));
    const canViewTools = computed(() => hasPermission('tools', 'view'));
    const canCreateTools = computed(() => hasPermission('tools', 'create'));
    const canEditTools = computed(() => hasPermission('tools', 'edit'));
    const canDeleteTools = computed(() => hasPermission('tools', 'delete'));
    const canViewComponents = computed(() => hasPermission('components', 'view'));
    const canCreateComponents = computed(() => hasPermission('components', 'create'));
    const canEditComponents = computed(() => hasPermission('components', 'edit'));
    const canDeleteComponents = computed(() => hasPermission('components', 'delete'));
    const canExportComponents = computed(() => hasPermission('components', 'export'));
    const canViewPages = computed(() => hasPermission('pages', 'view'));
    const canCreatePages = computed(() => hasPermission('pages', 'create'));
    const canEditPages = computed(() => hasPermission('pages', 'edit'));
    const canDeletePages = computed(() => hasPermission('pages', 'delete'));
    const canViewFilesystem = computed(() => hasPermission('filesystem', 'view'));
    const canCreateFiles = computed(() => hasPermission('filesystem', 'create'));
    const canEditFiles = computed(() => hasPermission('filesystem', 'edit'));
    const canDeleteFiles = computed(() => hasPermission('filesystem', 'delete'));
    const canUploadFiles = computed(() => hasPermission('filesystem', 'upload'));
    const canDownloadFiles = computed(() => hasPermission('filesystem', 'download'));
    const canViewDatabase = computed(() => hasPermission('database', 'view'));
    const canCreateDatabaseEntries = computed(() => hasPermission('database', 'create'));
    const canEditDatabaseEntries = computed(() => hasPermission('database', 'edit'));
    const canDeleteDatabaseEntries = computed(() => hasPermission('database', 'delete'));
    const canViewSettings = computed(() => hasPermission('settings', 'view'));
    const canEditSettings = computed(() => hasPermission('settings', 'edit'));

    const isOwner = computed(() => hasRole('owner'));
    const isAdmin = computed(() => hasRole(['owner', 'admin']));
    const isEditor = computed(() => hasRole('editor'));
    const isDeveloper = computed(() => hasRole('developer'));
    const isViewer = computed(() => hasRole('viewer'));

    return {
        userRole,
        userPermissions,
        loading,
        error,
        loadPermissions,
        hasPermission,
        hasRole,
        hasAnyPermission,
        hasAllPermissions,
        canViewProject,
        canEditProject,
        canDeleteProject,
        canManageUsers,
        canManageRoles,
        canViewTools,
        canCreateTools,
        canEditTools,
        canDeleteTools,
        canViewComponents,
        canCreateComponents,
        canEditComponents,
        canDeleteComponents,
        canExportComponents,
        canViewPages,
        canCreatePages,
        canEditPages,
        canDeletePages,
        canViewFilesystem,
        canCreateFiles,
        canEditFiles,
        canDeleteFiles,
        canUploadFiles,
        canDownloadFiles,
        canViewDatabase,
        canCreateDatabaseEntries,
        canEditDatabaseEntries,
        canDeleteDatabaseEntries,
        canViewSettings,
        canEditSettings,
        isOwner,
        isAdmin,
        isEditor,
        isDeveloper,
        isViewer
    };
}
/*
<template>
  <div>
    <button v-if="canEditComponents" @click="editComponent">
      Edit Component
    </button>
    
    <button v-if="canDeleteComponents" @click="deleteComponent">
      Delete Component
    </button>
    
    <div v-if="isAdmin">
      Admin-Only Content
    </div>
  </div>
</template>
<script>
import { usePermissions } from '@/composables/usePermissions';
import { computed } from 'vue';
export default {
  setup() {
    const project = computed(() => this.$route.params.project);
    
    const {
      canEditComponents,
      canDeleteComponents,
      canManageUsers,
      isAdmin,
      loadPermissions
    } = usePermissions(project);
    
    
    loadPermissions();
    
    return {
      canEditComponents,
      canDeleteComponents,
      canManageUsers,
      isAdmin
    };
  }
}
</script>
*/
