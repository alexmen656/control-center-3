<template>
    <ion-page>
      <ion-content class="ion-padding">
        <div class="verification-container">
          <ion-icon name="alert-circle-outline" class="verification-icon"></ion-icon>
          <h2 class="verification-title">Your Account is Under Verification</h2>
          <p class="verification-message">Verification may take up to 3 days.</p>
        </div>
      </ion-content>
    </ion-page>
  </template>
  
  <script>
  export default {
    name: 'PendingVerificationPage',
  };
  </script>
  
  <style scoped>
  .verification-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
  }
  
  .verification-icon {
    font-size: 100px;
    color: red;
  }
  
  .verification-title {
    font-size: 24px;
    margin-top: 20px;
  }
  
  .verification-message {
    font-size: 18px;
    margin-top: 10px;
    text-align: center;
  }
  </style>
  