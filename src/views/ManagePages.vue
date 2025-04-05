<template>
    <ion-page>
      <!--<ion-header>
        <ion-toolbar color="primary">
          <ion-title>Manage Pages</ion-title>
        </ion-toolbar>
      </ion-header>-->
      <ion-content class="ion-padding">
        <!-- Add New Page -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Add New Page</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item>
                <ion-label position="stacked">Title</ion-label>
                <ion-input v-model="title" placeholder="Enter page title"></ion-input>
              </ion-item>
              <ion-item>
                <ion-label position="stacked">URL</ion-label>
                <ion-input v-model="url" placeholder="Enter page URL"></ion-input>
              </ion-item>
              <ion-item>
                <ion-label position="stacked">Icon</ion-label>
                <ion-input v-model="icon" placeholder="Enter icon name (e.g., home, options)"></ion-input>
              </ion-item>
            </ion-list>
            <ion-button expand="block" @click="submitPage">Add Page</ion-button>
          </ion-card-content>
        </ion-card>
  
        <!-- Existing Pages with Pagination -->
        <ion-card style="margin-top: 20px;">
          <ion-card-header>
            <ion-card-title>Existing Pages</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item v-for="(page, index) in pages" :key="index">
                <ion-icon :name="page.icon ? page.icon : 'help-circle-outline'" slot="start"></ion-icon>
                <ion-label>
                  <h3>{{ page.title }}</h3>
                  <p>{{ page.url }}</p>
                </ion-label>
              </ion-item>
            </ion-list>
            <ion-grid>
              <ion-row class="ion-justify-content-between ion-align-items-center">
                <ion-col size="4">
                  <ion-button expand="block" @click="prevPage" :disabled="currentPage <= 1">
                    Previous
                  </ion-button>
                </ion-col>
                <ion-col size="4" class="ion-text-center">
                  Page {{ currentPage }} of {{ totalPages }}
                </ion-col>
                <ion-col size="4">
                  <ion-button expand="block" @click="nextPage" :disabled="currentPage >= totalPages">
                    Next
                  </ion-button>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>
      </ion-content>
    </ion-page>
  </template>
  
  <script>
  export default {
    name: "ManagePages",
    data() {
      return {
        // Fields for adding a new page
        title: "",
        url: "",
        icon: "",
        // Pagination for existing pages
        pages: [],
        currentPage: 1,
        totalPages: 1,
        limit: 30,
      };
    },
    mounted() {
      this.loadPages();
    },
    methods: {
      loadPages() {
        this.$axios
          .get(`new_page.php?page=${this.currentPage}`)
          .then((response) => {
            if (response.data.status === "success") {
              this.pages = response.data.data;
              this.totalPages = response.data.total_pages;
            } else {
              alert("Error loading pages");
            }
          })
          .catch((error) => {
            console.error(error);
            alert("Error loading pages");
          });
      },
      submitPage() {
        if (this.title && this.url) {
          // Always pass an empty string for html content
          const postData = this.$qs.stringify({
            addPage: "addPage",
            title: this.title,
            url: this.url,
            icon: this.icon,
            html: ""
          });
          this.$axios
            .post("new_page.php", postData)
            .then((response) => {
              if (response.data.status === "success") {
                alert("Page added successfully!");
                // Reset fields and reload pages
                this.title = "";
                this.url = "";
                this.icon = "";
                // Optionally, go to the first page when a new item is added
                this.currentPage = 1;
                this.loadPages();
              } else {
                alert(response.data.message);
              }
            })
            .catch((error) => {
              console.error(error);
              alert("Error adding page");
            });
        } else {
          alert("Title and URL are required!");
        }
      },
      nextPage() {
        if (this.currentPage < this.totalPages) {
          this.currentPage++;
          this.loadPages();
        }
      },
      prevPage() {
        if (this.currentPage > 1) {
          this.currentPage--;
          this.loadPages();
        }
      }
    }
  };
  </script>
  
  <style scoped>
  /* Add any view-specific styles here */
  </style>