<template>
  <div class="modern-modal">
    <div class="modal-overlay" @click="$emit('close')">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <div class="header-content">
            <h2>Delete Table</h2>
            <p>Permanently delete "{{ table }}" and all of its data</p>
          </div>
          <button class="close-btn" @click="$emit('close')">
            <ion-icon name="close-outline"></ion-icon>
          </button>
        </div>

        <div class="modal-content">
          <div class="form-section">
            <div class="warning-box">
              <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
              <span>This cannot be undone. The table config and all of its rows will be permanently deleted.</span>
            </div>

            <div class="form-group">
              <label class="form-label">Type <strong>{{ table }}</strong> to confirm</label>
              <input
                v-model="confirmName"
                type="text"
                class="modern-input"
                placeholder="Enter table name"
                @keyup.enter="deleteTable"
                ref="nameInput"
              >
            </div>

            <div class="button-group">
              <button class="secondary-btn" @click="$emit('close')">
                <ion-icon name="close-outline"></ion-icon>
                Cancel
              </button>
              <button
                class="danger-btn"
                @click="deleteTable"
                :disabled="!canDelete"
                :class="{ 'loading': isLoading }"
              >
                <ion-icon v-if="!isLoading" name="trash-outline"></ion-icon>
                <div v-else class="spinner"></div>
                {{ isLoading ? 'Deleting...' : 'Delete Table' }}
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
  name: 'DeleteTable',
  props: {
    project: {
      type: String,
      required: true
    },
    table: {
      type: String,
      required: true
    }
  },
  emits: ['close', 'success'],
  data() {
    return {
      confirmName: '',
      isLoading: false
    };
  },
  computed: {
    canDelete() {
      return this.confirmName === this.table && !this.isLoading;
    }
  },
  mounted() {
    this.$nextTick(() => {
      if (this.$refs.nameInput) {
        this.$refs.nameInput.focus();
      }
    });
  },
  methods: {
    async deleteTable() {
      if (!this.canDelete) return;

      this.isLoading = true;

      try {
        const response = await this.$axios.delete(
          'v2/tables/table',
          {
            params: {
              table_name: this.table,
              project: this.project
            }
          }
        );

        if (response.data.success) {
          this.$emit('success');
        } else {
          alert('Error deleting table: ' + (response.data.error || response.data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('Error deleting table:', error);
        alert('Error deleting table. Please try again.');
      } finally {
        this.isLoading = false;
      }
    }
  }
});
</script>

<style scoped>
.modern-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10000;
  --primary-color: #f97316;
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
  max-width: 480px;
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
  gap: 20px;
}

.warning-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(220, 38, 38, 0.08);
  border: 1px solid rgba(220, 38, 38, 0.3);
  border-radius: var(--radius);
  color: var(--danger-color);
  font-size: 13px;
  line-height: 1.5;
}

.warning-icon {
  font-size: 18px;
  flex-shrink: 0;
  margin-top: 1px;
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

.modern-input {
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  transition: all 0.2s ease;
}

.modern-input:focus {
  outline: none;
  border-color: var(--danger-color);
  box-shadow: 0 0 0 3px rgb(220 38 38 / 0.1);
}

.button-group {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 8px;
  border-top: 1px solid var(--border);
  padding-top: 16px;
}

.primary-btn,
.secondary-btn,
.danger-btn {
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

.danger-btn {
  background: var(--danger-color);
  color: white;
}

.danger-btn:hover:not(:disabled) {
  background: #b91c1c;
  transform: translateY(-1px);
}

.danger-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.danger-btn.loading {
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s ease-in-out infinite;
}

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

@media (max-width: 768px) {
  .modal-container {
    margin: 8px;
  }

  .button-group {
    flex-direction: column;
  }

  .primary-btn,
  .secondary-btn,
  .danger-btn {
    min-width: auto;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-modal {
    --surface: #1e1e1e;
    --background: #121212;
    --border: #2a2a2a;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --text-muted: #777777;
  }

  .modern-input {
    background: var(--background);
    color: var(--text-primary);
  }
}
</style>
