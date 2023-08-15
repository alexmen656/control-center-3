<template>
  <ion-page>
    <ion-content>
      <AlertMessage
        v-if="successMessage"
        :message="{ title: 'Success!', content: 'User created successful' }"
      />
      <TableCard :labels="labels" :data="data" />

      <h2 v-if="pendingVerificationEntries.length > 0">
        Waiting for verification
      </h2>

      <ion-card v-if="pendingVerificationEntries.length > 0">
        <table>
          <tr>
            <th>User ID</th>
            <th>Profile Image</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>E-Mail</th>
            <th>Password</th>
            <th>Google LogIn</th>
            <th>Account Status</th>
            <th>Approve</th>
          </tr>
          <tr v-for="tr in pendingVerificationEntries" :key="tr">
            <td>{{ tr[0] }}</td>
            <td>{{ tr[1] }}</td>
            <td>{{ tr[2] }}</td>
            <td>{{ tr[3] }}</td>
            <td>{{ tr[4] }}</td>
            <td>{{ tr[5] }}</td>
            <td>{{ tr[6] }}</td>
            <td>{{ tr[8] }}</td>
            <td>
              <ion-button color="success" size="small" @click="approve(tr[0])"
                >Approve</ion-button
              >
            </td>
          </tr>
        </table>
      </ion-card>

      <ion-button id="open-modal">
        <ion-icon name="add-outline" />
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
                  />
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
                  />
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
            />
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
            />
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import TableCard from "@/components/TableCard.vue";
import AlertMessage from "@/components/AlertMessage.vue";
import { defineComponent, ref } from "vue";
import {
  IonPage,
  IonButton,
  IonContent,
  IonModal,
  IonItem,
  IonInput,
  IonLabel,
  IonGrid,
  IonRow,
  IonCol,
  IonHeader,
  IonToolbar,
  IonButtons,
  IonIcon,
  IonTitle,
} from "@ionic/vue";
//import { OverlayEventDetail } from "@ionic/core/components";
import axios from "axios";
import qs from "qs";

export default defineComponent({
  components: {
    TableCard,
    IonPage,
    IonButton,
    IonContent,
    IonModal,
    AlertMessage,
    IonItem,
    IonInput,
    IonLabel,
    IonGrid,
    IonRow,
    IonCol,
    IonHeader,
    IonToolbar,
    IonButtons,
    IonIcon,
    IonTitle,
  },
  setup() {
    const labels = ref([]);
    const data = ref([]);
    const data2 = ref({});
    const pendingVerificationEntries = ref([]);

    axios
      .post(
        "/control-center/mysql.php",
        qs.stringify({ getTableByName: "control_center_users" })
      )
      .then((res) => {
        labels.value = res.data.labels;
        data.value = res.data.data;

        data2.value = res.data;

        const accountStatusIndex = data2.value.labels.indexOf("account_status");
        pendingVerificationEntries.value = data2.value.data.filter(
          (entry) => entry[accountStatusIndex] === "pending_verification"
        );
      });

    return {
      labels,
      data,
      pendingVerificationEntries,
    };
  },
  data() {
    return {
      password: "",
      email_adress: "",
      first_name: "",
      last_name: "",
      message: "",
      successMessage: "",
    };
  },
  methods: {
    cancel() {
      this.$refs.modal.$el.dismiss(null, "cancel");
    },
    confirm() {
      if (this.password && this.email_adress && this.first_name) {
        axios
          .post(
            "/control-center/users.php",
            qs.stringify({
              new_user: "new_user",
              first_name: this.first_name,
              last_name: this.last_name,
              email_adress: this.email_adress,
              password: this.password,
            })
          )
          .then((res) => {
            console.log(res);
            this.successMessage = res.data;

            axios
              .post(
                "/control-center/mysql.php",
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
        this.$refs.modal.$el.dismiss(null, "confirm");
      } else {
        console.log("empty");
      }
    },
    approve(userID) {
      axios
        .post(
          "/control-center/users.php",
          qs.stringify({
            updateAccountStatus: "updateAccountStatus",
            userID: userID,
            newStatus: "active",
          })
        )
        .then(() => {
          axios
            .post(
              "/control-center/mysql.php",
              qs.stringify({ getTableByName: "control_center_users" })
            )
            .then((res) => {
              this.labels = res.data.labels;
              this.data = res.data.data;
              this.data2 = res.data;
              const accountStatusIndex =
                this.data2.labels.indexOf("account_status");
              this.pendingVerificationEntries = this.data2.data.filter(
                (entry) => entry[accountStatusIndex] === "pending_verification"
              );
            });
          alert(userID + " approved");
        });
    },
  },
});
</script>
<style scoped>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td,
th {
  border: none;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #e9e9e9;
}

@media (prefers-color-scheme: dark) {
  tr:nth-child(even) {
    background-color: #121212;
  }
}
</style>
