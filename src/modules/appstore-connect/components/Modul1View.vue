<template>
    <ion-page class="ion-padding">
        <ion-content>
            <h2>App Store Downloads Dashboard</h2>
            <ion-card>
                <ion-card-header>
                    <ion-card-title>Download-Zahlen</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                    <div v-if="loading">Lade Daten...</div>
                    <div v-else-if="error">Fehler: {{ error }}</div>
                    <div v-else>
                        <ul>
                            <li v-for="(item, idx) in downloads" :key="idx">
                                {{ item.date }}: {{ item.count }} Downloads
                            </li>
                        </ul>
                    </div>
                </ion-card-content>
            </ion-card>
        </ion-content>
    </ion-page>
</template>
<script>
export default {
    name: 'ModulView',
    data() {
        return {
            downloads: [],
            loading: true,
            error: null
        };
    },
    created() {
        this.loadDownloads();
    },
    methods: {
        async loadDownloads() {
            this.loading = true;
            this.error = null;
            try {
                const res = await this.$axios.get('appstore_downloads.php');
                this.downloads = res.data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
<style scoped>
ion-card { margin-top: 20px; }
</style>