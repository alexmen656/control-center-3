<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <SiteTitle title="New Service" />
        
        <div class="form-container">
          <div class="form-header">
            <div class="header-icon">
              <ion-icon name="add-circle-outline"></ion-icon>
            </div>
            <h1>Add New Service</h1>
            <p>Create a new service for your project with custom configuration and settings.</p>
          </div>

          <form @submit.prevent="submit" class="service-form">
            <!-- Icon Selection -->
            <div class="form-group">
              <label class="form-label" for="service-icon">Service Icon</label>
              <div class="icon-input-group">
                <div class="icon-preview">
                  <ion-icon 
                    :name="icon || 'help-circle-outline'" 
                    class="preview-icon"
                  ></ion-icon>
                </div>
                <input
                  id="service-icon"
                  type="text"
                  v-model="icon"
                  class="form-input"
                  placeholder="Enter icon name (e.g., 'cog-outline')"
                />
              </div>
              <div class="form-help">
                Choose an Ionic icon name. Preview appears on the left.
              </div>
            </div>

            <!-- Service Name -->
            <div class="form-group">
              <label class="form-label" for="service-name">Service Name</label>
              <input
                id="service-name"
                type="text"
                v-model="name"
                class="form-input"
                placeholder="Enter service name"
                required
              />
              <div class="form-help">
                A descriptive name for your service that will appear in the sidebar.
              </div>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label class="form-label" for="service-description">Description</label>
              <textarea
                id="service-description"
                v-model="description"
                class="form-textarea"
                placeholder="Enter service description"
                rows="4"
              ></textarea>
              <div class="form-help">
                Optional description explaining what this service does.
              </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
              <button
                type="button"
                @click="goBack"
                class="btn btn-secondary"
                :disabled="isSubmitting"
              >
                <ion-icon name="arrow-back-outline"></ion-icon>
                Cancel
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="!name.trim() || isSubmitting"
              >
                <ion-icon 
                  :name="isSubmitting ? 'hourglass-outline' : 'add-outline'"
                  :class="{ 'spinning': isSubmitting }"
                ></ion-icon>
                {{ isSubmitting ? 'Adding...' : 'Add Service' }}
              </button>
            </div>
          </form>

          <!-- Quick Icon Suggestions -->
          <div class="icon-suggestions">
            <h3>Popular Icons</h3>
            <div class="icon-grid">
              <button
                v-for="suggestedIcon in popularIcons"
                :key="suggestedIcon.name"
                type="button"
                @click="selectIcon(suggestedIcon.name)"
                class="icon-suggestion"
                :class="{ 'selected': icon === suggestedIcon.name }"
              >
                <ion-icon :name="suggestedIcon.name"></ion-icon>
                <span>{{ suggestedIcon.label }}</span>
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
import SiteTitle from '@/components/SiteTitle.vue';

export default defineComponent({
  name: "NewService",
  components: {
    SiteTitle
  },
  data() {
    return {
      name: "",
      icon: "cog-outline",
      description: "",
      isSubmitting: false,
      popularIcons: [
        { name: "cog-outline", label: "Settings" },
        { name: "server-outline", label: "Server" },
        { name: "cloud-outline", label: "Cloud" },
        { name: "shield-outline", label: "Security" },
        { name: "analytics-outline", label: "Analytics" },
        { name: "mail-outline", label: "Email" },
        { name: "notifications-outline", label: "Notifications" },
        { name: "lock-closed-outline", label: "Auth" },
        { name: "storefront-outline", label: "Store" },
        { name: "document-text-outline", label: "Docs" },
        { name: "chatbubbles-outline", label: "Chat" },
        { name: "camera-outline", label: "Media" },
        { name: "card-outline", label: "Payment" },
        { name: "globe-outline", label: "Web" },
        { name: "terminal-outline", label: "Terminal" },
        { name: "build-outline", label: "Build" }
      ]
    };
  },
  methods: {
    async submit() {
      if (!this.name.trim()) {
        this.$toast.error("Service name is required");
        return;
      }

      this.isSubmitting = true;

      try {
        const response = await this.$axios.post(
          "services.php",
          this.$qs.stringify({
            addService: "addService",
            name: this.name.trim(),
            icon: this.icon || "cog-outline",
            description: this.description.trim(),
            project: this.$route.params.project,
          })
        );

        if (response.data && response.data.status === "success") {
          this.$toast.success(response.data.message || "Service added successfully");
          
          // Emit event to update sidebar immediately
          this.emitter.emit("updateSidebar");
          
          // Navigate back to the project view
          this.$router.push(`/project/${this.$route.params.project}`);
        } else {
          const errorMessage = response.data && response.data.message 
            ? response.data.message 
            : "Error adding service";
          this.$toast.error(errorMessage);
        }
      } catch (error) {
        console.error("Error adding service:", error);
        this.$toast.error("Error adding service. Please try again.");
      } finally {
        this.isSubmitting = false;
      }
    },
    
    goBack() {
      this.$router.go(-1);
    },
    
    selectIcon(iconName) {
      this.icon = iconName;
    },
    
    formatIconName(name) {
      return name.replace(/-outline$/, '').replace(/-/g, ' ');
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
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.form-container {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

/* Form Header */
.form-header {
  text-align: center;
  padding: 32px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: white;
}

.header-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
}

.form-header h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 700;
}

.form-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 16px;
  line-height: 1.5;
}

/* Form Styles */
.service-form {
  padding: 32px 24px;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

.form-help {
  margin-top: 6px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

/* Icon Input Group */
.icon-input-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.icon-preview {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 24px;
}

.icon-input-group .form-input {
  flex: 1;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  min-width: 120px;
  justify-content: center;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:not(:disabled):hover {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 2px solid var(--border);
}

.btn-secondary:not(:disabled):hover {
  background: var(--background);
  border-color: var(--text-secondary);
}

/* Icon Suggestions */
.icon-suggestions {
  padding: 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

.icon-suggestions h3 {
  margin: 0 0 16px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.icon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
}

.icon-suggestion {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 11px;
  font-weight: 500;
}

.icon-suggestion:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  transform: translateY(-1px);
}

.icon-suggestion.selected {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
  color: var(--primary-color);
}

.icon-suggestion ion-icon {
  font-size: 20px;
}

.icon-suggestion span {
  text-align: center;
  line-height: 1.2;
}

/* Animations */
.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .form-header {
    padding: 24px 16px;
  }
  
  .service-form {
    padding: 24px 16px;
  }
  
  .icon-suggestions {
    padding: 16px;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .icon-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
  
  .icon-input-group {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .icon-preview {
    align-self: center;
  }
}
</style>