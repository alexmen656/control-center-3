<template>
  <div class="modern-modal">
    <div class="modal-overlay" @click="closeModal">
      <div class="modal-container" @click.stop>
        <!-- Modal Header -->
        <div class="modal-header">
          <div class="header-content">
            <h2>Trigger Management</h2>
            <p>Manage notifications for form events</p>
          </div>
          <button class="close-btn" @click="closeModal">
            <ion-icon name="close-outline"></ion-icon>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="modal-content">
          <!-- Create New Trigger Section -->
          <div class="section-card">
            <div class="section-header">
              <h3>Create New Trigger</h3>
              <p>Set up automatic notifications for form events</p>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Trigger Event</label>
                <div class="select-wrapper">
                  <select v-model="newTrigger.event" class="modern-select">
                    <option value="">Select Event</option>
                    <option value="insert">On Insert (New Entry)</option>
                    <option value="update">On Update (Edit Entry)</option>
                    <option value="delete">On Delete (Remove Entry)</option>
                  </select>
                  <ion-icon name="chevron-down-outline" class="select-icon"></ion-icon>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Notification Type</label>
                <div class="select-wrapper">
                  <select v-model="newTrigger.type" class="modern-select">
                    <option value="">Select Type</option>
                    <option value="email">📧 Email</option>
                    <option value="telegram">📱 Telegram</option>
                    <option value="discord">💬 Discord</option>
                    <option value="sms">📲 SMS</option>
                  </select>
                  <ion-icon name="chevron-down-outline" class="select-icon"></ion-icon>
                </div>
              </div>

              <div class="form-group full-width" v-if="newTrigger.type === 'email'">
                <label class="form-label">Email Address</label>
                <input 
                  v-model="newTrigger.target" 
                  type="email"
                  class="modern-input" 
                  placeholder="user@example.com"
                >
              </div>

              <div class="form-group full-width" v-if="newTrigger.type === 'telegram'">
                <label class="form-label">Telegram (Token:ChatID)</label>
                <input 
                  v-model="newTrigger.target" 
                  class="modern-input" 
                  placeholder="1234567890:ABCDEF:123456789"
                >
                <span class="input-hint">Format: bot_token:chat_id</span>
              </div>

              <div class="form-group full-width" v-if="newTrigger.type === 'discord'">
                <label class="form-label">Discord Webhook URL</label>
                <input 
                  v-model="newTrigger.target" 
                  class="modern-input" 
                  placeholder="https://discord.com/api/webhooks/..."
                >
              </div>

              <div class="form-group full-width" v-if="newTrigger.type === 'sms'">
                <label class="form-label">Phone Number</label>
                <input 
                  v-model="newTrigger.target" 
                  class="modern-input" 
                  placeholder="+49123456789"
                >
              </div>

              <div class="form-group full-width">
                <label class="form-label">Message Template</label>
                <textarea 
                  v-model="newTrigger.message" 
                  class="modern-textarea"
                  rows="4"
                  placeholder="Use {field_name} for dynamic values. Example: New entry with ID {id} was created."
                ></textarea>
                <span class="input-hint">Use {field_name} to insert dynamic values from your form</span>
              </div>

              <div class="form-group full-width">
                <button 
                  @click="createTrigger" 
                  class="primary-btn"
                  :disabled="!canCreateTrigger"
                >
                  <ion-icon name="add-outline"></ion-icon>
                  Create Trigger
                </button>
              </div>
            </div>
          </div>

          <!-- Existing Triggers Section -->
          <div class="section-card">
            <div class="section-header">
              <h3>Active Triggers</h3>
              <p v-if="triggers.length === 0">No triggers configured yet</p>
              <p v-else>{{ triggers.length }} trigger{{ triggers.length !== 1 ? 's' : '' }} configured</p>
            </div>

            <div v-if="triggers.length === 0" class="empty-state">
              <ion-icon name="notifications-off-outline" class="empty-icon"></ion-icon>
              <h4>No triggers yet</h4>
              <p>Create your first trigger above to get automatic notifications when form events occur.</p>
            </div>

            <div v-else class="triggers-list">
              <div v-for="trigger in triggers" :key="trigger.id" class="trigger-card">
                <div class="trigger-info">
                  <div class="trigger-header">
                    <div class="trigger-type">
                      <span class="event-badge" :class="trigger.trigger_event">
                        {{ trigger.trigger_event.toUpperCase() }}
                      </span>
                      <ion-icon name="arrow-forward-outline" class="arrow-icon"></ion-icon>
                      <span class="notification-badge" :class="trigger.notification_type">
                        {{ getNotificationIcon(trigger.notification_type) }} {{ trigger.notification_type.toUpperCase() }}
                      </span>
                    </div>
                    <div class="trigger-actions">
                      <label class="toggle-switch">
                        <input 
                          type="checkbox" 
                          :checked="trigger.is_active" 
                          @change="toggleTrigger(trigger.id, $event.target.checked)"
                        >
                        <span class="toggle-slider"></span>
                      </label>
                      <button 
                        class="danger-btn-small" 
                        @click="deleteTrigger(trigger.id)"
                        title="Delete trigger"
                      >
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                  
                  <div class="trigger-details">
                    <div class="detail-item">
                      <strong>Target:</strong> 
                      <span class="target-text">{{ formatTarget(trigger.notification_target) }}</span>
                    </div>
                    <div class="detail-item">
                      <strong>Message:</strong>
                      <span class="message-preview">{{ formatMessage(trigger.message_template) }}</span>
                    </div>
                  </div>
                </div>
              </div>
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
  computed: {
    canCreateTrigger() {
      return this.newTrigger.event && 
             this.newTrigger.type && 
             this.newTrigger.target && 
             this.newTrigger.message;
    }
  },
  mounted() {
    this.loadTriggers();
  },
  methods: {
    closeModal() {
      this.$emit('close');
    },
    
    getNotificationIcon(type) {
      const icons = {
        email: '📧',
        telegram: '📱',
        discord: '💬',
        sms: '📲'
      };
      return icons[type] || '🔔';
    },
    
    formatTarget(target) {
      if (target.length > 40) {
        return target.substring(0, 40) + '...';
      }
      return target;
    },
    
    formatMessage(message) {
      if (message.length > 60) {
        return message.substring(0, 60) + '...';
      }
      return message;
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
  --warning-color: #d97706;
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
  max-width: 800px;
  max-height: 90vh;
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
  max-height: calc(90vh - 100px);
  overflow-y: auto;
  padding: 24px;
}

/* Section Cards */
.section-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 24px;
}

