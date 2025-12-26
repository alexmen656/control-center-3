<template>
  <div class="modern-project-view" :class="{ 'dark-mode': isDarkMode }">
    <!-- Project Header -->
    <div class="project-header">
      <div class="header-content">
        <div class="project-info">
          <h1 class="project-title">{{ $route.params.project }}</h1>
          <p class="project-subtitle">Project Overview & Management</p>
        </div>
        <div class="header-actions">
          <button class="action-btn" @click="refreshData" title="Refresh">
            <ion-icon name="refresh-outline"></ion-icon>
          </button>
          <button class="action-btn" @click="openSettings" title="Settings">
            <ion-icon name="settings-outline"></ion-icon>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Tools Section -->
      <div class="content-section">
        <div class="section-header">
          <div class="section-title">
            <ion-icon name="build-outline" class="section-icon"></ion-icon>
            <h2>Tools</h2>
          </div>
          <div class="section-actions">
            <router-link :to="`/project/${$route.params.project}/new-tool/`" class="add-btn">
              <ion-icon name="add"></ion-icon>
              <span>New Tool</span>
            </router-link>
          </div>
        </div>

        <div class="cards-grid" v-if="tools.length > 0">
          <div v-for="tool in tools" :key="tool.id" class="tool-card" @click="goToTool(tool.name)">
            <div class="card-icon">
              <ion-icon :name="tool.icon || 'construct-outline'"></ion-icon>
            </div>
            <div class="card-content">
              <h3 class="card-title">{{ tool.name.charAt(0).toUpperCase() + tool.name.slice(1) }}</h3>
              <p class="card-description">Tool Module</p>
            </div>
            <div class="card-actions">
              <button class="card-action-btn" @click.stop="configureTool(tool)" title="Configure">
                <ion-icon name="cog-outline"></ion-icon>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <ion-icon name="construct-outline" class="empty-icon"></ion-icon>
          <h3>No Tools Yet</h3>
          <p>Start by creating your first tool module</p>
          <router-link :to="`/project/${$route.params.project}/new-tool/`" class="empty-action-btn">
            <ion-icon name="add"></ion-icon>
            Create Tool
          </router-link>
        </div>
      </div>

      <!-- Components Section -->
      <div class="content-section">
        <div class="section-header">
          <div class="section-title">
            <ion-icon name="cube-outline" class="section-icon"></ion-icon>
            <h2>Components</h2>
          </div>
          <div class="section-actions">
            <button class="icon-btn" @click="openWebBuilder()" title="Web Builder">
              <ion-icon name="globe-outline"></ion-icon>
            </button>
            <button class="icon-btn" @click="exportWeb()" title="Export">
              <ion-icon name="download-outline"></ion-icon>
            </button>
            <button class="icon-btn" @click="viewWWW()" title="Preview">
              <ion-icon name="earth-outline"></ion-icon>
            </button>
          </div>
        </div>

        <!-- Download Link -->
        <div v-if="downloadLink" class="download-section">
          <a :href="'https://alex.polan.sk/control-center/website_builder/exports/' + downloadLink" download
            class="download-link">
            <ion-icon name="download-outline"></ion-icon>
            {{ downloadLink }}
          </a>
        </div>

        <div class="cards-grid" v-if="components && components.length > 0">
          <div v-for="component in components" :key="component.id" class="component-card">
            <div class="card-icon" :class="`type-${component.type}`">
              <ion-icon :name="getComponentIcon(component.type)"></ion-icon>
            </div>
            <div class="card-content">
              <h3 class="card-title">{{ component.name.charAt(0).toUpperCase() + component.name.slice(1) }}</h3>
              <p class="card-description">{{ component.type.charAt(0).toUpperCase() + component.type.slice(1) }}</p>
            </div>
            <div class="card-actions">
              <button class="card-action-btn" @click="editComponent(component)" title="Edit">
                <ion-icon name="create-outline"></ion-icon>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <ion-icon name="cube-outline" class="empty-icon"></ion-icon>
          <h3>No Components Yet</h3>
          <p>Create your first UI component</p>
          <button class="empty-action-btn" @click="openWebBuilder()">
            <ion-icon name="add"></ion-icon>
            Create Component
          </button>
        </div>
      </div>

      <!-- Users Section -->
      <div class="content-section">
        <div class="section-header">
          <div class="section-title">
            <ion-icon name="people-outline" class="section-icon"></ion-icon>
            <h2>Team Members</h2>
          </div>
          <div class="section-actions">
            <button class="add-btn" @click="setOpen(true)">
              <ion-icon name="person-add"></ion-icon>
              <span>Invite User</span>
            </button>
          </div>
        </div>

        <div class="users-grid" v-if="users.length > 0">
          <div v-for="user in users" :key="user.id" class="user-card">
            <div class="user-avatar">
              <ion-icon name="person"></ion-icon>
            </div>
            <div class="user-info">
              <h3 class="user-name">{{ user.name.charAt(0).toUpperCase() + user.name.slice(1) }}</h3>
              <p class="user-role">Read, Edit & Write</p>
            </div>
            <div class="user-actions">
              <button class="card-action-btn" @click="manageUser(user)" title="Manage">
                <ion-icon name="ellipsis-horizontal"></ion-icon>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <ion-icon name="people-outline" class="empty-icon"></ion-icon>
          <h3>No Team Members</h3>
          <p>Invite collaborators to your project</p>
          <button class="empty-action-btn" @click="setOpen(true)">
            <ion-icon name="person-add"></ion-icon>
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
            <ion-icon name="close"></ion-icon>
          </button>
        </div>

        <div class="modal-content">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-container">
              <input type="email" v-model="email" placeholder="john.doe@control-center.eu" class="form-input"
                @keyup.enter="confirm" />
              <ion-icon name="mail-outline" class="input-icon"></ion-icon>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Permission Level</label>
            <select class="form-select" v-model="selectedPermission">
              <option value="read">Read Only</option>
              <option value="write">Read & Write</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-secondary" @click="setOpen(false)">Cancel</button>
          <button class="btn-primary" @click="confirm" :disabled="!email">
            <ion-icon name="paper-plane-outline"></ion-icon>
            Send Invitation
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from "vue";

