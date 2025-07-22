<template>
    <ion-page>
        <ion-content>
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center;">
                <div>
                    <ion-spinner></ion-spinner>
                    <p style="margin-top: 16px;">Redirecting to App User Management...</p>
                </div>
            </div>
        </ion-content>
    </ion-page>
</template>

<script>
import { IonPage, IonContent, IonSpinner } from '@ionic/vue';

export default {
    name: 'ModulView',
    components: {
        IonPage,
        IonContent,
        IonSpinner
    },
    mounted() {
        // Redirect to the dashboard after a short delay
        setTimeout(() => {
            this.$router.replace('/app-users/dashboard');
        }, 1000);
    }
};
</script>

<style scoped></style>