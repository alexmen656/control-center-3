<template>
    <ion-page>
      <!--<ion-header>
        <ion-toolbar>
          <ion-title>Ionicons</ion-title>
        </ion-toolbar>
      </ion-header>-->
      <ion-content>
        <ion-grid>
          <ion-row>
            <ion-col v-for="icon in icons" :key="icon" size="3" class="ion-text-center">
              <ion-icon :icon="icon" :style="{ fontSize: '24px' }"></ion-icon>
              <p>{{ iconName(icon) }}</p>
            </ion-col>
          </ion-row>
        </ion-grid>
      </ion-content>
    </ion-page>
  </template>
  
  <script>
  import * as icons from 'ionicons/icons';
  
  export default {
    name: 'AllIcons',
    data() {
      return {
        icons: Object.values(icons),
      };
    },
    methods: {
      iconName(icon) {
        const iconKey = Object.keys(icons).find(key => icons[key] === icon);
        return iconKey ? iconKey : 'unknown';
      }
    }
  }
  </script>
  
  <style scoped>
  ion-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }
  
  ion-icon {
    margin-bottom: 8px;
  }
  </style>
  