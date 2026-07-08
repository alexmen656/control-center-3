<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="folder-outline" title="Manage Projects" />
      <div class="page-container">
        <PageHeader icon="folder-outline" title="Project Management">
          <template #actions>
            <ActionButton icon="refresh-outline" @click="refreshProjects">
              Refresh
            </ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">
              New Project
            </ActionButton>
          </template>
        </PageHeader>
        <div class="stats-grid">
          <StatCard icon="folder-outline" color="primary" :value="totalProjects" label="Total Projects" />
          <StatCard icon="eye-outline" color="success" :value="visibleProjects" label="Visible in Sidebar" />
          <StatCard icon="eye-off-outline" color="warning" :value="hiddenProjects" label="Hidden Projects" />
          <StatCard icon="calendar-outline" color="info" :value="recentProjects" label="Created this week" />
        </div>
        <DataCard title="Your Projects">
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search projects..." />
          </template>

          <LoadingState v-if="loading" message="Loading projects..." />

          <EmptyState v-else-if="filteredProjects.length === 0" icon="folder-outline" title="No Projects Found"
            :description="searchTerm ? 'No projects match your search criteria.' : 'You haven\'t created any projects yet.'">
            <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">
              Create Your First Project
            </ActionButton>
          </EmptyState>

          <div v-else class="projects-grid">
            <div v-for="project in filteredProjects" :key="project.id" class="project-card" :class="{
              'project-hidden': project.hidden
            }">
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
                  <span class="status-badge" :class="project.hidden ? 'status-hidden' : 'status-visible'">
                    <ion-icon :name="project.hidden ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                    {{ project.hidden ? 'Hidden' : 'Visible' }}
                  </span>
                </div>
              </div>

              <div class="project-actions">
                <button class="icon-btn info-btn" @click="info(project)" title="Project Info">
                  <ion-icon name="information-circle-outline"></ion-icon>
                </button>
                <button class="icon-btn toggle-btn" @click="toggleProjectVisibility(project)"
                  :title="project.hidden ? 'Show in Sidebar' : 'Hide from Sidebar'">
                  <ion-icon :name="project.hidden ? 'eye-outline' : 'eye-off-outline'"></ion-icon>
                </button>
                <button class="icon-btn edit-btn" @click="editProject(project)" title="Edit Project">
                  <ion-icon name="pencil-outline"></ion-icon>
                </button>
                <button class="icon-btn delete-btn" @click="confirmDelete(project)" title="Delete Project">
                  <ion-icon name="trash-outline"></ion-icon>
                </button>
              </div>
            </div>
          </div>
        </DataCard>
      </div>
      <AppModal v-model="showCreateModal" title="Create New Project">
        <div class="form-group">
          <label for="project-name">Project Name</label>
          <input id="project-name" type="text" v-model="newProject.name" placeholder="Enter project name"
            class="form-input">
        </div>
        <div class="form-group">
          <label for="project-icon">Icon</label>
          <input id="project-icon" type="text" v-model="newProject.icon"
            placeholder="Enter Ionic icon name (e.g., folder-outline)" class="form-input">
          <div class="icon-preview" v-if="newProject.icon">
            <ion-icon :name="newProject.icon"></ion-icon>
            <span>Preview</span>
          </div>
        </div>
        <template #footer>
          <ActionButton @click="showCreateModal = false">
            Cancel
          </ActionButton>
          <ActionButton variant="primary" @click="createProject" :disabled="!newProject.name.trim()">
            Create Project
          </ActionButton>
        </template>
      </AppModal>
      <AppModal v-model="showEditModal" title="Edit Project">
        <div class="form-group">
          <label for="edit-project-name">Project Name</label>
          <input id="edit-project-name" type="text" v-model="editingProject.name" placeholder="Enter project name"
            class="form-input">
        </div>
        <div class="form-group">
          <label for="edit-project-icon">Icon</label>
          <input id="edit-project-icon" type="text" v-model="editingProject.icon" placeholder="Enter Ionic icon name"
            class="form-input">
          <div class="icon-preview" v-if="editingProject.icon">
            <ion-icon :name="editingProject.icon"></ion-icon>
            <span>Preview</span>
          </div>
        </div>
        <template #footer>
          <ActionButton @click="showEditModal = false">
            Cancel
          </ActionButton>
          <ActionButton variant="primary" @click="updateProject">
            Update Project
          </ActionButton>
        </template>
      </AppModal>
      <AppModal v-model="deleteModal.show" title="Delete Project">
        <div class="warning-content">
          <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
          <h4>Are you sure?</h4>
          <p>This will permanently delete the project <strong>"{{ deleteModal.project?.name }}"</strong> and all its
            data.</p>
          <p class="warning-text">This action cannot be undone!</p>
        </div>
        <template #footer>
          <ActionButton @click="deleteModal.show = false">
            Cancel
          </ActionButton>
          <ActionButton variant="danger" @click="deleteProject()">
            Delete Permanently
          </ActionButton>
        </template>
      </AppModal>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import ActionButton from "@/components/ActionButton.vue";
import AppModal from "@/components/AppModal.vue";
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageView",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    DataCard,
    SearchBox,
    LoadingState,
    EmptyState,
    ActionButton,
    AppModal,
  },
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

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
  background: rgba(249, 115, 22, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
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
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.info-btn:hover {
  background: rgba(249, 115, 22, 0.22);
}

.toggle-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.toggle-btn:hover {
  background: #fde68a;
}

.edit-btn {
  background: rgba(45, 211, 111, 0.12);
  color: var(--success-color);
}

.edit-btn:hover {
  background: rgba(45, 211, 111, 0.22);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
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

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .projects-grid {
    grid-template-columns: 1fr;
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
