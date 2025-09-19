<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Trigger Management</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">
            <ion-icon name="close-outline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      
      <!-- Create New Trigger Form -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Create New Trigger</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-item>
            <ion-label position="stacked">Trigger Event</ion-label>
            <ion-select v-model="newTrigger.event" placeholder="Select Event">
              <ion-select-option value="insert">On Insert (New Entry)</ion-select-option>
              <ion-select-option value="update">On Update (Edit Entry)</ion-select-option>
              <ion-select-option value="delete">On Delete (Remove Entry)</ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Notification Type</ion-label>
            <ion-select v-model="newTrigger.type" placeholder="Select Type">
              <ion-select-option value="email">Email</ion-select-option>
              <ion-select-option value="telegram">Telegram</ion-select-option>
              <ion-select-option value="discord">Discord</ion-select-option>
              <ion-select-option value="sms">SMS</ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item v-if="newTrigger.type === 'email'">
            <ion-label position="stacked">Email Address</ion-label>
            <ion-input v-model="newTrigger.target" placeholder="user@example.com"></ion-input>
          </ion-item>
          
          <ion-item v-if="newTrigger.type === 'telegram'">
            <ion-label position="stacked">Telegram (Token:ChatID)</ion-label>
            <ion-input v-model="newTrigger.target" placeholder="1234567890:ABCDEF:123456789"></ion-input>
          </ion-item>
          
          <ion-item v-if="newTrigger.type === 'discord'">
            <ion-label position="stacked">Discord Webhook URL</ion-label>
            <ion-input v-model="newTrigger.target" placeholder="https://discord.com/api/webhooks/..."></ion-input>
          </ion-item>
          
          <ion-item v-if="newTrigger.type === 'sms'">
            <ion-label position="stacked">Phone Number</ion-label>
            <ion-input v-model="newTrigger.target" placeholder="+49123456789"></ion-input>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Message Template</ion-label>
            <ion-textarea 
              v-model="newTrigger.message" 
              placeholder="Use {field_name} for dynamic values. Example: New entry with ID {id} was created."
              rows="4"
            ></ion-textarea>
          </ion-item>
          
          <ion-button @click="createTrigger" expand="block" color="primary">
            Create Trigger
          </ion-button>
        </ion-card-content>
      </ion-card>
      
      <!-- Existing Triggers List -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Existing Triggers</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div v-if="triggers.length === 0" class="no-triggers">
            <p>No triggers configured yet.</p>
          </div>
          
          <ion-item v-for="trigger in triggers" :key="trigger.id">
            <ion-label>
              <h3>{{ trigger.trigger_event.toUpperCase() }} → {{ trigger.notification_type.toUpperCase() }}</h3>
              <p>Target: {{ trigger.notification_target }}</p>
              <p>Message: {{ trigger.message_template.substring(0, 50) }}...</p>
            </ion-label>
            <ion-toggle 
              :checked="trigger.is_active" 
              @ionChange="toggleTrigger(trigger.id, $event.detail.checked)"
            ></ion-toggle>
            <ion-button 
              fill="clear" 
              color="danger" 
              @click="deleteTrigger(trigger.id)"
            >
              <ion-icon name="trash-outline"></ion-icon>
            </ion-button>
          </ion-item>
        </ion-card-content>
      </ion-card>
      
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'TriggerManager',
  props: {
    project: String,
    form: String
  },
  data() {
    return {
      triggers: [],
      newTrigger: {
        event: '',
        type: '',
        target: '',
        message: ''
      }
    };
  },
  mounted() {
    this.loadTriggers();
  },
  methods: {
    closeModal() {
      this.$emit('close');
    },
    
    async loadTriggers() {
      try {
        const response = await this.$axios.post(
          'triggers.php',
          this.$qs.stringify({
            get_triggers: true,
            project: this.project,
            form_name: this.form
          })
        );
        this.triggers = response.data;
      } catch (error) {
        console.error('Error loading triggers:', error);
      }
    },
    
    async createTrigger() {
      if (!this.newTrigger.event || !this.newTrigger.type || !this.newTrigger.target || !this.newTrigger.message) {
        alert('Please fill all fields');
        return;
      }
      
      try {
        const response = await this.$axios.post(
          'triggers.php',
          this.$qs.stringify({
            create_trigger: true,
            project: this.project,
            form_name: this.form,
            trigger_event: this.newTrigger.event,
            notification_type: this.newTrigger.type,
            notification_target: this.newTrigger.target,
            message_template: this.newTrigger.message
          })
        );
        
        if (response.data.success) {
          this.newTrigger = { event: '', type: '', target: '', message: '' };
          this.loadTriggers();
          alert('Trigger created successfully!');
        } else {
          alert('Error creating trigger: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error creating trigger:', error);
        alert('Error creating trigger');
      }
    },
    
    async toggleTrigger(triggerId, isActive) {
      try {
        await this.$axios.post(
          'triggers.php',
          this.$qs.stringify({
            toggle_trigger: true,
            trigger_id: triggerId,
            is_active: isActive ? 1 : 0
          })
        );
        this.loadTriggers();
      } catch (error) {
        console.error('Error toggling trigger:', error);
      }
    },
    
    async deleteTrigger(triggerId) {
      if (!confirm('Are you sure you want to delete this trigger?')) {
        return;
      }
      
      try {
        const response = await this.$axios.post(
          'triggers.php',
          this.$qs.stringify({
            delete_trigger: true,
            trigger_id: triggerId
          })
        );
        
        if (response.data.success) {
          this.loadTriggers();
          alert('Trigger deleted successfully!');
        } else {
          alert('Error deleting trigger');
        }
      } catch (error) {
        console.error('Error deleting trigger:', error);
        alert('Error deleting trigger');
      }
    }
  }
});
</script>

<style scoped>
.no-triggers {
  text-align: center;
  color: var(--ion-color-medium);
  padding: 20px;
}

ion-item {
  margin-bottom: 10px;
}

ion-card {
  margin-bottom: 20px;
}
</style>
