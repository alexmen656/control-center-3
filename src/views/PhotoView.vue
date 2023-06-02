<template>
  <ion-page>
      <ion-header>
          <ion-title>Vue Camera App</ion-title>
      </ion-header>
      <ion-content :fullscreen="true">

        <ion-grid>
          <ion-row>
            <ion-col size="6" :key="photo.filepath" v-for="photo in photos">
            <ion-img :src="photo.webviewPath"></ion-img>
          </ion-col>
          </ion-row>
        </ion-grid>
          <ion-fab vertical="bottom" horizontal="center" slot="fixed">
              <ion-fab-button @click="takePhoto()">
                  <ion-icon :icon="camera"></ion-icon>
              </ion-fab-button>
          </ion-fab>
      </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { camera, trash, close } from 'ionicons/icons';
import { IonPage, IonHeader, IonTitle, IonContent, IonFab, IonFabButton, IonGrid, IonRow, IonCol, IonImg } from '@ionic/vue';
import {usePhotoGallery, Photo} from '@/composables/usePhotoGallery';
//import '@/checkLogin';

export default {
name: 'PhotoView',
components: {
  IonPage, IonHeader, IonTitle, IonContent, IonFab, IonFabButton, IonGrid, IonRow, IonCol, IonImg 
},
setup(){
  const { takePhoto, photos } = usePhotoGallery();
  return {
      camera, trash, close, takePhoto, photos
  }
}
}
</script>