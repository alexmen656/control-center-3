<template>
            <span v-for="index in input.length" :key="index">*</span>
 
  <ion-grid>
    <ion-row>
      <ion-col size="4" v-for="(nichts, index) in 9" :key="index+1"><ion-button @click="onClick(index)" size="large" fill="outline">{{ index+1 }}</ion-button></ion-col>
      <ion-col size="4"/>
      <ion-col size="4"><ion-button @click="onClick(0)" size="large" fill="outline">0</ion-button></ion-col>
      <ion-col size="4"/>
    </ion-row>
  </ion-grid>
</template>
<script>
export default {
    name: "PinVerification",
    data(){
        return {
            pin: "181881",
            input: ""
        };
    },
    methods: {
    onClick(index){
        this.input = this.input+(1+index);
        if(this.input.length == 6){
            if(this.input == this.pin){
                //alert("you are now logged In")
                this.emitter.emit("authenticated");

                this.$router.push('/dashboard')
            }else{
                alert("Wrong Pin, try again!");
                this.input = "";
            }
        }
    }
    }
};
</script>