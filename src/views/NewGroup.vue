<template>
  <ion-page>
    <ion-content class="ion-padding">
      <ion-list>
        <!-- <ion-item>
          <ion-label position="stacked">Group Name</ion-label>
          <ion-input v-model="groupName" type="text"></ion-input>
        </ion-item>-->

        <FloatingInput
          v-model="groupName"
          label="Group Name"
          placeholder="best group ever!"
          type="text"
        />
        <FloatingSelect v-model="selectedParticipants" :select="select" multiple="true" />

        <!--    <ion-item>
          <ion-label position="stacked">Participants</ion-label>
          <ion-select fill="outline" multiple v-model="selectedParticipants">
            <ion-select-option
              v-for="participant in participants"
              :key="participant.id"
              :value="participant.id"
            >
              {{ participant.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-label position="stacked">Group Image (optional)</ion-label>-->
        <ion-input fill="outline" type="file" @change="handleImageUpload" />
        <!--</ion-item>-->
      </ion-list>
      <ion-button expand="full" @click="createGroup">Create Group</ion-button>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import { getUsers } from "@/getData";
import { getUserData } from "@/userData";

//import FloatingCheckbox from "@/components/FloatingCheckbox.vue";

export default {
  components: {
    FloatingInput,
    FloatingSelect,
  },
  data() {
    return {
      users: [],
      groupName: "",
      selectedParticipants: [],
      userData: {},
      participants: [
        { id: 1, name: "User 1" },
        { id: 2, name: "User 2" },
        { id: 3, name: "User 3" },
        // Add more participants as needed
      ],
      groupImage: null,
      select: {
        type: "select",
        name: "participants",
        label: "Participants",
        placeholder: "Participants",
        options: [
         /* {
            name: "1",
            label: "User1",
          },
          {
            name: "2",
            label: "User2",
          },
          {
            name: "3",
            label: "User3",
          },*/
        ],
      },
    };
  },
  async mounted() {
    this.userData = await getUserData();
    this.users = await getUsers();
    console.log(this.users);
    this.users.forEach((user)=>{
      if(user.userID != this.userData.userID){
              this.select.options.push({value: user.userID, label: user.firstName + " " + user.lastName})
      }
    });
  },
  methods: {
  handleImageUpload(event) {
    const file = event.target.files[0];
    this.groupImage = file;
  },
  createGroup() {
    if (!this.groupImage) {
      console.error("Please upload a group image.");
      return;
    }
    this.selectedParticipants.push(this.userData.userID);
    const formData = new FormData();
    formData.append("image", this.groupImage);
    formData.append("name", this.groupName);
    formData.append("participants", this.selectedParticipants);

    axios
      .post("/control-center/groups.php", formData)
      .then((response) => {
        if (response.data.success) {
          const imagePath = response.data.path;
          // Send group data to the server
          const groupData = {
            name: this.groupName,
            participants: this.selectedParticipants,
            image: imagePath,
          };

          // Send the group data to your backend or perform any required actions
          axios
            .post("/control-center/groups.php", groupData)
            .then((response) => {
              // Handle the response from the server
              console.log(response.data);
            })
            .catch((error) => {
              console.error("Error creating group:", error);
            });
        } else {
          console.error("Image upload failed:", response.data.message);
        }
      })
      .catch((error) => {
        console.error("Image upload failed:", error);
      });
  },
}

};
</script>

<style scoped>
/* Add custom styles here */
</style>
