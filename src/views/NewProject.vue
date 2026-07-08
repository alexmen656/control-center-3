<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="add-circle-outline" title="New Project" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Create New Project</h1>
            <p>Start a new project from scratch</p>
          </div>
        </div>

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
          </div>
        </div>

        <div class="action-section">
          <ActionButton
            variant="primary"
            @click="createWithoutTemplate"
            :disabled="!name"
          >
            Create Project
          </ActionButton>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from '@/components/SiteTitle.vue';
import ActionButton from '@/components/ActionButton.vue';
import { defineComponent } from 'vue';

export default defineComponent({
  name: "NewProject",
  components: {
    SiteTitle,
    ActionButton
  },
  data() {
    return {
      name: "",
      icon: "folder-outline",
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
.page-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}

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

.action-section {
  margin-top: 32px;
}

.action-section :deep(.action-btn) {
  width: 100%;
  justify-content: center;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
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
</style>
