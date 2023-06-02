<template>
    <ion-page>
   <!--     <StreamBarcodeReader
        class="reader"
    @decode="onDecode"
    @loaded="onLoaded"
></StreamBarcodeReader>---->
<ion-button @click="print">Print</ion-button>
<div class="img-box">
   <h1>Apple Watch Series 4 44 mm</h1>
<img alt='Barcode Generator TEC-IT'
       src='https://barcode.tec-it.com/barcode.ashx?data=83753742&code=&translate-esc=true&dpi=600&hidehrt=True'/>
    </div>
    <ion-button @click="subscribe">Push abonnieren</ion-button>
    <ion-button @click="unsubscribe">Push abbestellen</ion-button>
    </ion-page>
  </template>
  
  <script>
  import { StreamBarcodeReader } from "vue-barcode-reader";
  import axios from 'axios';
  import qs from 'qs';

  export default {
    name: "BarcodeScanner",
    components: {
       // StreamBarcodeReader
    },
    methods: {
        onDecode(text) {
    
            const audio = new Audio('https://alex.polan.sk/control-center/scanner.mp3');

    console.log(`Decode text from QR code is ${text}`)
    //alert(text);
            axios.post("https://alex.polan.sk/control-center/products.php", qs.stringify({getProductByCode: "getProductByCode", code: text})).then(res=>{
                audio.play().then( kg => {
                     alert(res.data.title + "\n" + res.data.price + "€");
                });
                   
                   
                
                    
               
                 
              // audio.play();
  
            });

},
onLoaded() {
   console.log(`Ready to start scanning barcodes`)
   
},
print(){
  window.print();
},


    subscribe() {
      // Überprüfen, ob der Browser Push-Benachrichtigungen unterstützt
      if ('serviceWorker' in navigator && 'PushManager' in window) {
        // Service Worker registrieren
        navigator.serviceWorker.register('/service-worker.js')
          .then((registration) => {
            // Benutzer um Erlaubnis zur Anzeige von Push-Benachrichtigungen bitten
            return registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: 'AIzaSyAF53AYFblvyoeCHvXqUT--C5lnYf095VY',
            });
          })
          .then((subscription) => {
            // Senden Sie das Push-Abonnementobjekt an Ihren Server
            axios.post('/api/subscribe', subscription)
              .then((response) => {
                 alert('Push-Abonnement erfolgreich gespeichert');
              })
              .catch((error) => {
                console.error('Fehler beim Speichern des Push-Abonnements:', error);
              });
          })
          .catch((error) => {
            console.error('Fehler bei der Registrierung des Service Workers:', error);
          });
      } else {
        alert('Push-Benachrichtigungen werden von diesem Browser nicht unterstützt.');
      }
    }
  }
  }
  
  </script>
  <style>
  @media print {

    .img-box {
        width: 210mm !important;
        height: 297mm !important;
      position: absolute;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    img {
        display: block !important;
        width: 11cm;
      /*  left: 5cm !important;*/
        top: 40% !important;
    }

    ion-button, .reader, ion-header, ion-menu, ion-title  {
    display: none !important;
    width: 0;
    height: 0;
}


@page {
    size: auto;   /* auto is the initial value */
    margin: 0;  /* this affects the margin in the printer settings */
}
  }

</style>
