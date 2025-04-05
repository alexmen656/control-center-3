<template>
  <ion-content>
    <ion-header>
      <ion-toolbar>
        <ion-title>Email Liste</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-list>
      <ion-item v-for="email in emails" :key="email.seqno" button detail @click="openEmail(email)">
        <ion-label>
          <div class="email-header-row">
            <h2>{{ email.header['from'] ? removeQuotes(email.header['from'][0]) : 'Unbekannt' }}</h2>
            <ion-badge v-if="email.mailbox && email.mailbox !== 'INBOX'" color="warning">
              {{ email.mailbox }}
            </ion-badge>
          </div>
          <h3>{{ email.header['subject'] ? email.header['subject'][0] : 'Kein Betreff' }}</h3>
          <p v-if="email.body && (email.body.text || email.body.html)">
            {{ getTextPreview(email.body.text || email.body.html) }}
          </p>
          <p v-else>Keine Vorschau verfügbar</p>
        </ion-label>
        <ion-note slot="end">
          {{ formatDate(email.header['date'] ? email.header['date'][0] : null) }}
        </ion-note>
      </ion-item>
    </ion-list>
  </ion-content>
</template>

<script setup>
import { 
  IonContent, 
  IonHeader, 
  IonToolbar, 
  IonTitle, 
  IonList, 
  IonItem, 
  IonLabel, 
  IonNote, 
  IonBadge 
} from '@ionic/vue';
import { useRouter } from 'vue-router';

const router = useRouter();
defineProps({
  emails: {
    type: Array,
    required: true,
  },
});

function openEmail(email) {
  // Using query parameters instead of path parameters
  router.push({
    name: 'email-view',
    query: { id: email.seqno }
  });
}

function formatDate(dateString) {
  if (!dateString) return 'Kein Datum';
  
  try {
    const date = new Date(dateString);
    const today = new Date();
    
    // Today's date -> show only time
    if (date.toDateString() === today.toDateString()) {
      return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    }
    
    // This year's date -> show day and month
    if (date.getFullYear() === today.getFullYear()) {
      return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
    }
    
    // Older dates -> show full date
    return date.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' });
  } catch (e) {
    return dateString;
  }
}

function getTextPreview(bodyContent) {
  if (!bodyContent) return 'Keine Vorschau verfügbar';
  
  // Create a temporary div to strip HTML tags for preview
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = bodyContent;
  const textContent = tempDiv.textContent || tempDiv.innerText || '';
  
  // Return a truncated preview
  return textContent.slice(0, 50) + (textContent.length > 50 ? '...' : '');
}

function removeQuotes(text) {
  return text.replace(/["]+/g, ''); // Entfernt alle Anführungszeichen
}
</script>

<style scoped>
ion-item {
  --padding-start: 10px;
  --padding-end: 10px;
}

.email-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

ion-badge {
  font-size: 0.65rem;
  padding: 4px 8px;
  margin-left: 8px;
}
</style>
