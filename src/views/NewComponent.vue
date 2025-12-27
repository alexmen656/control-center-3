<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="add-circle-outline" title="Neues Projekt" />
      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Neues Web Builder Projekt</h1>
          </div>
        </div>
        <div v-if="successMessage" class="success-toast">
          <ion-icon name="checkmark-circle-outline"></ion-icon>
          {{ successMessage }}
        </div>
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Projektdetails</h3>
            </div>
          </div>
          <div class="card-body">
            <div class="modern-edit-form">
              <div class="form-group">
                <label class="form-label">
                  Projektname <span class="required">*</span>
                </label>
                <input v-model="projectName" type="text" placeholder="Geben Sie einen Projektnamen ein"
                  class="modern-input" :disabled="creating" />
                <p class="form-help">Der Name Ihres Web Builder Projekts</p>
              </div>
              <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea v-model="description" placeholder="Geben Sie eine optionale Beschreibung ein"
                  class="modern-input modern-textarea" rows="4" :disabled="creating"></textarea>
                <p class="form-help">Eine kurze Beschreibung des Projekts (optional)</p>
              </div>
              <div v-if="errorMessage" class="error-banner">
                <ion-icon name="alert-circle-outline"></ion-icon>
                <div>
                  <strong>Fehler</strong>
                  <p>{{ errorMessage }}</p>
                </div>
              </div>
              <div class="form-actions">
                <button @click="cancel" :disabled="creating" class="action-btn secondary">
                  <ion-icon name="close-outline"></ion-icon>
                  Abbrechen
                </button>
                <button @click="createProject" :disabled="!canCreate || creating" class="action-btn primary">
                  <ion-icon v-if="creating" name="sync-outline" class="loading-icon"></ion-icon>
                  <ion-icon v-else name="add-outline"></ion-icon>
                  {{ creating ? 'Erstelle...' : 'Projekt Erstellen' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";

export default defineComponent({
  name: "NewComponent",
  components: {
    SiteTitle,
  },
  data() {
    return {
      projectName: "",
      description: "",
      creating: false,
      errorMessage: "",
      successMessage: "",
    };
  },
  computed: {
    canCreate(): boolean {
      return this.projectName.trim() !== "" && !this.creating;
    },
    projectLink(): string {
      return this.$route.params.project as string;
    },
  },
  methods: {
    async createProject() {
      if (!this.canCreate) return;

      this.creating = true;
      this.errorMessage = "";
      this.successMessage = "";

      try {
        const projectResponse = await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            getProject: true,
            link: this.projectLink,
          })
        );

        if (!projectResponse.data.success) {
          this.errorMessage = "Projekt nicht gefunden";
          return;
        }

        const ccProjectId = projectResponse.data?.projectID;
        if (!ccProjectId) {
          this.errorMessage = "Projekt-ID nicht gefunden";
          return;
        }

        const response = await this.$axios.post(
          "/web-builder/projects.php",
          {
            project_id: ccProjectId,
            name: this.projectName.trim(),
            description: this.description.trim(),
          }
        );

        if (response.data.status === "success") {
          this.successMessage = "Projekt erstellt!";
          setTimeout(() => {
            this.$router.push(`/project/${this.projectLink}/wb/${this.projectName.trim().toLowerCase().replace(/\s+/g, "-")}`);
          }, 1000);
        } else {
          this.errorMessage = response.data.message || "Fehler beim Erstellen";
        }
      } catch (error: any) {
        console.error("Error:", error);
        this.errorMessage = error.response?.data?.message || "Fehler beim Erstellen";
      } finally {
        this.creating = false;
      }
    },

    cancel() {
      this.$router.back();
    },
  },
});
</script>

<style scoped>
.modern-content {
  --background: #f8f9fa;
  --surface: #ffffff;
  --border: #e5e7eb;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --success-color: #059669;
  --danger-color: #dc2626;
  --radius: 8px;
  --radius-lg: 12px;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  margin-bottom: 32px;
}

.header-content h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 8px 0;
}

.header-content p {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
}

.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.entry-count {
  font-size: 13px;
  color: var(--text-secondary);
}

.card-body {
  padding: 24px;
}

.modern-edit-form {
  width: 100%;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.required {
  color: var(--danger-color);
  margin-left: 2px;
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

.modern-input:disabled {
  background: var(--background);
  color: var(--text-muted);
  cursor: not-allowed;
}

.modern-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-help {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
}

.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: var(--radius);
  margin-bottom: 24px;
}

.error-banner ion-icon {
  color: var(--danger-color);
  font-size: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.error-banner strong {
  display: block;
  color: var(--danger-color);
  font-weight: 600;
  margin-bottom: 4px;
}

.error-banner p {
  margin: 0;
  color: #991b1b;
  font-size: 14px;
  line-height: 1.5;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn ion-icon {
  font-size: 18px;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover:not(:disabled) {
  background: var(--background);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.loading-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.success-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: rgba(5, 150, 105, 0.95);
  color: white;
  padding: 16px 20px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  z-index: 10001;
  backdrop-filter: blur(8px);
  box-shadow: var(--shadow-lg);
  animation: slideInRight 0.3s ease;
}

.success-toast ion-icon {
  font-size: 24px;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .header-content h1 {
    font-size: 24px;
  }

  .card-header,
  .card-body {
    padding: 16px;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .action-btn {
    width: 100%;
    justify-content: center;
  }

  .success-toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .error-banner {
    background: rgba(220, 38, 38, 0.1);
    border-color: rgba(220, 38, 38, 0.3);
  }

  .error-banner p {
    color: #fca5a5;
  }
}
</style>