export default {
  name: "ProjectView",
  data() {
    return {
      tools: [],
      components: [],
      users: [],
      downloadLink: "",
      email: "",
      selectedPermission: "write",
      isDarkMode: false,
    };
  },
  setup() {
    const isOpen = ref(false);
    const setOpen = (open) => {
      isOpen.value = open;
    };

    return {
      isOpen,
      setOpen,
    };
  },
  created() {
    this.loadData();
    this.checkDarkMode();
  },
  methods: {
    loadData() {
      // Load sidebar data
      this.$axios
        .get("sidebar.php?getSideBarByProjectName=" + this.$route.params.project)
        .then((response) => {
          this.tools = response.data.tools;
          this.components = response.data.components;
        });

      // Load project users
      this.$axios
        .post(
          "projects.php",
          this.$qs.stringify({
            getProjectUsers: "getProjectUsers",
            project: this.$route.params.project,
          })
        )
        .then((response2) => {
          this.users = response2.data;
        });
    },

    refreshData() {
      this.loadData();
    },

    checkDarkMode() {
      this.isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    },

    viewWWW() {
      window.open("https://alex.polan.sk/" + this.$route.params.project, "_blank").focus();
    },

    openWebBuilder() {
      const project = this.$route.params.project;
      const url = `https://web-builder.control-center.eu/project/${project}`;
      window.open(url, '_blank').focus();
    },

    exportWeb() {
      this.$axios.post("website_builder/export.php").then((response) => {
        this.downloadLink = response.data;
      });
    },

    openSettings() {
      this.$router.push(`/project/${this.$route.params.project}/settings`);
    },

    goToTool(tool) {
      if (tool.toLowerCase().includes("dashboard-")) {
        this.$router.push(
          "/project/" +
          this.$route.params.project +
          "/dashboard/" +
          tool.toLowerCase().replaceAll(" ", "-")
        );
      } else {
        this.$router.push(
          "/project/" +
          this.$route.params.project +
          "/" +
          tool.toLowerCase().replaceAll(" ", "-")
        );
      }
    },

    configureTool(tool) {
      this.$router.push(`/project/${this.$route.params.project}/${tool.name.toLowerCase()}/config`);
    },

    editComponent(component) {
      this.$router.push(`/project/${this.$route.params.project}/component/${component.id}`);
    },

    manageUser(user) {
      // Implement user management
      console.log('Manage user:', user);
    },

    getComponentIcon(type) {
      switch (type) {
        case 'script':
          return 'code-slash-outline';
        case 'image':
          return 'image-outline';
        case 'menu':
          return 'menu-outline';
        default:
          return 'cube-outline';
      }
    },

    async confirm() {
      if (!this.email) return;

      try {
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            addUserToProject: "addUserToProject",
            project: this.$route.params.project,
            email: this.email,
            permission: this.selectedPermission,
          })
        );

        this.setOpen(false);
        this.email = "";
        this.selectedPermission = "write";
        this.loadData(); // Refresh user list

        // Show success notification (you can implement a proper notification system)
        alert("User invitation sent successfully!");
      } catch (error) {
        console.error("Error inviting user:", error);
        alert("Error sending invitation. Please try again.");
      }
    },
  },
};
</script>

