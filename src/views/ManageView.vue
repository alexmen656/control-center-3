<template>
  <ion-page>
    <ion-content class="modern-content">

      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Project Management</h1>
            <p>Manage your projects and create new ones</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshProjects">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="showCreateModal = true">
              <ion-icon name="add-outline"></ion-icon>
              New Project
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="folder-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ totalProjects }}</h3>
              <p>Total Projects</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="eye-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ visibleProjects }}</h3>
              <p>Visible in Sidebar</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="eye-off-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ hiddenProjects }}</h3>
              <p>Hidden Projects</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="calendar-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ recentProjects }}</h3>
              <p>Created this week</p>
            </div>
          </div>
        </div>

        <!-- Projects List -->
        <div class="projects-card">
          <div class="card-header">
            <h2>Your Projects</h2>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input 
                type="text" 
                placeholder="Search projects..." 
                v-model="searchTerm"
              >
            </div>
          </div>

          <div class="projects-container">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading projects...</p>
            </div>

            <div v-else-if="filteredProjects.length === 0" class="no-data-state">
              <ion-icon name="folder-outline" class="no-data-icon"></ion-icon>
              <h3>No Projects Found</h3>
              <p>{{ searchTerm ? 'No projects match your search criteria.' : 'You haven\'t created any projects yet.' }}</p>
              <button class="action-btn primary" @click="showCreateModal = true">
                <ion-icon name="add-outline"></ion-icon>
                Create Your First Project
              </button>
            </div>

            <div v-else class="projects-grid">
              <div 
                v-for="project in filteredProjects" 
                :key="project.id"
                class="project-card"
                :class="{
                  'project-hidden': project.hidden
                }"
              >
                <div class="project-header">
                  <div class="project-info">
                    <div class="project-icon">
                      <ion-icon :name="project.icon || 'folder-outline'"></ion-icon>
                    </div>
                    <div class="project-details">
                      <h3 class="project-name">{{ project.name }}</h3>
                      <p class="project-link">{{ project.link }}</p>
                    </div>
                  </div>
                  <div class="project-status">
                    <span 
                      class="status-badge"
                      :class="project.hidden ? 'status-hidden' : 'status-visible'"
                    >
                      <ion-icon :name="project.hidden ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                      {{ project.hidden ? 'Hidden' : 'Visible' }}
                    </span>
                  </div>
                </div>

                <div class="project-actions">
                  <button 
                    class="icon-btn info-btn" 
                    @click="info(project)"
                    title="Project Info"
                  >
                    <ion-icon name="information-circle-outline"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn toggle-btn"
                    @click="toggleProjectVisibility(project)"
                    :title="project.hidden ? 'Show in Sidebar' : 'Hide from Sidebar'"
                  >
                    <ion-icon :name="project.hidden ? 'eye-outline' : 'eye-off-outline'"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn edit-btn"
                    @click="editProject(project)"
                    title="Edit Project"
                  >
                    <ion-icon name="pencil-outline"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn delete-btn"
                    @click="confirmDelete(project)"
                    title="Delete Project"
                  >
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Project Modal -->
      <div v-if="showCreateModal" class="custom-modal-overlay" @click="showCreateModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Create New Project</h3>
            <button class="modal-close-btn" @click="showCreateModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label for="project-name">Project Name</label>
              <input 
                id="project-name"
                type="text" 
                v-model="newProject.name"
                placeholder="Enter project name"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="project-icon">Icon</label>
              <input 
                id="project-icon"
                type="text" 
                v-model="newProject.icon"
                placeholder="Enter Ionic icon name (e.g., folder-outline)"
                class="form-input"
              >
              <div class="icon-preview" v-if="newProject.icon">
                <ion-icon :name="newProject.icon"></ion-icon>
                <span>Preview</span>
              </div>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showCreateModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="createProject" :disabled="!newProject.name.trim()">
                Create Project
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Project Modal -->
      <div v-if="showEditModal" class="custom-modal-overlay" @click="showEditModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Edit Project</h3>
            <button class="modal-close-btn" @click="showEditModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label for="edit-project-name">Project Name</label>
              <input 
                id="edit-project-name"
                type="text" 
                v-model="editingProject.name"
                placeholder="Enter project name"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="edit-project-icon">Icon</label>
              <input 
                id="edit-project-icon"
                type="text" 
                v-model="editingProject.icon"
                placeholder="Enter Ionic icon name"
                class="form-input"
              >
              <div class="icon-preview" v-if="editingProject.icon">
                <ion-icon :name="editingProject.icon"></ion-icon>
                <span>Preview</span>
              </div>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="updateProject">
                Update Project
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="deleteModal.show" class="custom-modal-overlay" @click="deleteModal.show = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Delete Project</h3>
            <button class="modal-close-btn" @click="deleteModal.show = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="warning-content">
              <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
              <h4>Are you sure?</h4>
              <p>This will permanently delete the project <strong>"{{ deleteModal.project?.name }}"</strong> and all its data.</p>
              <p class="warning-text">This action cannot be undone!</p>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="deleteModal.show = false">
                Cancel
              </button>
              <button class="action-btn danger" @click="deleteProject()">
                Delete Permanently
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageView",
  data() {
    return {
      projects: [],
      loading: true,
      searchTerm: '',
      showCreateModal: false,
      showEditModal: false,
      newProject: {
        name: '',
        icon: ''
      },
      editingProject: {
        id: null,
        name: '',
        icon: ''
      },
      deleteModal: {
        show: false,
        project: null
      }
    };
  },
  computed: {
    filteredProjects() {
      if (!this.searchTerm.trim()) {
        return this.projects;
      }
      
      const searchLower = this.searchTerm.toLowerCase();
      return this.projects.filter(project =>
        project.name.toLowerCase().includes(searchLower) ||
        project.link.toLowerCase().includes(searchLower)
      );
    },
    totalProjects() {
      return this.projects.length;
    },
    visibleProjects() {
      return this.projects.filter(project => !project.hidden).length;
    },
    hiddenProjects() {
      return this.projects.filter(project => project.hidden).length;
    },
    recentProjects() {
      const oneWeekAgo = new Date();
      oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
      return this.projects.filter(project => {
        const createdDate = new Date(project.createdOn || project.date);
        return createdDate >= oneWeekAgo;
      }).length;
    }
  },
  created() {
    this.loadProjects();
  },
  methods: {
    async loadProjects() {
      this.loading = true;
      try {
        const response = await this.$axios.get("projects.php");
        // Add hidden property to each project (default false for now)
        this.projects = response.data.map(project => ({
          ...project,
          hidden: project.hidden || false
        }));
      } catch (error) {
        console.error('Error loading projects:', error);
        this.projects = [];
      } finally {
        this.loading = false;
      }
    },
    refreshProjects() {
      this.loadProjects();
    },
    async createProject() {
      if (!this.newProject.name.trim()) {
        alert("Project name is required!");
        return;
      }

      try {
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            createProject: "createProject",
            projectName: this.newProject.name,
            projectIcon: this.newProject.icon,
          })
        );
        
        alert("Project created successfully");
        this.showCreateModal = false;
        this.newProject = { name: '', icon: '' };
        await this.loadProjects();
        //this.$emit("updateSidebar");
        this.emitter.emit("updateSidebar");
      } catch (error) {
        console.error('Error creating project:', error);
        alert("Error creating project");
      }
    },
    editProject(project) {
      this.editingProject = {
        id: project.id,
        name: project.name,
        icon: project.icon
      };
      this.showEditModal = true;
    },
    async updateProject() {
      try {
        // Note: We'll need to add this endpoint to the backend
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            updateProject: "updateProject",
            projectID: this.editingProject.id,
            projectName: this.editingProject.name,
            projectIcon: this.editingProject.icon,
          })
        );
        
        alert("Project updated successfully");
        this.showEditModal = false;
        await this.loadProjects();
        //this.$emit("updateSidebar");
        this.emitter.emit("updateSidebar");
      } catch (error) {
        console.error('Error updating project:', error);
        alert("Error updating project");
      }
    },
    async toggleProjectVisibility(project) {
      try {
        // Note: We'll need to add this endpoint to the backend
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            toggleProjectVisibility: "toggleProjectVisibility",
            projectID: project.id,
            hidden: !project.hidden,
          })
        );
        
        project.hidden = !project.hidden;
        //this.$emit("updateSidebar");
        this.emitter.emit("updateSidebar");
      } catch (error) {
        console.error('Error toggling project visibility:', error);
        alert("Error updating project visibility");
      }
    },
    confirmDelete(project) {
      this.deleteModal.project = project;
      this.deleteModal.show = true;
    },
    async deleteProject() {
      if (!this.deleteModal.project) return;
      
      try {
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            deleteProject: "deleteProject",
            projectID: this.deleteModal.project.id,
          })
        );
        
        alert("Project deleted successfully");
        this.projects = this.projects.filter(p => p.id !== this.deleteModal.project.id);
        this.deleteModal.show = false;
        this.deleteModal.project = null;
        //this.$emit("updateSidebar");
        this.emitter.emit("updateSidebar");
      } catch (error) {
        console.error('Error deleting project:', error);
        alert("Error deleting project");
      }
    },
    info(project) {
      location.href = `/project/${project.link}/info`;
    },
    // Legacy method for compatibility
    submit(icon, name) {
      this.newProject.icon = icon;
      this.newProject.name = name;
      this.createProject();
    }
  },
});
</script>
<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn.danger:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Projects Card */
.projects-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.card-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-box input {
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--background);
  color: var(--text-primary);
  min-width: 250px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Projects Container */
