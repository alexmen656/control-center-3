<template>
  <div class="ion-page">
    <ion-content>
      <div class="modern-project-view" :class="{ 'dark-mode': isDarkMode }">
        <div class="project-header">
          <div class="header-content">
            <div class="project-info">
              <h1 class="project-title">{{ $route.params.project }}</h1>
              <p class="project-subtitle">Project Overview & Management</p>
            </div>
            <div class="header-actions">
              <button class="action-btn" @click="refreshData" title="Refresh">
                <ion-icon name="refresh-outline" />
              </button>
              <button class="action-btn" @click="openSettings" title="Settings">
                <ion-icon name="settings-outline" />
              </button>
            </div>
          </div>
        </div>
        <div class="main-content">
          <div class="content-section">
            <div class="section-header">
              <div class="section-title">
                <ion-icon name="build-outline" class="section-icon" />
                <h2>Tools</h2>
              </div>
              <div class="section-actions">
                <router-link :to="`/project/${$route.params.project}/module-store/`" class="add-btn">
                  <ion-icon name="add" />
                  <span>New Tool</span>
                </router-link>
              </div>
            </div>
            <div class="cards-grid" v-if="tools.length > 0">
              <div v-for="tool in tools" :key="tool.id" class="tool-card" @click="goToTool(tool.link)">
                <div class="card-icon">
                  <ion-icon :name="tool.icon || 'construct-outline'" />
                </div>
                <div class="card-content">
                  <h3 class="card-title">{{ tool.name.charAt(0).toUpperCase() + tool.name.slice(1) }}</h3>
                  <!-- <p class="card-description">Tool Module</p>-->
                </div>
                <div class="card-actions">
                  <button class="card-action-btn" @click.stop="configureTool(tool)" title="Configure">
                    <ion-icon name="cog-outline" />
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="empty-state">
              <ion-icon name="construct-outline" class="empty-icon" />
              <h3>No Tools Yet</h3>
              <p>Start by creating your first tool module</p>
              <router-link :to="`/project/${$route.params.project}/new/tool/`" class="empty-action-btn">
                <ion-icon name="add" />
                Create Tool
              </router-link>
            </div>
          </div>
          <div class="content-section">
            <div class="section-header">
              <div class="section-title">
                <ion-icon name="cube-outline" class="section-icon" />
                <h2>Web Builder</h2>
              </div>
              <div class="section-actions">
                <button class="add-btn" @click="openWebBuilder()">
                  <ion-icon name="add" />
                  <span>New Project</span>
                </button>
              </div>
            </div>
            <div class="web-builder-content" v-if="webBuilderProjects && webBuilderProjects.length > 0">
              <div v-for="project in webBuilderProjects" :key="project.id" class="wb-project-section">
                <div class="wb-project-header">
                  <div class="wb-project-info">
                    <h3 class="wb-project-title">{{ project.name }}</h3>
                    <p class="wb-project-description" v-if="project.description">{{ project.description }}</p>
                  </div>
                  <div class="wb-project-actions">
                    <button class="icon-btn" @click="openWebBuilderProject(project.id)" title="Edit in Web Builder">
                      <ion-icon name="create-outline" />
                    </button>
                    <button class="icon-btn" @click="viewWebBuilderProject(project)" title="Preview Website"
                      v-if="webBuilderDomain">
                      <ion-icon name="eye-outline" />
                    </button>
                  </div>
                </div>
                <div class="pages-list" v-if="project.pages && project.pages.length > 0">
                  <div v-for="page in project.pages" :key="page.id" class="page-item">
                    <div class="page-icon">
                      <ion-icon name="document-outline" />
                    </div>
                    <div class="page-info">
                      <span class="page-name">{{ page.name }}</span>
                      <span class="page-slug">/{{ page.slug }}</span>
                      <span v-if="page.is_home" class="page-badge">Home</span>
                    </div>
                    <div class="page-actions">
                      <button class="page-action-btn" @click="editWebBuilderPage(project.id, page.id)"
                        title="Edit Page">
                        <ion-icon name="create-outline" />
                      </button>
                      <button class="page-action-btn" @click="viewWebBuilderPage(page)" title="Preview Page"
                        v-if="webBuilderDomain">
                        <ion-icon name="eye-outline" />
                      </button>
                    </div>
                  </div>
                </div>
                <div v-else class="no-pages">
                  <span>No pages yet</span>
                  <button class="text-btn" @click="openWebBuilderProject(project.id)">
                    <ion-icon name="add" />
                    Add Page
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="empty-state">
              <ion-icon name="cube-outline" class="empty-icon" />
              <h3>No Web Builder Projects</h3>
              <p>Create your first website with the Web Builder</p>
              <button class="empty-action-btn" @click="openWebBuilder()">
                <ion-icon name="add" />
                Create Project
              </button>
            </div>
          </div>
          <div class="content-section">
            <div class="section-header">
              <div class="section-title">
                <ion-icon name="people-outline" class="section-icon" />
                <h2>Team Members</h2>
              </div>
              <div class="section-actions" v-if="canManageTeam">
                <button class="add-btn" @click="setOpen(true)">
                  <ion-icon name="person-add" />
                  <span>Invite User</span>
                </button>
              </div>
            </div>
            <div class="users-grid" v-if="users.length > 0">
              <div v-for="user in users" :key="user.id" class="user-card">
                <div class="user-avatar">
                  <ion-icon name="person" />
                </div>
                <div class="user-info">
                  <h3 class="user-name">{{ user.name.charAt(0).toUpperCase() + user.name.slice(1) }}</h3>
                  <p class="user-role" v-if="user.role">{{ user.role.name }}</p>
                  <p class="user-role" v-else>No role assigned</p>
                </div>
                <div class="user-actions" v-if="canManageTeam">
                  <button class="card-action-btn" @click="editUserRole(user)" title="Edit Role">
                    <ion-icon name="create-outline" />
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="empty-state">
              <ion-icon name="people-outline" class="empty-icon" />
              <h3>No Team Members</h3>
              <p v-if="canManageTeam">Invite collaborators to your project</p>
              <p v-else>No team members have been added yet</p>
              <button v-if="canManageTeam" class="empty-action-btn" @click="setOpen(true)">
                <ion-icon name="person-add" />
                Invite User
              </button>
            </div>
          </div>
        </div>

        <!-- Invite User Modal -->
        <div v-if="isOpen" class="modal-overlay" @click="setOpen(false)">
          <div class="modern-modal" @click.stop>
            <div class="modal-header">
              <h3>Invite Team Member</h3>
              <button class="close-btn" @click="setOpen(false)">
                <ion-icon name="close" />
              </button>
            </div>
            <div class="modal-content">
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-container">
                  <input type="email" v-model="email" placeholder="john.doe@control-center.eu" class="form-input"
                    @keyup.enter="confirm" />
                  <ion-icon name="mail-outline" class="input-icon" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-select" v-model="selectedRoleId">
                  <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
                <p v-if="selectedRoleDescription" class="form-hint">
                  {{ selectedRoleDescription }}
                </p>
              </div>
            </div>
            <div class="modal-actions">
              <button class="btn-secondary" @click="setOpen(false)">Cancel</button>
              <button class="btn-primary" @click="confirm" :disabled="!email || !selectedRoleId">
                <ion-icon name="paper-plane-outline" />
                Send Invitation
              </button>
            </div>
          </div>
        </div>

        <!-- Edit User Role Modal -->
        <div v-if="isEditModalOpen" class="modal-overlay" @click="setEditModalOpen(false)">
          <div class="modern-modal" @click.stop>
            <div class="modal-header">
              <h3>Edit User Role</h3>
              <button class="close-btn" @click="setEditModalOpen(false)">
                <ion-icon name="close" />
              </button>
            </div>
            <div class="modal-content" v-if="editingUser">
              <div class="user-info-box">
                <div class="user-avatar-large">
                  <ion-icon name="person" />
                </div>
                <div>
                  <h4>{{ editingUser.name }}</h4>
                  <p class="text-muted">{{ editingUser.email }}</p>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Change Role</label>
                <select class="form-select" v-model="editingRoleId">
                  <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
                <p v-if="editingRoleDescription" class="form-hint">
                  {{ editingRoleDescription }}
                </p>
              </div>
            </div>
            <div class="modal-actions">
              <button class="btn-secondary" @click="setEditModalOpen(false)">Cancel</button>
              <button class="btn-primary" @click="confirmEditRole" :disabled="!editingRoleId">
                <ion-icon name="checkmark-outline" />
                Update Role
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </div>
</template>