<style scoped>
/* Modern Project View Design */
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
  background: var(--background);
  padding: 0;
}

/* Project Header */
.project-header {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 24px 32px;
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
}

.project-info {
  flex: 1;
}

.project-title {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
  text-transform: capitalize;
}

.project-subtitle {
  margin: 4px 0 0 0;
  color: var(--text-secondary);
  font-size: 16px;
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
  background: var(--background);
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
}

/* Main Content */
.main-content {
  max-width: 1200px;
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
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
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
  padding: 12px 20px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.add-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-lg);
  color: white;
}

.icon-btn {
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

.icon-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
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
  padding: 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.tool-card:hover,
.component-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-color);
}

.tool-card::before,
.component-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  transform: scaleX(0);
  transition: transform 0.2s ease;
}

.tool-card:hover::before,
.component-card:hover::before {
  transform: scaleX(1);
}

.card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: var(--radius-lg);
  margin-bottom: 16px;
}

.card-icon ion-icon {
  font-size: 28px;
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
  margin-bottom: 16px;
}

.card-title {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.card-description {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.card-actions {
  display: flex;
  justify-content: flex-end;
}

.card-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
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
  transform: scale(1.1);
}

/* User Cards */
.user-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
}

.user-card:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: 50%;
  flex-shrink: 0;
}

.user-avatar ion-icon {
  font-size: 24px;
  color: white;
}

.user-info {
  flex: 1;
}

.user-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.user-actions {
  flex-shrink: 0;
}

/* Download Section */
.download-section {
  margin-bottom: 24px;
}

.download-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: var(--success-color);
  color: white;
  border-radius: var(--radius);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
}

.download-link:hover {
  background: #047857;
  transform: translateY(-1px);
  color: white;
}

/* Empty States */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 32px;
  text-align: center;
  background: var(--surface);
  border: 2px dashed var(--border);
  border-radius: var(--radius-lg);
}

.empty-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
}

.empty-action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.empty-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-lg);
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
  padding: 24px 24px 0;
  border-bottom: 1px solid var(--border);
  margin-bottom: 24px;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
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
  background: var(--background);
  color: var(--text-primary);
}

.modal-content {
  padding: 0 24px 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
}

.input-container {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 18px;
}

.form-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--surface);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 24px;
  border-top: 1px solid var(--border);
}

.btn-secondary {
  padding: 12px 24px;
  background: var(--background);
  color: var(--text-secondary);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-weight: 500;
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
  padding: 12px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
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

/* Responsive Design */
@media (max-width: 768px) {
  .project-header {
    padding: 20px 16px;
  }

  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .main-content {
    padding: 0 16px 16px;
  }

  .section-header {
    flex-direction: column;
    gap: 16px;
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
    font-size: 24px;
  }
}

@media (max-width: 480px) {

  .tool-card,
  .component-card,
  .user-card {
    padding: 16px;
  }

  .empty-state {
    padding: 48px 24px;
  }
}
</style>
