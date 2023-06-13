<template>
  <ion-page>
    <ion-content>
      <AlertMessage
        v-if="successMessage"
        :message="{ title: 'Success!', content: 'User created successful' }"
      />
      <TableCard :labels="labels" :data="data"></TableCard>
      <ion-button id="open-modal">
        <ion-icon name="add-outline"/>
        New User
      </ion-button>
      <ion-modal ref="modal" trigger="open-modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="cancel()" style="color: red"
                >Cancel</ion-button
              >
            </ion-buttons>
            <ion-title style="text-align: center">Create new user</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="confirm()" style="color: red"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-grid>
            <ion-row>
              <ion-col>
                <ion-item>
                  <ion-label position="stacked">First name</ion-label>
                  <ion-input
                    ref="first_name"
                    type="text"
                    placeholder="Enter your first name"
                    v-model="first_name"
                    :value="first_name"
                    @ionInput="first_name = $event.target.value"
                  ></ion-input>
                </ion-item>
              </ion-col>
              <ion-col>
                <ion-item>
                  <ion-label position="stacked">Last name</ion-label>
                  <ion-input
                    ref="last_name"
                    type="text"
                    placeholder="Enter your last name"
                    v-model="last_name"
                    :value="last_name"
                    @ionInput="last_name = $event.target.value"
                  ></ion-input>
                </ion-item>
              </ion-col>
            </ion-row>
          </ion-grid>
          <ion-item>
            <ion-label position="stacked">Email address</ion-label>
            <ion-input
              ref="email_adress"
              type="email"
              placeholder="Enter your email address"
              v-model="email_adress"
              :value="email_adress"
              @ionInput="email_adress = $event.target.value"
            ></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="stacked">Password</ion-label>
            <ion-input
              ref="password"
              type="password"
              placeholder="Enter your password"
              v-model="password"
              :value="password"
              @ionInput="password = $event.target.value"
            ></ion-input>
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script lang="js">
import TableCard from "@/components/TableCard.vue";
import AlertMessage from '@/components/AlertMessage.vue';
import { defineComponent, ref } from "vue";
import { IonPage, IonButton, IonContent, IonModal } from "@ionic/vue";
import { OverlayEventDetail } from "@ionic/core/components";
import axios from "axios";
import qs from "qs";

export default defineComponent({
  components: {
    TableCard,
    IonPage,
    IonButton,
    IonContent,
    IonModal,
    AlertMessage
  },
  setup() {
    const labels = ref([]);
    const data = ref([]);

    axios
      .post(
        "https://alex.polan.sk/control-center/mysql.php",
        qs.stringify({ getTableByName: "control_center_users" })
      )
      .then((res) => {
        labels.value = res.data.labels;
        data.value = res.data.data;
      });

    return {
      labels,
      data,
    };
  },
  data() {
    return {
        password: "",
        email_adress: "",
        first_name: "",
        last_name: "",
        message: "",
        successMessage: ""
    };
  },
  methods: {
      cancel() {
        this.$refs.modal.$el.dismiss(null, 'cancel');
      },
      confirm() {
      if(this.password && this.email_adress && this.first_name){
         axios
          .post(
            "https://alex.polan.sk/control-center/users.php",
            qs.stringify({
              new_user: "new_user",
              first_name: this.first_name,
              last_name: this.last_name,
              email_adress: this.email_adress,
              password: this.password
            })
            ).then(res => {
          console.log(res);
          this.successMessage = res.data;

          axios
            .post(
              "https://alex.polan.sk/control-center/mysql.php",
              qs.stringify({ getTableByName: "control_center_users" })
            )
            .then((res) => {
              this.labels = res.data.labels;
              this.data = res.data.data;
            });

          setTimeout(() => {
            this.successMessage = "";
            console.log(this.successMessage);
          }, 3000);
         });
         this.$refs.modal.$el.dismiss(null, 'confirm');
      }else{
        console.log("empty")
      }
      },
    },
});
</script>