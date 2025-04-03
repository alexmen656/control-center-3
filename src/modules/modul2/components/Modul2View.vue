<template>
    <ion-page class="ion-padding">
        <ion-content>
            <!--ChatGPT ist krasser Brudi 🔥-->
            <ion-card>
                <ion-card-header>
                    <ion-card-title>Benutzerliste:</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                    <ion-list v-if="users.length">
                        <ion-item v-for="user in users" :key="user.id">
                            {{ user.name }}
                        </ion-item>
                    </ion-list>
                    <ion-text v-else color="medium">
                        Keine Benutzer gefunden.
                    </ion-text>
                </ion-card-content>
            </ion-card>
            <ion-card>
                <ion-card-header>
                    <ion-card-title>Tool-Konfiguration:</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                    <ion-list v-if="toolConfig">
                        <ion-item v-for="(value, key) in toolConfig" :key="key">
                            {{ key }}: {{ value }}
                        </ion-item>
                    </ion-list>
                    <ion-text v-else color="medium">
                        Keine Tool-Konfiguration geladen.
                    </ion-text>
                </ion-card-content>
            </ion-card>
        </ion-content>
    </ion-page>
</template>
<script>
import { getProjectData } from '@/services/projectDataService';
import { ToolConfigService } from '@/services/ToolConfigService';
import { useRoute } from 'vue-router';

export default {
    name: 'ModulView',

    data() {
        return {
            users: [],
            toolConfig: null,
        };
    },

    methods: {
        async fetchUserData(projectId) {
            try {
                const projectData = await getProjectData(projectId);
                this.users = projectData.tables.users;
            } catch (error) {
                console.error('Fehler beim Abrufen der Projektdaten:', error);
            }
        },

        async fetchToolConfig(projectId, toolName) {
            try {
                const config = await ToolConfigService.loadToolConfig(projectId, toolName);
                this.toolConfig = config;
            } catch (error) {
                console.error('Fehler beim Laden der Tool-Konfiguration:', error);
            }
        },
    },

    mounted() {
        const route = useRoute();
        const projectId = route.params.project;
        const toolName = route.path.split('/')[3];
        this.fetchUserData(projectId);
        this.fetchToolConfig(projectId, toolName);
    },
};
</script>
<style scoped>
ion-item,
ion-list,
ion-card-content {
    --background: #000000;
    background: #000000;
}
</style>