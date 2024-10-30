<template>
  <ion-page>
    <!--  <ion-header>
        <ion-toolbar color="primary">
          <ion-title>Control Center Info</ion-title>
        </ion-toolbar>
      </ion-header>-->
    <ion-content class="ion-padding">
      <ion-card>
        <!--   <ion-card-header>
            <ion-card-title>{{ functionData.name }}</ion-card-title>
          </ion-card-header>-->
        <ion-card-content>
          <ion-text>{{ functionData.description }}</ion-text>
          <br />
          <!--   <ion-label>Examples:</ion-label>
            <ion-list>
              <ion-item v-for="(example, index) in functionData.examples" :key="index">
                <div v-if="example.type === 'text'">
                  <ion-text>{{ example.content }}</ion-text>
                </div>
                <div v-else-if="example.type === 'image'">
                  <img :src="example.content" />
                </div>
              </ion-item>
            </ion-list>-->
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: "InfoView",
  data() {
    return {
      functionData: {},
      functionName: "",
    };
  },
  created() {
    this.functionName = this.$route.params.function;
    this.fetchFunctionData();
  },
  methods: {
    async fetchFunctionData() {
      try {
        const response = await this.$axios.get(
          `info.php?function=${this.functionName}`
        );
        this.functionData = response.data;
      } catch (error) {
        console.error(error);
      }
    },
  },
};
</script>

<style>
ion-card-header {
  background-color: #ff4d4d;
  color: #fff;
}

ion-label {
  color: #ff4d4d;
  font-weight: bold;
}
</style>
