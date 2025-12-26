<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="add-circle-outline" title="New Project" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Create New Project</h1>
            <p>Start a new project from scratch or use a template</p>
          </div>
        </div>

        <!-- Project Creation Form -->
        <div class="create-card">
          <div class="card-header">
            <h3>Project Details</h3>
            <p class="card-subtitle">Configure your new project settings</p>
          </div>

          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Project Icon</label>
              <div class="icon-input-wrapper">
                <input
                  type="text"
                  v-model="icon"
                  @input="icon = $event.target.value"
                  placeholder="Enter Ionicon name (e.g., folder-outline)"
                  class="modern-input"
                />
                <div class="icon-preview">
                  <ion-icon :name="icon || 'help-circle-outline'" size="large"></ion-icon>
                </div>
              </div>
              <p class="form-help">
                Browse icons at <a href="https://ionic.io/ionicons" target="_blank">ionic.io/ionicons</a>
              </p>
            </div>

            <div class="form-group">
              <label class="form-label">Project Name *</label>
              <input
                v-model="name"
                @input="name = $event.target.value"
                type="text"
                placeholder="Enter project name"
                class="modern-input"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Project Template</label>
              <project-template-selector v-model="selectedTemplateId" />
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
          <div class="action-cards">
            <div class="action-option-card" :class="{ disabled: !name }">
              <div class="option-icon empty">
                <ion-icon name="document-outline"></ion-icon>
              </div>
              <div class="option-content">
                <h4>Empty Project</h4>
                <p>Start with a blank project and build from scratch</p>
              </div>
              <button
                class="action-btn secondary"
                @click="createWithoutTemplate"
                :disabled="!name"
              >
                Create Empty
              </button>
            </div>

            <div class="action-option-card" :class="{ disabled: !name || !selectedTemplateId }">
              <div class="option-icon template">
                <ion-icon name="layers-outline"></ion-icon>
              </div>
              <div class="option-content">
                <h4>From Template</h4>
                <p>Use a pre-configured template with starter content</p>
              </div>
              <button
                class="action-btn primary"
                @click="createFromTemplate"
                :disabled="!name || !selectedTemplateId"
              >
                Create From Template
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import ProjectTemplateSelector from '@/components/ProjectTemplateSelector.vue';
import SiteTitle from '@/components/SiteTitle.vue';
import { defineComponent } from 'vue';

export default defineComponent({
  name: "NewProject",
  components: {
    ProjectTemplateSelector,
    SiteTitle
  },
  data() {
    return {
      name: "",
      icon: "folder-outline",
      selectedTemplateId: null,
      createGithubRepo: false
    };
  },
  methods: {
    createWithoutTemplate() {
      if (this.name) {
        this.createProject();
      } else {
        this.showError("Project Name is empty!");
      }
    },

    async createFromTemplate() {
      if (!this.name) {
        this.showError("Project Name is empty!");
        return;
      }

      if (!this.selectedTemplateId) {
        this.showError("Please select a template");
        return;
      }

      try {
        const response = await this.$axios.post(
          "project_templates.php",
          this.$qs.stringify({
            action: "apply",
            template_id: this.selectedTemplateId,
            project_name: this.name,
            project_icon: this.icon || "folder-outline"
          })
        );
        if (response.data.success) {
          this.showSuccess("Project created successfully from template!");
          this.emitter.emit("updateSidebar");
          this.$router.push(`/project/${this.name}/`);
        } else {
          this.showError(response.data.message || "Failed to create project from template");
        }
      } catch (error) {
        console.error("Error creating project from template:", error);
        this.showError("Network or server error");
      }
    },

    async createProject() {
      try {
        await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            createProject: "createProject",
            projectName: this.name,
            projectIcon: this.icon,
          })
        );

        this.showSuccess("Project created successfully");
        this.emitter.emit("updateSidebar");
        this.$router.push(`/project/${this.name.toLowerCase().replace(/\s+/g, '-')}/`);
      } catch (error) {
        console.error("Error creating project:", error);
        this.showError("Network or server error");
      }
    },

    showSuccess(message) {
      if (this.$toast && typeof this.$toast.success === 'function') {
        this.$toast.success(message);
      } else if (this.$ionic && this.$ionic.toastController) {
        this.$ionic.toastController.create({
          message,
          duration: 2000,
          position: "bottom",
          color: "success"
        }).then(toast => toast.present());
      } else {
        alert(message);
      }
    },

    showError(message) {
      if (this.$toast && typeof this.$toast.error === 'function') {
        this.$toast.error(message);
      } else if (this.$ionic && this.$ionic.toastController) {
        this.$ionic.toastController.create({
          message,
          duration: 3000,
          position: "bottom",
          color: "danger"
        }).then(toast => toast.present());
      } else {
        alert(message);
      }
    }
  },
});
</script>

<style scoped>
.modern-content {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Create Card */
.create-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  margin-bottom: 32px;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
}

.card-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.card-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.card-body {
  padding: 24px;
}

/* Form Elements */
.form-group {
  margin-bottom: 24px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-help {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
}

.form-help a {
  color: var(--primary-color);
  text-decoration: none;
}

.form-help a:hover {
  text-decoration: underline;
}

/* Icon Input */
.icon-input-wrapper {
  display: flex;
  gap: 12px;
  align-items: center;
}

.icon-input-wrapper .modern-input {
  flex: 1;
}

.icon-preview {
  width: 64px;
  height: 64px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--background);
  color: var(--primary-color);
  font-size: 32px;
  flex-shrink: 0;
}

/* Action Section */
.action-section {
  margin-top: 32px;
}

.action-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.action-option-card {
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.action-option-card:not(.disabled):hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.action-option-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.option-icon {
  width: 64px;
  height: 64px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 32px;
  flex-shrink: 0;
}

.option-icon.empty {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.option-icon.template {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
}

.option-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.option-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  margin-top: auto;
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn.secondary:hover:not(:disabled) {
  background: var(--background);
  border-color: var(--text-primary);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border: 1px solid var(--primary-color);
  box-shadow: var(--shadow);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-cards {
    grid-template-columns: 1fr;
  }

  .icon-input-wrapper {
    flex-direction: column;
    align-items: stretch;
  }

  .icon-preview {
    width: 100%;
    height: 80px;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}
</style>
