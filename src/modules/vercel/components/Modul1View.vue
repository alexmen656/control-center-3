<template>
    <ion-page class="ion-padding">
        <ion-content>
            <div class="env-variables-manager">
                <ion-card>
                    <ion-card-header>
                        <ion-card-title>Environment Variables</ion-card-title>
                        <ion-card-subtitle v-if="selectedProject">
                            Project: {{ selectedProject.name }}
                        </ion-card-subtitle>
                    </ion-card-header>

                    <ion-card-content>
                        <div v-if="selectedProject">
                            <!-- Add New Environment Variable -->
                            <ion-card>
                                <ion-card-header>
                                    <ion-card-title>Add New Variable</ion-card-title>
                                </ion-card-header>
                                <ion-card-content>
                                    <ion-item>
                                        <ion-label position="stacked">Key</ion-label>
                                        <ion-input v-model="newEnvVar.key" placeholder="VARIABLE_NAME"></ion-input>
                                    </ion-item>
                                    <ion-item>
                                        <ion-label position="stacked">Value</ion-label>
                                        <ion-textarea v-model="newEnvVar.value"
                                            placeholder="variable_value"></ion-textarea>
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
                                    <ion-button expand="block" @click="addEnvironmentVariable"
                                        :disabled="!newEnvVar.key || !newEnvVar.value || loading">
                                        <ion-spinner v-if="loading" name="crescent"></ion-spinner>
                                        Add Variable
                                    </ion-button>
                                </ion-card-content>
                            </ion-card>

                            <!-- Environment Variables List -->
                            <ion-card v-if="envVariables.length > 0">
                                <ion-card-header>
                                    <ion-card-title>Current Variables</ion-card-title>
                                </ion-card-header>
                                <ion-card-content>
                                    <ion-list>
                                        <ion-item v-for="envVar in envVariables" :key="envVar.id">
                                            <div class="env-var-item">
                                                <div class="env-var-header">
                                                    <h3>{{ envVar.key }}</h3>
                                                    <div class="env-var-actions">
                                                        <ion-button fill="clear" size="small"
                                                            @click="editEnvironmentVariable(envVar)">
                                                            <ion-icon name="create-outline"></ion-icon>
                                                        </ion-button>
                                                        <ion-button fill="clear" size="small" color="danger"
                                                            @click="deleteEnvironmentVariable(envVar)">
                                                            <ion-icon name="trash-outline"></ion-icon>
                                                        </ion-button>
                                                    </div>
                                                </div>
                                                <div class="env-var-details">
                                                    <p class="env-var-value">{{ envVar.decryptedValue }}</p>
                                                    <div class="env-var-targets">
                                                        <ion-chip v-for="target in envVar.target" :key="target"
                                                            :color="getTargetColor(target)">
                                                            {{ target }}
                                                        </ion-chip>
                                                    </div>
                                                </div>
                                            </div>
                                        </ion-item>
                                    </ion-list>
                                </ion-card-content>
                            </ion-card>

                            <ion-card v-else-if="!loading">
                                <ion-card-content>
                                    <p>No environment variables found for this project.</p>
                                </ion-card-content>
                            </ion-card>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="loading-container">
                            <ion-spinner name="crescent"></ion-spinner>
                            <p>Loading...</p>
                        </div>
                    </ion-card-content>
                </ion-card>
            </div>

            <!-- Edit Modal -->
            <ion-modal :is-open="editModal.isOpen" @did-dismiss="closeEditModal">
                <ion-header>
                    <ion-toolbar>
                        <ion-title>Edit Environment Variable</ion-title>
                        <ion-buttons slot="end">
                            <ion-button @click="closeEditModal">Close</ion-button>
                        </ion-buttons>
                    </ion-toolbar>
                </ion-header>
                <ion-content class="ion-padding">
                    <ion-item>
                        <ion-label position="stacked">Key</ion-label>
                        <ion-input v-model="editModal.key" placeholder="VARIABLE_NAME"></ion-input>
                    </ion-item>
                    <ion-item>
                        <ion-label position="stacked">Value</ion-label>
                        <ion-textarea v-model="editModal.value" placeholder="variable_value"></ion-textarea>
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
                    <ion-button expand="block" @click="updateEnvironmentVariable"
                        :disabled="!editModal.key || !editModal.value || loading">
                        Update Variable
                    </ion-button>
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
    //IonSelect,
    //IonSelectOption,
    //IonCheckboxGroup,
    IonCheckbox,
    IonList,
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
import { createOutline, trashOutline } from 'ionicons/icons';
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
        //IonSelect,
        //IonSelectOption,
        //IonCheckboxGroup,
        IonCheckbox,
        IonList,
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
                    newEnvVar.value = {
                        key: '',
                        value: '',
                        target: ['production', 'preview', 'development']
                    };
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
            editModal,
            addEnvironmentVariable,
            editEnvironmentVariable,
            updateEnvironmentVariable,
            deleteEnvironmentVariable,
            closeEditModal,
            getTargetColor,
            createOutline,
            trashOutline
        };
    }
};
</script>
<style scoped>
.env-variables-manager {
    max-width: 800px;
    margin: 0 auto;
}

.env-var-item {
    width: 100%;
}

.env-var-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.env-var-header h3 {
    margin: 0;
    font-weight: 600;
    color: var(--ion-color-primary);
}

.env-var-actions {
    display: flex;
    gap: 4px;
}

.env-var-details {
    margin-left: 0;
}

.env-var-value {
    font-family: 'Courier New', monospace;
    background-color: var(--ion-color-light);
    padding: 8px;
    border-radius: 4px;
    margin: 8px 0;
    font-size: 14px;
    word-break: break-all;
}

.env-var-targets {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.loading-container p {
    margin-top: 16px;
    color: var(--ion-color-medium);
}

ion-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

ion-item.checkbox-item {
    --padding-start: 0;
    --inner-padding-end: 0;
}

@media (max-width: 768px) {
    .env-var-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .env-var-actions {
        align-self: flex-end;
    }
}
</style>