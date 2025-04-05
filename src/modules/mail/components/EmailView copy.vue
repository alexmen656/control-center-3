<template>
  <ion-content>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/mail"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ emailSubject }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <div v-if="loading" class="loading-container">
      <ion-spinner></ion-spinner>
      <p>Lade Email...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <ion-button @click="fetchEmail(emailId)">Erneut versuchen</ion-button>
    </div>

    <div v-else class="email-container">
      <ion-card>
        <ion-card-header>
          <div class="email-source" v-if="email?.mailbox && email.mailbox !== 'INBOX'">
            <ion-badge color="warning">{{ email.mailbox }}</ion-badge>
          </div>
          <ion-card-subtitle>
            Von: {{ email?.header?.from?.[0] || 'Unbekannt' }}
          </ion-card-subtitle>
          <ion-card-subtitle>
            An: {{ email?.header?.to?.[0] || 'Unbekannt' }}
          </ion-card-subtitle>
          <ion-card-subtitle>
            Datum: {{ formatDate(email?.header?.date?.[0]) }}
          </ion-card-subtitle>
          <ion-card-title>{{ emailSubject }}</ion-card-title>
        </ion-card-header>

        <ion-card-content>
          <div v-if="email?.body" class="email-body">
            <div v-if="sanitizedEmailBody.length > 0" v-html="sanitizedEmailBody"></div>
            <div v-else class="no-content">
              <p>Der Inhalt konnte nicht angezeigt werden.</p>
              <ion-button size="small" @click="toggleRawContent">
                {{ showRawContent ? 'Raw Content ausblenden' : 'Raw Content anzeigen' }}
              </ion-button>
              <pre v-if="showRawContent" class="raw-content">{{ email.body }}</pre>
            </div>
          </div>
          <div v-else class="no-content">
            <p>Diese E-Mail enthält keinen Text.</p>
          </div>

          <div v-if="email?.attachments?.length" class="attachments">
            <h3>Anhänge</h3>
            <ion-list>
              <ion-item v-for="(attachment, index) in email.attachments" :key="index">
                <ion-icon name="document-outline" slot="start"></ion-icon>
                <ion-label>{{ attachment.filename || `Anhang ${index + 1}` }}</ion-label>
                <ion-button slot="end" size="small" fill="clear">
                  <ion-icon name="download-outline"></ion-icon>
                </ion-button>
              </ion-item>
            </ion-list>
          </div>
        </ion-card-content>
      </ion-card>
    </div>
  </ion-content>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import DOMPurify from 'dompurify'; // Import DOMPurify for sanitizing HTML
import {
  IonContent,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonBackButton,
  IonCard,
  IonCardHeader,
  IonCardSubtitle,
  IonCardTitle,
  IonCardContent,
  IonList,
  IonItem,
  IonIcon,
  IonLabel,
  IonButton,
  IonSpinner,
  IonBadge
} from '@ionic/vue';

const route = useRoute();
const email = ref(null);
const loading = ref(true);
const error = ref(null);
const showRawContent = ref(false);

// Get ID from query params instead of route params
const emailId = computed(() => route.query.id);

onMounted(() => {
  fetchEmail(emailId.value);
});

// Watch for query param changes to reload email when navigating between emails
watch(emailId, (newId) => {
  if (newId) {
    fetchEmail(newId);
  }
});

const emailSubject = computed(() => {
  return email.value?.header?.subject?.[0] || 'Kein Betreff';
});

// Clean and sanitize HTML content
const sanitizedEmailBody = computed(() => {
  if (!email.value?.body) return '';
  
  try {
    // First clean MIME boundaries and email headers
    const cleanedContent = cleanEmailContent(email.value.body);
    
    // Then sanitize HTML to prevent XSS attacks
    const sanitized = DOMPurify.sanitize(cleanedContent);
    console.log('Sanitized content length:', sanitized.length);
    return sanitized;
  } catch (err) {
    console.error('Error sanitizing email body:', err);
    return '';
  }
});

function toggleRawContent() {
  showRawContent.value = !showRawContent.value;
}

/**
 * Cleans email content by removing MIME boundaries, headers, and other technical content
 */
function cleanEmailContent(content) {
  if (!content) return '';
  
  console.log('Original content length:', content.length);
  
  try {
    // Remove MIME boundaries (lines starting with --)
    let cleaned = content.replace(/^--[a-zA-Z0-9]+.*$/gm, '');
    
    // Remove email headers like Content-Type, Content-Transfer-Encoding, etc.
    cleaned = cleaned.replace(/^Content-[^:]+:.*$/gm, '');
    
    // Remove other email-specific markers
    cleaned = cleaned.replace(/^boundary=.*$/gm, '');
    
    // Remove excessive blank lines (more than 2 consecutive)
    cleaned = cleaned.replace(/\n{3,}/g, '\n\n');
    
    // Trim whitespace
    cleaned = cleaned.trim();
    
    console.log('Cleaned content length:', cleaned.length);
    return cleaned;
  } catch (err) {
    console.error('Error cleaning email content:', err);
    return content; // Return original content if cleaning fails
  }
}

async function fetchEmail(id) {
  if (!id) {
    error.value = 'Keine Email-ID angegeben';
    loading.value = false;
    return;
  }

  loading.value = true;
  error.value = null;
  
  try {
    // Fetch all emails
    const response = await axios.get('http://192.168.178.149:3000/emails');
    const emails = response.data;
    
    // Find the specific email by its seqno (ID)
    const foundEmail = emails.find(email => email.seqno.toString() === id.toString());
    
    if (foundEmail) {
      email.value = foundEmail;
      console.log('Email data:', foundEmail); // Log to see what data is available
    } else {
      error.value = 'Die E-Mail wurde nicht gefunden.';
    }
  } catch (err) {
    console.error('Error fetching emails:', err);
    error.value = 'Es ist ein Fehler beim Laden der E-Mail aufgetreten. Bitte versuchen Sie es später erneut.';
  } finally {
    loading.value = false;
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Kein Datum';
  
  try {
    const date = new Date(dateString);
    return date.toLocaleString('de-DE', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return dateString;
  }
}
</script>

<style scoped>
.email-container {
  padding: 16px;
  width: 100%; /* Setze die Breite auf 100% */
  margin: 0; /* Entferne zentrierte Ausrichtung */
}

@media (min-width: 768px) {
  .email-container {
    padding: 32px; /* Optional: Mehr Padding für größere Bildschirme */
  }
}

.loading-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
  text-align: center;
  padding: 20px;
}

.error-container {
  color: #d33;
}

.email-body {
  white-space: pre-wrap;
  margin-bottom: 20px;
}

/* Style for rendered HTML email */
.email-body :deep(a) {
  color: var(--ion-color-primary);
  text-decoration: none;
}

.email-body :deep(img) {
  max-width: 100%;
  height: auto;
}

.email-body :deep(table) {
  max-width: 100%;
  overflow-x: auto;
  display: block;
}

.attachments {
  margin-top: 24px;
  border-top: 1px solid #eee;
  padding-top: 16px;
}

.no-content {
  color: #666;
  font-style: italic;
  text-align: center;
  padding: 20px;
}

.email-source {
  margin-bottom: 10px;
}

.email-source ion-badge {
  font-size: 0.75rem;
  padding: 5px 10px;
}

.raw-content {
  background-color: #f5f5f5;
  padding: 10px;
  overflow-x: auto;
  font-size: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  white-space: pre-wrap;
  max-height: 300px;
  overflow-y: auto;
}
</style>
