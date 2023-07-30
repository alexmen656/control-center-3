<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-title>Create New Function</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <ion-list>
        <ion-item>
          <ion-label position="stacked">Name</ion-label>
          <ion-input
            type="text"
            v-model="name"
            :value="name"
            @ionInput="name = $event.target.value"
          ></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="stacked">Description</ion-label>
          <ion-textarea
            v-model="description"
            :value="description"
            @ionInput="description = $event.target.value"
          ></ion-textarea>
        </ion-item>
        <!--<ion-item>
            <ion-label position="stacked">Examples</ion-label>
            <ion-textarea v-model="examples" :value="examples" @ionInput="examples = $event.target.value;"></ion-textarea>
          </ion-item>-->
      </ion-list>
      <ion-button expand="block" color="primary" @click="createFunction()"
        >Create</ion-button
      >
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import qs from "qs";

export default {
  name: "CreatePage",
  data() {
    return {
      name: "",
      description: "",
      examples: "",
    };
  },
  methods: {
    async createFunction() {
      const data = {
        name: this.name,
        description: this.description,
        examples: this.examples,
      };
      await axios
        .post("/control-center/create.php", qs.stringify(data))
        .then(() => {
          // this.$router.push('/');
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>
