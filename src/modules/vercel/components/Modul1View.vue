<template>
    <ion-page class="ion-padding">
        <ion-content>
            <div class="env-variables-manager">
                <ion-card>
                    <ion-card-header>
                        <div class="header-with-action">
                            <div>
                                <ion-card-title>Environment Variables</ion-card-title>
                                <ion-card-subtitle v-if="selectedProject">
                                    Project: {{ selectedProject.name }}
                                </ion-card-subtitle>
                            </div>
                            <ion-button @click="openAddModal" color="primary">
                                <ion-icon name="add-outline" slot="start"></ion-icon>
                                Add Variable
                            </ion-button>
                        </div>
                    </ion-card-header>

                    <ion-card-content>
                        <!-- Environment Variables Table -->
                        <div v-if="selectedProject && envVariables.length > 0" class="table-container">
                            <table class="env-table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Value</th>
                                        <th>Environments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="envVar in envVariables" :key="envVar.id">
                                        <td class="key-column">
                                            <span class="env-key">{{ envVar.key }}</span>
                                        </td>
                                        <td class="value-column">
                                            <code class="env-value">{{ envVar.decryptedValue || '••••••••' }}</code>
                                        </td>
                                        <td class="targets-column">
                                            <div class="env-targets">
                                                <ion-chip v-for="target in envVar.target" :key="target"
                                                    :color="getTargetColor(target)" size="small">
                                                    {{ target }}
                                                </ion-chip>
                                            </div>
                                        </td>
                                        <td class="actions-column">
                                            <div class="action-buttons">
                                                <ion-button fill="clear" size="small"
                                                    @click="editEnvironmentVariable(envVar)">
                                                    <ion-icon name="create-outline"></ion-icon>
                                                </ion-button>
                                                <ion-button fill="clear" size="small" color="danger"
                                                    @click="deleteEnvironmentVariable(envVar)">
                                                    <ion-icon name="trash-outline"></ion-icon>
                                                </ion-button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="selectedProject && !loading" class="empty-state">
                            <ion-icon name="server-outline" size="large"></ion-icon>
                            <h3>No Environment Variables</h3>
                            <p>This project doesn't have any environment variables yet.</p>
                            <ion-button @click="openAddModal" color="primary">
                                <ion-icon name="add-outline" slot="start"></ion-icon>
                                Add Your First Variable
                            </ion-button>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="loading-container">
                            <ion-spinner name="crescent"></ion-spinner>
                            <p>Loading environment variables...</p>
                        </div>
                    </ion-card-content>
                </ion-card>
            </div>

            <!-- Add Variable Modal -->
            <ion-modal :is-open="addModal.isOpen" @did-dismiss="closeAddModal">
                <ion-header>
                    <ion-toolbar>
                        <ion-title>Add Environment Variable</ion-title>
                        <ion-buttons slot="end">
                            <ion-button @click="closeAddModal">
                                <ion-icon name="close-outline"></ion-icon>
                            </ion-button>
                        </ion-buttons>
                    </ion-toolbar>
                </ion-header>
                <ion-content class="ion-padding">
                    <ion-item>
                        <ion-label position="stacked">Key *</ion-label>
                        <ion-input v-model="newEnvVar.key" placeholder="VARIABLE_NAME" clear-input></ion-input>
                    </ion-item>
                    <ion-item>
                        <ion-label position="stacked">Value *</ion-label>
                        <ion-textarea v-model="newEnvVar.value" placeholder="variable_value" auto-grow></ion-textarea>
                    </ion-item>
                    <ion-item>
                        <ion-label>Target Environments</ion-label>
                        <ion-checkbox-group v-model="newEnvVar.target">
                            <ion-item>
                                <ion-checkbox value="production"></ion-checkbox>
                                <ion-label class="ion-margin-start">Production</ion-label>
                            </ion-item>
                            <ion-item>
                                <ion-checkbox value="preview"></ion-checkbox>
                                <ion-label class="ion-margin-start">Preview</ion-label>
                            </ion-item>
                            <ion-item>
                                <ion-checkbox value="development"></ion-checkbox>
                                <ion-label class="ion-margin-start">Development</ion-label>
                            </ion-item>
                        </ion-checkbox-group>
                    </ion-item>
                    
                    <div class="modal-buttons">
                        <ion-button expand="block" @click="addEnvironmentVariable"
                            :disabled="!newEnvVar.key || !newEnvVar.value || loading" color="primary">
                            <ion-spinner v-if="loading" name="crescent" slot="start"></ion-spinner>
                            {{ loading ? 'Adding...' : 'Add Variable' }}
                        </ion-button>
                    </div>
                </ion-content>
            </ion-modal>

            <!-- Edit Modal -->
            <ion-modal :is-open="editModal.isOpen" @did-dismiss="closeEditModal">
                <ion-header>
                    <ion-toolbar>
                        <ion-title>Edit Environment Variable</ion-title>
                        <ion-buttons slot="end">
                            <ion-button @click="closeEditModal">
                                <ion-icon name="close-outline"></ion-icon>
                            </ion-button>
                        </ion-buttons>
                    </ion-toolbar>
                </ion-header>
                <ion-content class="ion-padding">
                    <ion-item>
                        <ion-label position="stacked">Key *</ion-label>
                        <ion-input v-model="editModal.key" placeholder="VARIABLE_NAME" clear-input></ion-input>
                    </ion-item>
                    <ion-item>
                        <ion-label position="stacked">Value *</ion-label>
                        <ion-textarea v-model="editModal.value" placeholder="variable_value" auto-grow></ion-textarea>
                    </ion-item>
                    <ion-item>
                        <ion-label>Target Environments</ion-label>
                        <ion-checkbox-group v-model="editModal.target">
                            <ion-item>
                                <ion-checkbox value="production"></ion-checkbox>
                                <ion-label class="ion-margin-start">Production</ion-label>
                            </ion-item>
                            <ion-item>
                                <ion-checkbox value="preview"></ion-checkbox>
                                <ion-label class="ion-margin-start">Preview</ion-label>
                            </ion-item>
                            <ion-item>
                                <ion-checkbox value="development"></ion-checkbox>
                                <ion-label class="ion-margin-start">Development</ion-label>
                            </ion-item>
                        </ion-checkbox-group>
                    </ion-item>
                    
                    <div class="modal-buttons">
                        <ion-button expand="block" @click="updateEnvironmentVariable"
                            :disabled="!editModal.key || !editModal.value || loading" color="primary">
                            <ion-spinner v-if="loading" name="crescent" slot="start"></ion-spinner>
                            {{ loading ? 'Updating...' : 'Update Variable' }}
                        </ion-button>
                    </div>
                </ion-content>
            </ion-modal>
        </ion-content>
    </ion-page>