<script>
import { ref } from "vue";
import { usePermissions } from '@/composables/usePermissions';

export default {
  name: "ProjectView",
  data() {
    return {
      tools: [],
      components: [],
      users: [],
      webBuilderProjects: [],
      webBuilderDomain: null,
      email: "",
      selectedPermission: "write",
      isDarkMode: false,
      availableRoles: [],
      selectedRoleId: null,
      editingUser: null,
      editingRoleId: null,
    };
  },
  setup() {
    const isOpen = ref(false);
    const isEditModalOpen = ref(false);
    const projectName = ref(null);

    const setOpen = (open) => {
      isOpen.value = open;
    };

    const setEditModalOpen = (open) => {
      isEditModalOpen.value = open;
    };

    const {
      canManageUsers,
      isAdmin,
      isOwner,
      loadPermissions
    } = usePermissions(projectName);

    return {
      isOpen,
      isEditModalOpen,
      setOpen,
      setEditModalOpen,
      canManageUsers,
      isAdmin,
      isOwner,
      loadPermissions,
      projectName
    };
  },
  created() {
    this.projectName = this.$route.params.project;
    this.loadData();
    this.checkDarkMode();
    this.loadRoles();
    this.loadPermissions();
  },
  computed: {
    selectedRoleDescription() {
      if (!this.selectedRoleId) return null;
      const role = this.availableRoles.find(r => r.id === this.selectedRoleId);
      return role ? role.description : null;
    },
    editingRoleDescription() {
      if (!this.editingRoleId) return null;
      const role = this.availableRoles.find(r => r.id === this.editingRoleId);
      return role ? role.description : null;
    },
    canManageTeam() {
      return this.isAdmin || this.isOwner;
    }
  },
  methods: {
    async loadRoles() {
      try {
        const response = await this.$axios.post(
          "roles.php",
          this.$qs.stringify({
            getAllRoles: "getAllRoles"
          })
        );

        if (response.data && response.data.roles) {
          this.availableRoles = response.data.roles;
        } else if (Array.isArray(response.data)) {
          this.availableRoles = response.data;
        }

        if (this.availableRoles.length > 0) {
          const editorRole = this.availableRoles.find(r => r.slug === 'editor');
          if (editorRole) {
            this.selectedRoleId = editorRole.id;
          } else {
            this.selectedRoleId = this.availableRoles[0].id;
          }
        }
      } catch (error) {
        console.error("Failed to load roles:", error);
        console.error("Error response:", error.response);
      }
    },

    editUserRole(user) {
      this.editingUser = user;
      this.editingRoleId = user.role ? user.role.id : null;
      this.setEditModalOpen(true);
    },

    async confirmEditRole() {
      if (!this.editingUser || !this.editingRoleId) {
        alert("Please select a role");
        return;
      }

      try {
        const response = await this.$axios.post(
          "roles.php",
          this.$qs.stringify({
            assignRole: "assignRole",
            project: this.$route.params.project,
            targetUserId: this.editingUser.id,
            roleId: this.editingRoleId
          })
        );

        if (response.data.success) {
          alert("Role updated successfully");
          this.loadData();
          this.setEditModalOpen(false);
          this.editingUser = null;
          this.editingRoleId = null;
        } else {
          alert("Failed to update role: " + response.data.message);
        }
      } catch (error) {
        console.error("Failed to update role:", error);
        alert("Failed to update role");
      }
    },

    loadData() {
      this.$axios
        .get("sidebar.php?getSideBarByProjectName=" + this.$route.params.project)
        .then((response) => {
          this.tools = response.data.tools || [];
          this.components = response.data.components || [];
        })
        .catch(error => {
          console.error("Failed to load sidebar:", error);
        });

      this.$axios
        .post(
          "projects.php",
          this.$qs.stringify({
            getProjectUsers: "getProjectUsers",
            project: this.$route.params.project,
          })
        )
        .then((response2) => {
          console.log('Users response:', response2.data);
          if (response2.data.success && response2.data.users) {
            this.users = response2.data.users;
          } else if (response2.data.users) {
            this.users = response2.data.users;
          } else if (Array.isArray(response2.data)) {
            this.users = response2.data;
          } else {
            this.users = [];
          }
          console.log('Users set to:', this.users);
        })
        .catch(error => {
          console.error("Failed to load users:", error);
          console.error("Error response:", error.response);
        });

      this.loadWebBuilderProjects();
      this.loadWebBuilderDomain();
    },

    async loadWebBuilderProjects() {
      try {
        const token = localStorage.getItem("token");
        const response = await this.$axios.get(
          "web-builder/projects.php",
          {
            headers: {
              Authorization: token
            }
          }
        );

        if (response.data && Array.isArray(response.data.data)) {
          this.webBuilderProjects = response.data.data.filter(p =>
            p.control_center_project.link === this.$route.params.project
          );
        }
      } catch (error) {
        console.error("Failed to load web builder projects:", error);
        this.webBuilderProjects = [];
      }
    },

    async loadWebBuilderDomain() {
      try {
        const response = await this.$axios.post(
          "web_builder_domains.php",
          this.$qs.stringify({
            action: "get",
            project: this.$route.params.project
          })
        );

        if (response.data.success && response.data.data) {
          this.webBuilderDomain = response.data.data;
        }
      } catch (error) {
        console.error("Failed to load web builder domain:", error);
      }
    },

    refreshData() {
      this.loadData();
    },

    checkDarkMode() {
      this.isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    },

    /*viewWWW() {
      if (this.webBuilderDomain && this.webBuilderDomain.domain) {
        window.open(`https://${this.webBuilderDomain.domain}`, "_blank").focus();
      } else {
        alert("No domain configured for this project.");
      }
    },*/

    openWebBuilder() {
      const project = this.$route.params.project;
      this.$router.push(`/project/${project}/new/wb`);
    },

    openWebBuilderProject(projectId) {
      const url = `https://web-builder.control-center.eu/projects/${projectId}`;
      window.open(url, '_blank').focus();
    },

    viewWebBuilderProject(project) {
      if (this.webBuilderDomain && this.webBuilderDomain.domain) {
        window.open(`https://${this.webBuilderDomain.domain}`, "_blank").focus();
      }
    },

    editWebBuilderPage(projectId, pageId) {
      const url = `https://web-builder.control-center.eu/projects/${projectId}/pages/${pageId}`;
      window.open(url, '_blank').focus();
    },

    viewWebBuilderPage(page) {
      if (this.webBuilderDomain && this.webBuilderDomain.domain) {
        const pageUrl = page.is_home ? '' : page.slug;
        window.open(`https://${this.webBuilderDomain.domain}/${pageUrl}`, "_blank").focus();
      }
    },

    openSettings() {
      this.$router.push(`/project/${this.$route.params.project}/info`);
    },

    goToTool(tool) {
      this.$router.push(
        `/project/${this.$route.params.project}/${tool}`
      );
    },

    configureTool(tool) {
      this.$router.push(`/project/${this.$route.params.project}/${tool.link}/config`);
    },

    async confirm() {
      if (!this.email || !this.selectedRoleId) {
        alert("Please enter an email and select a role");
        return;
      }

      try {
        const response = await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            addUserToProject: "addUserToProject",
            project: this.$route.params.project,
            email: this.email,
            roleId: this.selectedRoleId,
          })
        );

        if (response.data.success) {
          this.setOpen(false);
          this.email = "";
          this.loadData();
          alert("User invited successfully!");
        } else {
          alert("Failed to invite user: " + response.data.message);
        }
      } catch (error) {
        console.error("Error inviting user:", error);
        alert("Error sending invitation. Please try again.");
      }
    },
  },
};
</script>

