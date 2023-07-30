<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Create Group</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <ion-list>
        <ion-item>
          <ion-label position="stacked">Group Name</ion-label>
          <ion-input v-model="groupName" type="text"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="stacked">Participants</ion-label>
          <ion-select multiple v-model="selectedParticipants">
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
          <ion-label position="stacked">Group Image (optional)</ion-label>
          <input type="file" @change="handleImageUpload" />
        </ion-item>
      </ion-list>
      <ion-button expand="full" @click="createGroup">Create Group</ion-button>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      groupName: "",
      selectedParticipants: [],
      participants: [
        { id: 1, name: "User 1" },
        { id: 2, name: "User 2" },
        { id: 3, name: "User 3" },
        // Add more participants as needed
      ],
      groupImage: null,
    };
  },
  methods: {
    handleImageUpload(event) {
      const file = event.target.files[0];
      this.groupImage = file;
    },
    createGroup() {
      const formData = new FormData();
      formData.append("image", this.groupImage);

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
  },
};
</script>

<style scoped>
/* Add custom styles here */
</style>