</template>
<script>
import {
    IonPage,
    IonContent,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonItem,
    IonLabel,
    IonInput,
    IonTextarea,
    IonButton,
    IonCheckbox,
    IonChip,
    IonIcon,
    IonSpinner,
    IonModal,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonButtons,
    alertController,
    toastController
} from '@ionic/vue';
import { createOutline, trashOutline, addOutline, closeOutline, serverOutline } from 'ionicons/icons';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import qs from 'qs';
import { useRoute } from 'vue-router'

export default {
    name: 'VercelEnvironmentVariables',
    components: {
        IonPage,
        IonContent,
        IonCard,
        IonCardHeader,
        IonCardTitle,
        IonCardSubtitle,
        IonCardContent,
        IonItem,
        IonLabel,
        IonInput,
        IonTextarea,
        IonButton,
        IonCheckbox,
        IonChip,
        IonIcon,
        IonSpinner,
        IonModal,
        IonHeader,
        IonToolbar,
        IonTitle,
        IonButtons
    },
    setup() {
        const selectedProjectId = ref('');
        const selectedProject = ref(null);
        const envVariables = ref([]);
        const loading = ref(false);
        const route = useRoute();

        const newEnvVar = ref({
            key: '',
            value: '',
            target: ['production', 'preview', 'development']
        });

        const addModal = ref({
            isOpen: false
        });

        const editModal = ref({
            isOpen: false,
            id: '',
            key: '',
            value: '',
            target: ['production', 'preview', 'development']
        });

        const loadProjects = async () => {
            loading.value = true;
            try {
                // Get current project from URL or context - this should be the connected project
                const currentProject = route.params.project || 'default-project'; // This should come from your project context

                // Get the connected Vercel project for this Control Center project
                const response = await axios.post('project_vercel.php', qs.stringify({
                    action: 'get',
                    project: currentProject
                }));

                if (response.data.vercel_project_id) {
                    // We have a connected project, set it directly
                    selectedProjectId.value = response.data.vercel_project_id;
                    selectedProject.value = {
                        id: response.data.vercel_project_id,
                        name: response.data.vercel_project_name
                    };
                    await loadEnvironmentVariables();
                } else {
                    showToast('No Vercel project connected. Please connect a project first.', 'warning');
                }
            } catch (error) {
                console.error('Error loading connected project:', error);
                showToast('Error loading connected project', 'danger');
            } finally {
                loading.value = false;
            }
        };

        const loadEnvironmentVariables = async () => {
            if (!selectedProjectId.value) return;

            loading.value = true;
            try {
                const response = await axios.get(`vercel_api.php?project=${selectedProjectId.value}&action=env`);

                if (response.data.success) {
                    envVariables.value = response.data.envVars.envs || [];
                }
            } catch (error) {
                console.error('Error loading environment variables:', error);
                showToast('Error loading environment variables', 'danger');
            } finally {
                loading.value = false;
            }
        };

        const openAddModal = () => {
            addModal.value.isOpen = true;
        };

        const closeAddModal = () => {
            addModal.value.isOpen = false;
            // Reset form
            newEnvVar.value = {
                key: '',
                value: '',
                target: ['production', 'preview', 'development']
            };
        };

        const addEnvironmentVariable = async () => {
            if (!newEnvVar.value.key || !newEnvVar.value.value) return;

            loading.value = true;
            try {
                const response = await axios.post(`vercel_api.php?project=${selectedProjectId.value}`, {
                    action: 'create_env',
                    key: newEnvVar.value.key,
                    value: newEnvVar.value.value,
                    target: newEnvVar.value.target
                });

                if (response.data.success) {
                    showToast('Environment variable added successfully', 'success');
                    closeAddModal();
                    await loadEnvironmentVariables();
                }
            } catch (error) {
                console.error('Error adding environment variable:', error);
                showToast('Error adding environment variable', 'danger');
            } finally {
                loading.value = false;
            }
        };

        const editEnvironmentVariable = (envVar) => {
            editModal.value = {
                isOpen: true,
                id: envVar.id,
                key: envVar.key,
                value: envVar.value,
                target: envVar.target
            };
        };

        const updateEnvironmentVariable = async () => {
            loading.value = true;
            try {
                const response = await axios.post(`vercel_api.php?project=${selectedProjectId.value}`, {
                    action: 'update_env',
                    envId: editModal.value.id,
                    key: editModal.value.key,
                    value: editModal.value.value,
                    target: editModal.value.target
                });

                if (response.data.success) {
                    showToast('Environment variable updated successfully', 'success');
                    closeEditModal();
                    await loadEnvironmentVariables();
                }
            } catch (error) {
                console.error('Error updating environment variable:', error);
                showToast('Error updating environment variable', 'danger');
            } finally {
                loading.value = false;
            }
        };

        const deleteEnvironmentVariable = async (envVar) => {
            const alert = await alertController.create({
                header: 'Confirm Deletion',
                message: `Are you sure you want to delete the environment variable "${envVar.key}"?`,
                buttons: [
                    {
                        text: 'Cancel',
                        role: 'cancel'
                    },
                    {
                        text: 'Delete',
                        role: 'destructive',
                        handler: async () => {
                            loading.value = true;
                            try {
                                const response = await axios.post(`vercel_api.php?project=${selectedProjectId.value}`, {
                                    action: 'delete_env',
                                    envId: envVar.id
                                });

                                if (response.data.success) {
                                    showToast('Environment variable deleted successfully', 'success');
                                    await loadEnvironmentVariables();
                                }
                            } catch (error) {
                                console.error('Error deleting environment variable:', error);
                                showToast('Error deleting environment variable', 'danger');
                            } finally {
                                loading.value = false;
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        const closeEditModal = () => {
            editModal.value = {
                isOpen: false,
                id: '',
                key: '',
                value: '',
                target: ['production', 'preview', 'development']
            };
        };

        const getTargetColor = (target) => {
            switch (target) {
                case 'production': return 'danger';
                case 'preview': return 'warning';
                case 'development': return 'success';
                default: return 'medium';
            }
        };

        const showToast = async (message, color = 'primary') => {
            const toast = await toastController.create({
                message,
                duration: 3000,
                color,
                position: 'top'
            });
            await toast.present();
        };

        onMounted(() => {
            loadProjects(); // This now loads the connected project directly
        });

        return {
            selectedProjectId,
            selectedProject,
            envVariables,
            loading,
            newEnvVar,
            addModal,
            editModal,
            openAddModal,
            closeAddModal,
            addEnvironmentVariable,
            editEnvironmentVariable,
            updateEnvironmentVariable,
            deleteEnvironmentVariable,
            closeEditModal,
            getTargetColor,
            createOutline,
            trashOutline,
            addOutline,
            closeOutline,
            serverOutline
        };
    }
};
</script>
<style scoped>
.env-variables-manager {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header with action button */
.header-with-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

/* Table styles */
.table-container {
    overflow-x: auto;
    margin-top: 16px;
}

.env-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--ion-color-light);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.env-table th {
    background: var(--ion-color-primary);
    color: white;
    padding: 16px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.env-table td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--ion-color-light-shade);
    vertical-align: top;
}

.env-table tr:last-child td {
    border-bottom: none;
}

.env-table tr:hover {
    background-color: var(--ion-color-light-tint);
}

/* Column specific styles */
.key-column {
    min-width: 200px;
}

.env-key {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--ion-color-primary);
    font-size: 14px;
}

.value-column {
    min-width: 250px;
    max-width: 300px;
}

.env-value {
    font-family: 'Courier New', monospace;
    background-color: var(--ion-color-light-shade);
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    word-break: break-all;
    display: block;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.targets-column {
    min-width: 180px;
}

.env-targets {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.actions-column {
    width: 120px;
    text-align: center;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--ion-color-medium);
}

.empty-state ion-icon {
    margin-bottom: 16px;
    color: var(--ion-color-medium-shade);
}

.empty-state h3 {
    margin: 16px 0 8px 0;
    color: var(--ion-color-dark);
    font-size: 18px;
}

.empty-state p {
    margin-bottom: 24px;
    font-size: 14px;
}

/* Loading state */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
}

.loading-container p {
    margin-top: 16px;
    color: var(--ion-color-medium);
    font-size: 14px;
}

/* Modal styles */
.modal-buttons {
    margin-top: 24px;
    padding-top: 16px;
}

ion-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
  .env-table {
    background: var(--ion-color-dark-tint);
  }
  .env-table th {
    background: var(--ion-color-dark);
    color: var(--ion-color-light);
  }
  .env-table td {
    border-bottom-color: var(--ion-color-dark);
    color: var(--ion-color-light);
  }
  .env-value {
    background-color: var(--ion-color-dark-shade);
    color: var(--ion-color-light);
  }
  .env-targets ion-chip {
    --background: var(--ion-color-dark-shade);
    color: var(--ion-color-light);
  }
  .empty-state,
  .loading-container {
    color: var(--ion-color-light);
  }
  .empty-state ion-icon {
    color: var(--ion-color-light-tint);
  }
}

/* Responsive design */
@media (max-width: 768px) {
    .header-with-action {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .env-table {
        font-size: 12px;
    }
    
    .env-table th,
    .env-table td {
        padding: 12px 8px;
    }
    
    .value-column {
        min-width: 150px;
        max-width: 200px;
    }
    
    .env-value {
        font-size: 11px;
        max-height: 40px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}

@media (max-width: 480px) {
    /* Stack table for very small screens */
    .table-container {
        overflow-x: scroll;
    }
    
    .env-table {
        min-width: 600px;
    }
}
</style>