<style scoped>
.modern-project-view {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --surface: #ffffff;
  --background: #f8fafc;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;

  min-height: 100vh;
  height: 100%;
  background: var(--background);
  padding: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

/* Project Header */
.project-header {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 24px 32px;
  margin-bottom: 32px;
  box-shadow: var(--shadow);
  z-index: 10;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}

.project-info {
  flex: 1;
}

.project-title {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  text-transform: capitalize;
  letter-spacing: -0.5px;
}

.project-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

/* Main Content */
.main-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 32px 32px;
}

/* Content Sections */
.content-section {
  margin-bottom: 48px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -0.3px;
}

.section-icon {
  font-size: 24px;
  color: var(--primary-color);
}

.section-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

/* Buttons */
.add-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--primary-color);
  color: white !important;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.add-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  color: white;
}

.icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

/* Tool Cards */
.tool-card,
.component-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.tool-card:hover,
.component-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: var(--radius);
  margin-bottom: 12px;
}

.card-icon ion-icon {
  font-size: 24px;
  color: white;
}

.card-icon.type-script {
  background: linear-gradient(135deg, #059669, #047857);
}

.card-icon.type-image {
  background: linear-gradient(135deg, #d97706, #b45309);
}

.card-icon.type-menu {
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
}

.card-content {
  flex: 1;
  margin-bottom: 12px;
}

.card-title {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.card-description {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
}

.card-actions {
  display: flex;
  justify-content: flex-end;
}

.card-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.card-action-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: scale(1.05);
}

/* User Cards */
.user-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  transition: all 0.2s ease;
}

.user-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: 50%;
  flex-shrink: 0;
}

