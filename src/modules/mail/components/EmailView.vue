<template>
  <ion-content>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="`/project/${projectId}/mail`"></ion-back-button>
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
          <div class="email-header">
            <div class="email-header-info">
              <ion-card-subtitle>
                Von: {{ email?.header?.from?.[0] ? removeQuotes(email.header.from[0]) : 'Unbekannt' }}
              </ion-card-subtitle>
              <ion-card-subtitle>
                An: {{ email?.header?.to?.[0] || 'Unbekannt' }}
              </ion-card-subtitle>
              <ion-card-subtitle>
                Datum: {{ formatDate(email?.header?.date?.[0]) }}
              </ion-card-subtitle>
            </div>
            <ion-card-title class="email-subject">{{ emailSubject }}</ion-card-title>
          </div>
        </ion-card-header>

        <ion-card-content>
          <div v-if="email?.body" class="email-body">
            <div v-if="email.body.html || email.body.textAsHtml">
              <iframe
                class="email-iframe"
                :srcdoc="email.body.html || email.body.textAsHtml"
                frameborder="0"
                @load="adjustIframeHeight"
              ></iframe>
            </div>
            <div v-else-if="email.body.text" class="plain-text-body">
              <pre>{{ email.body.text }}</pre>
            </div>
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
                <ion-button
                  slot="end"
                  size="small"
                  fill="clear"
                  @click="downloadAttachment(attachment)"
                >
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
import { ToolConfigService } from '@/services/ToolConfigService';
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

import '@ionic/core/css/ionic.bundle.css'; // Ionic-Styles
import 'ionicons/icons'; // Ionic-Icons

const route = useRoute();
const projectId = computed(() => route.params.project); // Dynamisch die Projekt-ID aus den Routen-Parametern holen
const email = ref(null);
const loading = ref(true);
const error = ref(null);
const showRawContent = ref(false);
const config = ref(null); // Store the configuration data

// Get ID from query params instead of route params
const emailId = computed(() => route.query.id);

onMounted(async () => {
  try {
    loading.value = true;
    config.value = await ToolConfigService.loadToolConfig(projectId.value, 'mail');
    fetchEmail(emailId.value);
  } catch (err) {
    console.error('Failed to load configuration:', err);
    error.value = 'Fehler beim Laden der Konfiguration. Bitte versuchen Sie es später erneut.';
    loading.value = false;
  }
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
  if (!email.value?.body?.html && !email.value?.body?.textAsHtml) return '';

  try {
    const content = email.value.body.html || email.value.body.textAsHtml;
    const sanitized = content;
    return sanitized;
  } catch (err) {
    console.error('Error sanitizing email body:', err);
    return '';
  }
});

function toggleRawContent() {
  showRawContent.value = !showRawContent.value;
}

async function fetchEmail(id) {
  if (!id || !config.value) {
    error.value = 'Keine Konfiguration verfügbar oder keine Email-ID angegeben.';
    loading.value = false;
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.post('http://192.168.178.149:3000/emails', config.value);
    const emails = response.data;

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

function adjustIframeHeight(event) {
  const iframe = event.target;
  if (iframe && iframe.contentDocument) {
    iframe.style.height = iframe.contentDocument.body.scrollHeight + 'px';
  }
}

function downloadAttachment(attachment) {
  if (!attachment.content) {
    console.error('Attachment content is missing');
    return;
  }

  const binaryString = atob(attachment.content);
  const binaryLength = binaryString.length;
  const bytes = new Uint8Array(binaryLength);

  for (let i = 0; i < binaryLength; i++) {
    bytes[i] = binaryString.charCodeAt(i);
  }

  const blob = new Blob([bytes], { type: attachment.contentType });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = attachment.filename || 'attachment';
  link.click();

  URL.revokeObjectURL(link.href);
}

function removeQuotes(text) {
  return text.replace(/["]+/g, ''); // Entfernt alle Anführungszeichen
}
</script>

<style scoped>
.email-container {
  padding: 16px;
  width: 100%;
  margin: 0;
}

@media (min-width: 768px) {
  .email-container {
    padding: 32px;
  }
}

.loading-container,
.error-container {
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

/* Verbessertes Styling für gerenderten HTML-Inhalt */
.email-body :deep(a) {
  color: var(--ion-color-primary);
  text-decoration: underline;
}

.email-body :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 10px 0;
}

.email-body :deep(table) {
  max-width: 100%;
  overflow-x: auto;
  display: block;
  border-collapse: collapse;
  width: 100%;
}

.email-body :deep(th),
.email-body :deep(td) {
  border: 1px solid #ddd;
  padding: 8px;
}

.email-body :deep(th) {
  background-color: #f4f4f4;
  font-weight: bold;
}

.email-body :deep(ul),
.email-body :deep(ol) {
  padding-left: 20px;
}

.email-body :deep(blockquote) {
  margin: 10px 0;
  padding: 10px;
  background-color: #f9f9f9;
  border-left: 4px solid #ccc;
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

.email-iframe {
  width: 100%;
  border: none;
  background-color: white; /* Optional: Hintergrundfarbe */
}

.email-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 0.5rem;
}

.email-header-info {
  font-size: 1.25rem;
  line-height: 1.5;
}

.email-subject {
  font-size: 1.5rem;
  font-weight: bold;
  margin-top: 10px;
}

ion-card, ion-card-content {
  padding: 0 !important;
}
</style>
