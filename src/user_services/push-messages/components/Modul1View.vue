<template>
    <IonPage>
      <IonHeader>
        <IonToolbar color="light">
          <IonTitle>Push Messages</IonTitle>
        </IonToolbar>
        <IonSegment v-model="selectedTab" scrollable>
          <IonSegmentButton value="config">Configuration</IonSegmentButton>
          <IonSegmentButton value="logs">Logs</IonSegmentButton>
          <IonSegmentButton value="triggers">Triggers</IonSegmentButton>
        </IonSegment>
      </IonHeader>
  
      <IonContent :fullscreen="true" class="ion-padding">
        <div v-if="selectedTab === 'config'">
          <h2>Service Configuration</h2>
          <IonTextarea
            v-model="config"
            autoGrow
            :rows="10"
            placeholder="{ '\apiKey\': \'...\' }"
          />
          <IonButton expand="block" @click="saveConfig">Save Configuration</IonButton>
          <IonButton expand="block" fill="outline" color="medium" @click="testService">
            Test Service
          </IonButton>
        </div>
  
        <div v-else-if="selectedTab === 'logs'">
          <h2>Logs</h2>
          <IonList>
            <IonItem v-for="(log, index) in logs" :key="index">
              <IonLabel>
                <p>{{ log.timestamp }}</p>
                <h3>{{ log.message }}</h3>
              </IonLabel>
            </IonItem>
          </IonList>
        </div>
  
        <div v-else-if="selectedTab === 'triggers'">
          <h2>Triggers</h2>
          <IonList>
            <IonItem v-for="(trigger, index) in triggers" :key="index">
              <IonLabel>
                <p>Event: {{ trigger.event }}</p>
                <p>Condition: {{ trigger.condition }}</p>
                <p>Message: {{ trigger.message }}</p>
              </IonLabel>
            </IonItem>
          </IonList>
          <IonButton expand="block" @click="addTrigger">Add Trigger</IonButton>
        </div>
      </IonContent>
    </IonPage>
  </template>
  
  <script lang="ts" setup>
  import {
    IonPage,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonSegment,
    IonSegmentButton,
    IonContent,
    IonTextarea,
    IonButton,
    IonItem,
    IonList,
    IonLabel,
  } from '@ionic/vue'
  import { ref } from 'vue'
  
  const selectedTab = ref('config')
  const config = ref(`{
    "apiKey": "YOUR_PUSH_API_KEY",
    "senderId": "YOUR_SENDER_ID"
  }`)
  
  const logs = ref([
    { timestamp: '2025-04-07 14:01', message: 'Sent push to 123 users' },
    { timestamp: '2025-04-07 13:54', message: 'Error: Invalid token' },
  ])
  
  const triggers = ref([
    {
      event: 'order.created',
      condition: 'order.amount > 100',
      message: 'High value order received!',
    },
  ])
  
  const saveConfig = () => {
    console.log('Saving config:', config.value)
    // Save to backend logic
  }
  
  const testService = () => {
    console.log('Testing service with config:', config.value)
    // Simulate push
  }
  
  const addTrigger = () => {
    // Push a new empty trigger (optional modal later)
    triggers.value.push({
      event: 'user.login',
      condition: 'true',
      message: 'User just logged in',
    })
  }
  </script>
  
  <style scoped>
  h2 {
    margin-top: 1rem;
    font-weight: 600;
  }
  </style>
  