.user-avatar ion-icon {
  font-size: 22px;
  color: white;
}

.user-info {
  flex: 1;
}

.user-name {
  margin: 0 0 2px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  margin: 0;
  color: var(--text-secondary);
  font-size: 12px;
}

.user-actions {
  flex-shrink: 0;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
  background: var(--surface);
  border: 2px dashed var(--border);
  border-radius: var(--radius-lg);
}

.empty-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state p {
  margin: 0 0 20px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.empty-action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.empty-action-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  color: white;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  backdrop-filter: blur(4px);
}

.modern-modal {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow: hidden;
  animation: modalAppear 0.2s ease-out;
  border: 1px solid var(--border);
}

@keyframes modalAppear {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }

  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  border-radius: var(--radius);
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.modal-content {
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
  font-size: 13px;
}

.input-container {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 10px 14px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.input-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 16px;
}

.form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-hint {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

.user-info-box {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
}

.user-avatar-large {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: 50%;
  color: white;
  font-size: 28px;
}

.user-info-box h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.text-muted {
  color: var(--text-muted);
  font-size: 13px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 16px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

.btn-secondary {
  padding: 10px 16px;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: var(--border);
  color: var(--text-primary);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Dark Mode */
.modern-project-view.dark-mode {
  --background: #121212;
  --surface: #1a1a1a;
  --border: #2a2a2a;
  --text-primary: #f1f5f9;
  --text-secondary: #b0b0b0;
  --text-muted: #707070;
}

/* Web Builder Styles */
.web-builder-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.wb-project-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
}

.wb-project-section:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.wb-project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}

.wb-project-info {
  flex: 1;
}

.wb-project-title {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.wb-project-description {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

.wb-project-actions {
  display: flex;
  gap: 8px;
}

.pages-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.page-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.page-item:hover {
  background: var(--surface);
  border-color: var(--primary-color);
}

.page-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: var(--radius);
  flex-shrink: 0;
}

.page-icon ion-icon {
  font-size: 16px;
  color: white;
}

.page-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.page-name {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
}

.page-slug {
  font-size: 12px;
  color: var(--text-muted);
  font-family: monospace;
}

.page-badge {
  padding: 2px 8px;
  background: var(--success-color);
  color: white;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
}

.page-actions {
  display: flex;
  gap: 4px;
}

.page-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.page-action-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: scale(1.05);
}

.no-pages {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
  color: var(--text-muted);
  font-size: 13px;
}

.text-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: none;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--primary-color);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.text-btn:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 768px) {
  .project-header {
    padding: 16px;
  }

  .header-content {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .wb-project-header {
    flex-direction: column;
    gap: 12px;
  }

  .wb-project-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .page-item {
    flex-wrap: wrap;
  }

  .page-info {
    width: 100%;
  }

  .main-content {
    padding: 0 16px 16px;
  }

  .section-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .cards-grid {
    grid-template-columns: 1fr;
  }

  .users-grid {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 16px;
  }

  .modal-actions {
    flex-direction: column;
  }

  .project-title {
    font-size: 22px;
  }
}

@media (max-width: 480px) {

  .tool-card,
  .component-card,
  .user-card {
    padding: 12px;
  }

  .empty-state {
    padding: 32px 16px;
  }
}
</style>
