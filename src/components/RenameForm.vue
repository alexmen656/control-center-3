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
            :disabled="!isValidName || loading"
          >
            <ion-spinner v-if="loading" name="crescent"></ion-spinner>
            <span v-else>Form umbenennen</span>
          </ion-button>
        </div>
        
        <ion-alert
          :is-open="showAlert"
          header="Fehler"
          :message="alertMessage"
          :buttons="['OK']"
          @didDismiss="showAlert = false"
        ></ion-alert>
        
        <ion-alert
          :is-open="showSuccessAlert"
          header="Erfolg"
          message="Form wurde erfolgreich umbenannt!"
          :buttons="['OK']"
          @didDismiss="handleSuccess"
        ></ion-alert>
      </ion-card-content>
    </ion-card>
  </ion-content>
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
      loading: false,
      showError: false,
      showAlert: false,
      showSuccessAlert: false,
      alertMessage: '',
      formExists: false
    };
  },
  computed: {
    isValidName() {
      if (!this.newFormName) return false;
      // Allow letters, numbers, hyphens and underscores
      const nameRegex = /^[a-zA-Z0-9-_]+$/;
      return nameRegex.test(this.newFormName) && this.newFormName !== this.form;
    }
  },
  watch: {
    newFormName() {
      this.showError = false;
      this.formExists = false;
    }
  },
  methods: {
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
        return response.data.exists === true;
      } catch (error) {
        console.error('Error checking form existence:', error);
        return false;
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
      
      this.loading = true;
      
      try {
        // Check if form with new name already exists
        const exists = await this.checkFormExists();
        if (exists) {
          this.formExists = true;
          this.showError = true;
          this.loading = false;
          return;
        }
        
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
          this.showSuccessAlert = true;
        } else {
          this.alertMessage = response.data.error || 'Fehler beim Umbenennen der Form';
          this.showAlert = true;
        }
      } catch (error) {
        console.error('Error renaming form:', error);
        this.alertMessage = 'Netzwerkfehler beim Umbenennen der Form';
        this.showAlert = true;
      } finally {
        this.loading = false;
      }
    },
    
    handleSuccess() {
      this.showSuccessAlert = false;
      this.$emit('sidebarRefresh'); // Trigger sidebar refresh
      this.$emit('success', this.newFormName);
    }
  }
});
</script>

<style scoped>
.ion-invalid {
  --border-color: var(--ion-color-danger);
}

ion-card {
  margin: 0;
  height: 100%;
}

ion-button[disabled] {
  opacity: 0.6;
}
</style>