.section-card:last-child {
  margin-bottom: 0;
}

.section-header {
  margin-bottom: 24px;
}

.section-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.section-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
}

/* Modern Inputs */
.modern-input,
.modern-textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.modern-input:focus,
.modern-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-textarea {
  resize: vertical;
  min-height: 80px;
}

/* Select Wrapper */
.select-wrapper {
  position: relative;
}

.modern-select {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  appearance: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.select-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  pointer-events: none;
  font-size: 16px;
}

.input-hint {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
}

/* Buttons */
.primary-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  justify-self: start;
}

.primary-btn:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.primary-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.danger-btn-small {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #fef2f2;
  color: var(--danger-color);
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.danger-btn-small:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.empty-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.empty-state h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Triggers List */
.triggers-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.trigger-card {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  background: var(--background);
  transition: all 0.2s ease;
}

.trigger-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 4px 12px rgb(37 99 235 / 0.1);
}

.trigger-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.trigger-type {
  display: flex;
  align-items: center;
  gap: 12px;
}

.event-badge,
.notification-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.event-badge.insert {
  background: #dcfce7;
  color: #166534;
}

.event-badge.update {
  background: #fef3c7;
  color: #92400e;
}

.event-badge.delete {
  background: #fee2e2;
  color: #991b1b;
}

.notification-badge {
  background: #eff6ff;
  color: var(--primary-color);
}

.arrow-icon {
  color: var(--text-muted);
  font-size: 16px;
}

.trigger-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Toggle Switch */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.3s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: var(--success-color);
}

input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

/* Trigger Details */
.trigger-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item {
  font-size: 14px;
  color: var(--text-secondary);
}

.detail-item strong {
  color: var(--text-primary);
  margin-right: 8px;
}

.target-text,
.message-preview {
  font-family: 'SF Mono', Monaco, monospace;
  background: var(--surface);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 13px;
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

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    margin: 8px;
    max-height: calc(100vh - 16px);
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-group.full-width {
    grid-column: span 1;
  }
  
  .trigger-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .trigger-type {
    flex-wrap: wrap;
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
  
  .modern-input,
  .modern-textarea,
  .modern-select {
    background: var(--background);
    color: var(--text-primary);
  }
  
  .target-text,
  .message-preview {
    background: var(--background);
  }
}
</style>
