<template>
  <ion-header>
    <ion-toolbar>
      <ion-title>Form umbenennen</ion-title>
      <ion-buttons slot="end">
        <ion-button @click="$emit('close')">
          <ion-icon name="close"></ion-icon>
        </ion-button>
      </ion-buttons>
    </ion-toolbar>
  </ion-header>
  
  <ion-content class="ion-padding">
    <ion-card>
      <ion-card-header>
        <ion-card-title>Form "{{ form }}" umbenennen</ion-card-title>
        <ion-card-subtitle>Geben Sie den neuen Namen für die Form ein</ion-card-subtitle>
      </ion-card-header>
      
      <ion-card-content>
        <ion-item>
          <ion-label position="stacked">Aktueller Name</ion-label>
          <ion-input :value="form" readonly></ion-input>
        </ion-item>
        
        <ion-item>
          <ion-label position="stacked">Neuer Name *</ion-label>
          <ion-input
            v-model="newFormName"
            placeholder="Neuen Formnamen eingeben"
            :class="{ 'ion-invalid': showError && !isValidName }"
          ></ion-input>
          <ion-note slot="helper" v-if="!showError">
            Nur Buchstaben, Zahlen und Bindestriche erlaubt
          </ion-note>
          <ion-note slot="error" v-if="showError && !newFormName">
            Name ist erforderlich
          </ion-note>
          <ion-note slot="error" v-if="showError && newFormName && !isValidName">
            Ungültiger Name. Verwenden Sie nur Buchstaben, Zahlen und Bindestriche
          </ion-note>
          <ion-note slot="error" v-if="showError && formExists">
            Eine Form mit diesem Namen existiert bereits
          </ion-note>
        </ion-item>
        
        <div class="ion-margin-top">
          <ion-button 
            expand="block" 
            @click="renameForm"
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
