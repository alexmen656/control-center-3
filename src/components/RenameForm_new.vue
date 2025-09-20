<template>
  <div class="modern-modal">
    <div class="modal-overlay" @click="$emit('close')">
      <div class="modal-container" @click.stop>
        <!-- Modal Header -->
        <div class="modal-header">
          <div class="header-content">
            <h2>Rename Form</h2>
            <p>Change the name of your form "{{ form }}"</p>
          </div>
          <button class="close-btn" @click="$emit('close')">
            <ion-icon name="close-outline"></ion-icon>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="modal-content">
          <div class="form-section">
            <div class="form-group">
              <label class="form-label">Current Name</label>
              <div class="current-name-display">
                <ion-icon name="document-text-outline" class="form-icon"></ion-icon>
                <span class="current-name">{{ form }}</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">New Name *</label>
              <div class="input-container">
                <input 
                  v-model="newFormName"
                  type="text"
                  class="modern-input"
                  :class="{ 'error': showError && !isValidName }"
                  placeholder="Enter new form name"
                  @input="validateName"
                  @keyup.enter="renameForm"
                  ref="nameInput"
                >
                <div class="input-status">
                  <ion-icon 
                    v-if="newFormName && isValidName && !formExists" 
                    name="checkmark-circle-outline" 
                    class="status-icon success"
                  ></ion-icon>
                  <ion-icon 
                    v-else-if="showError && (!isValidName || formExists)" 
                    name="close-circle-outline" 
                    class="status-icon error"
                  ></ion-icon>
                </div>
              </div>
              
              <div class="input-feedback">
                <div v-if="!showError" class="hint">
                  <ion-icon name="information-circle-outline"></ion-icon>
                  Only letters, numbers, and hyphens allowed
                </div>
                <div v-else-if="!newFormName" class="error-message">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  Form name is required
                </div>
                <div v-else-if="!isValidName" class="error-message">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  Invalid name. Use only letters, numbers, and hyphens
                </div>
                <div v-else-if="formExists" class="error-message">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  A form with this name already exists
                </div>
                <div v-else-if="isValidName" class="success-message">
                  <ion-icon name="checkmark-circle-outline"></ion-icon>
                  Name is available
                </div>
              </div>
            </div>

            <!-- Preview Section -->
            <div v-if="newFormName && isValidName" class="preview-section">
              <h4>Preview</h4>
              <div class="preview-card">
                <div class="preview-item">
                  <span class="preview-label">Old URL:</span>
                  <code class="preview-url old">/project/{{ project }}/form/{{ form }}</code>
                </div>
                <div class="arrow-down">
                  <ion-icon name="arrow-down-outline"></ion-icon>
                </div>
                <div class="preview-item">
                  <span class="preview-label">New URL:</span>
                  <code class="preview-url new">/project/{{ project }}/form/{{ newFormName }}</code>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
              <button class="secondary-btn" @click="$emit('close')">
                <ion-icon name="close-outline"></ion-icon>
                Cancel
              </button>
              <button 
                class="primary-btn"
                @click="renameForm"
                :disabled="!canRename"
                :class="{ 'loading': isLoading }"
              >
                <ion-icon v-if="!isLoading" name="create-outline"></ion-icon>
                <div v-else class="spinner"></div>
                {{ isLoading ? 'Renaming...' : 'Rename Form' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'RenameForm',
  props: {
    project: {
      type: String,
      required: true
    },
    form: {
      type: String,
      required: true
    }
  },
  emits: ['close', 'success', 'sidebarRefresh'],
  data() {
    return {
      newFormName: '',
      isLoading: false,
      showError: false,
      formExists: false
    };
  },
  computed: {
    isValidName() {
      if (!this.newFormName) return false;
      // Allow letters, numbers, hyphens and underscores
      const nameRegex = /^[a-zA-Z0-9-_]+$/;
      return nameRegex.test(this.newFormName) && this.newFormName !== this.form;
    },
    canRename() {
      return this.isValidName && !this.formExists && !this.isLoading;
    }
  },
  mounted() {
    // Focus the input when modal opens
    this.$nextTick(() => {
      if (this.$refs.nameInput) {
        this.$refs.nameInput.focus();
      }
    });
  },
  methods: {
    validateName() {
      this.showError = false;
      this.formExists = false;
      
      if (this.newFormName && this.isValidName) {
        this.checkFormExists();
      }
    },
    
    async checkFormExists() {
      try {
        const response = await this.$axios.post(
          'form.php',
          this.$qs.stringify({
            check_form_exists: 'check_form_exists',
            form_name: this.newFormName,
            project: this.project
          })
        );
        this.formExists = response.data.exists === true;
      } catch (error) {
        console.error('Error checking form existence:', error);
        this.formExists = false;
      }
    },
    
    async renameForm() {
      this.showError = false;
      
      if (!this.newFormName) {
        this.showError = true;
        return;
      }
      
      if (!this.isValidName) {
        this.showError = true;
        return;
      }
      
      // Check if form exists one more time
      await this.checkFormExists();
      if (this.formExists) {
        this.showError = true;
        return;
      }
      
      this.isLoading = true;
      
      try {
        const response = await this.$axios.post(
          'form.php',
          this.$qs.stringify({
            rename_form: 'rename_form',
            old_form_name: this.form,
            new_form_name: this.newFormName,
            project: this.project
          })
        );
        
        if (response.data.success) {
          // Emit events to update the parent components
          this.$emit('sidebarRefresh');
          this.$emit('success', this.newFormName);
        } else {
          console.error('Rename failed:', response.data.message);
          alert('Error renaming form: ' + (response.data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('Error renaming form:', error);
        alert('Error renaming form. Please try again.');
      } finally {
        this.isLoading = false;
      }
    }
  }
});
</script>

<style scoped>
/* Modern Modal Design */
.modern-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10000;
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --surface: #ffffff;
  --background: #f8fafc;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: fadeIn 0.2s ease-out;
}

.modal-container {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  width: 100%;
  max-width: 500px;
  overflow: hidden;
  animation: slideIn 0.3s ease-out;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.header-content h2 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: var(--radius);
  background: var(--border);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: var(--text-muted);
  color: var(--surface);
}

.modal-content {
  padding: 24px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

/* Current Name Display */
.current-name-display {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.form-icon {
  color: var(--text-secondary);
  font-size: 18px;
}

.current-name {
  font-weight: 500;
  color: var(--text-primary);
}

/* Input Container */
.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.modern-input {
  flex: 1;
  padding: 12px 40px 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  transition: all 0.2s ease;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-input.error {
  border-color: var(--danger-color);
}

.modern-input.error:focus {
  box-shadow: 0 0 0 3px rgb(220 38 38 / 0.1);
}

.input-status {
  position: absolute;
  right: 12px;
  display: flex;
  align-items: center;
}

.status-icon {
  font-size: 18px;
}

.status-icon.success {
  color: var(--success-color);
}

.status-icon.error {
  color: var(--danger-color);
}

/* Input Feedback */
.input-feedback {
  min-height: 20px;
}

.hint,
.error-message,
.success-message {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.hint {
  color: var(--text-muted);
}

.error-message {
  color: var(--danger-color);
}

.success-message {
  color: var(--success-color);
}

.hint ion-icon,
.error-message ion-icon,
.success-message ion-icon {
  font-size: 14px;
}

/* Preview Section */
.preview-section {
  padding: 20px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.preview-section h4 {
  margin: 0 0 16px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.preview-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.preview-label {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.preview-url {
  padding: 8px 12px;
  border-radius: 6px;
  font-family: 'SF Mono', Monaco, monospace;
  font-size: 13px;
  background: var(--surface);
  border: 1px solid var(--border);
}

.preview-url.old {
  color: var(--text-muted);
  text-decoration: line-through;
}

.preview-url.new {
  color: var(--success-color);
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.05);
}

.arrow-down {
  display: flex;
  justify-content: center;
  color: var(--text-muted);
}

/* Buttons */
.button-group {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}

.primary-btn,
.secondary-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 120px;
  justify-content: center;
}

.secondary-btn {
  background: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.secondary-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.primary-btn {
  background: var(--primary-color);
  color: white;
}

.primary-btn:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.primary-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.primary-btn.loading {
  cursor: not-allowed;
}

/* Spinner */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s ease-in-out infinite;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to { 
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    margin: 8px;
  }
  
  .button-group {
    flex-direction: column;
  }
  
  .primary-btn,
  .secondary-btn {
    min-width: auto;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-modal {
    --surface: #1e293b;
    --background: #0f172a;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
  
  .modern-input {
    background: var(--background);
    color: var(--text-primary);
  }
  
  .preview-url {
    background: var(--background);
  }
  
  .preview-url.new {
    background: rgba(5, 150, 105, 0.1);
  }
}
</style>
