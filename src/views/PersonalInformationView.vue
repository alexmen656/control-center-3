<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col
            size="10"
            style="display: flex; align-items: center; flex-direction: column"
          >
            <ion-avatar @click="takePhoto()">
              <ion-icon style="display: none" name="camera-outline"></ion-icon>
              <img v-if="user.profileImg != 'avater'" :src="user.profileImg" />
            </ion-avatar>
            <ion-list style="width: 100%">
              <ion-col style="display: flex; margin-bottom: 0.5rem !important">
                <ion-item style="width: 50%">
                  <ion-label position="stacked">Name</ion-label>
                  <ion-input
                    :disabled="!user.firstName.editable"
                    :value="user.firstName.value"
                    @input="user.firstName.value = $event.target.value"
                  ></ion-input>
                  <ion-button
                    slot="end"
                    color="danger"
                    fill="clear"
                    @click="edit('firstName')"
                  >
                    <ion-icon name="pencil"></ion-icon>
                  </ion-button>
                </ion-item>
                <ion-item style="width: 50%">
                  <ion-label position="stacked">Surname</ion-label>
                  <ion-input
                    :disabled="!user.lastName.editable"
                    :value="user.lastName.value"
                    @input="user.lastName.value = $event.target.value"
                  ></ion-input>
                  <ion-button
                    slot="end"
                    color="danger"
                    fill="clear"
                    @click="edit('lastName')"
                  >
                    <ion-icon name="pencil"></ion-icon>
                  </ion-button>
                </ion-item>
              </ion-col>
              <ion-item style="margin-bottom: 0.5rem !important">
                <ion-label position="stacked">E-Mail-Adresse</ion-label>
                <ion-input
                  :disabled="!user.email.editable"
                  :value="user.email.value"
                  @input="user.email.value = $event.target.value"
                ></ion-input>
                <ion-button
                  slot="end"
                  color="danger"
                  fill="clear"
                  @click="edit('email')"
                >
                  <ion-icon name="pencil"></ion-icon>
                </ion-button>
              </ion-item>
              <ion-item style="margin-bottom: 0.5rem !important">
                <ion-label position="stacked">Telefonnummer</ion-label>
                <ion-input
                  :disabled="!user.phone.editable"
                  :value="user.phone.value"
                  @input="user.phone.value = $event.target.value"
                ></ion-input>
                <ion-button
                  slot="end"
                  color="danger"
                  fill="clear"
                  @click="edit('phone')"
                >
                  <ion-icon name="pencil"></ion-icon>
                </ion-button>
              </ion-item>
              <ion-item>
                <ion-label position="stacked">Adresse</ion-label>
                <ion-textarea
                  :disabled="!user.address.editable"
                  :value="user.address.value"
                  @input="user.address.value = $event.target.value"
                ></ion-textarea>
                <ion-button
                  slot="end"
                  color="danger"
                  fill="clear"
                  @click="edit('address')"
                >
                  <ion-icon name="pencil"></ion-icon>
                </ion-button>
              </ion-item>
            </ion-list>
            <ion-button expand="block" color="danger" @click="save"
              >Profil speichern</ion-button
            >
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  IonIcon,
  IonTextarea,
  IonAvatar,
} from "@ionic/vue";
import { defineComponent } from "vue";
import axios from "axios";
import qs from "qs";
import { usePhotoGallery, Photo } from "@/composables/updateProfileImage";
import { getUserData } from "@/userData";

export default defineComponent({
  name: "PersonalInformation",
  components: {
    IonPage,
    IonContent,
    IonList,
    IonItem,
    IonLabel,
    IonInput,
    IonButton,
    IonIcon,
    IonTextarea,
    IonAvatar,
  },
  data() {
    return {
      user: {
        firstName: { value: "", editable: true },
        lastName: { value: "", editable: true },
        email: { value: "", editable: false },
        phone: { value: "", editable: false },
        address: { value: "", editable: true },
      },
    };
  },
  methods: {
    edit(field) {
      this.user[field].editable = true;
    },
    save() {
      const filteredUser = {};
      for (const field in this.user) {
        if (Object.prototype.propertyIsEnumerable.call(this.user, field)) {
          if (field !== "id" && field !== "editable") {
            filteredUser[field] = this.user[field]["value"];
          }
        }
      }
      filteredUser["editData"] = "editData";
      axios
        .post("/control-center/user.php?" + qs.stringify(filteredUser), {
          headers: {
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response);
          console.log("API response:", response.data);
        })
        .catch((error) => {
          console.error("API error:", error);
        });

      for (const field in this.user) {
        if (Object.prototype.propertyIsEnumerable.call(this.user, field)) {
          this.user[field].editable = false;
        }
      }
    },
  },
  async mounted() {
    const data = await getUserData();
    console.log(data);
    this.user.email.value = data.email;
    this.user.firstName.value = data.firstName;
    this.user.lastName.value = data.lastName;
    this.user.profileImg = data.profileImg;
  },
  setup() {
    const { takePhoto, photos } = usePhotoGallery();
    return {
      takePhoto,
      photos,
    };
  },
});
</script>
<style scoped>
ion-list {
  background: var(--ion-background-color);
}
ion-avatar {
  display: flex;
  justify-content: center;
  align-items: center;
}
ion-avatar > ion-icon {
  position: absolute;
}

ion-avatar:hover {
  opacity: 0.35;
}

ion-avatar:hover > ion-icon {
  display: block !important;
  opacity: 1 !important;
}
</style>
