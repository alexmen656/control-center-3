<template>
  <div>
    <h1>Email Liste</h1>
    <ul class="email-list">
      <li v-for="email in emails" :key="email.seqno" class="email-item">
        <div class="email-header">
          <span class="email-sender">{{ email.header['from'] ? email.header['from'][0] : 'Unbekannt' }}</span>
          <span class="email-timestamp">{{ email.header['date'] ? email.header['date'][0] : 'Kein Datum' }}</span>
        </div>
        <div class="email-subject">
          {{ email.header['subject'] ? email.header['subject'][0] : 'Kein Betreff' }}
        </div>
        <div class="email-preview">
          {{ email.body ? email.body.slice(0, 50) + '...' : 'Keine Vorschau verfügbar' }}
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  emails: {
    type: Array,
    required: true,
  },
})
</script>

<style scoped>
.email-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.email-item {
  border-bottom: 1px solid #ddd;
  padding: 10px;
}

.email-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.9em;
  color: #555;
}

.email-sender {
  font-weight: bold;
}

.email-timestamp {
  font-style: italic;
}

.email-subject {
  font-size: 1em;
  margin: 5px 0;
}

.email-preview {
  font-size: 0.9em;
  color: #777;
}
</style>