.projects-container {
  padding: 24px;
}

.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-state p {
  margin: 0 0 16px 0;
  font-size: 14px;
}

/* Projects Grid */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.project-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
  border-left: 4px solid var(--success-color);
}

.project-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.project-card.project-hidden {
  border-left: 4px solid var(--warning-color);
  background: #fefce8;
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.project-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.project-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}

.project-details h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.project-link {
  margin: 0;
  color: var(--text-muted);
  font-size: 12px;
  font-family: monospace;
}

.project-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
}

.status-visible {
  background: #f0fdf4;
  color: var(--success-color);
}

.status-hidden {
  background: #fef3c7;
  color: var(--warning-color);
}

/* Project Actions */
.project-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.info-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.info-btn:hover {
  background: #dbeafe;
}

.toggle-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.toggle-btn:hover {
  background: #fde68a;
}

.edit-btn {
  background: #f0fdf4;
  color: var(--success-color);
}

.edit-btn:hover {
  background: #dcfce7;
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
}

/* Modal Styles */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 500px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
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

.icon-preview {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 12px;
  background: var(--background);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-secondary);
}

.icon-preview ion-icon {
  font-size: 18px;
  color: var(--primary-color);
}

.warning-content {
  text-align: center;
  margin-bottom: 24px;
}

.warning-icon {
  font-size: 48px;
  color: var(--warning-color);
  margin-bottom: 16px;
}

.warning-content h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.warning-content p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

.warning-text {
  color: var(--danger-color);
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box input {
    min-width: 100%;
  }
  
  .projects-grid {
    grid-template-columns: 1fr;
  }
  
  .custom-modal-content {
    width: 95vw;
    margin: 20px;
  }
  
  .project-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .project-status {
    align-self: flex-start;
  }
}
</style>
