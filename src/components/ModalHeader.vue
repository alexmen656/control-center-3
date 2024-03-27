<template>
      <ion-header>
    <ion-toolbar>
      <ion-buttons slot="start">
        <ion-button @click="cancel()">Cancel</ion-button>
      </ion-buttons>
      <ion-title style="text-align: center">Edit Entry</ion-title
      ><!--{{ data.id }} {{ data.project }} {{ data.form }}-->
      <ion-buttons slot="end">
        <ion-button :strong="true" @click="confirm()">Confirm</ion-button>
      </ion-buttons>
    </ion-toolbar>
  </ion-header>
</template>