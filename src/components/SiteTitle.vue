<template>
  <ion-toolbar>
    <ion-title size="large">
      <div class="title-container">
      <ion-icon class="before-title" v-if="icon" :name="icon" />
      <span class="h2">{{ title[0].toUpperCase() + title.slice(1) }}</span>
      </div>
    </ion-title>
    <span class="actions" slot="end">
      <ion-icon
        slot="end"
        @click="toggleBookmark()"
        :name="isBookmark ? 'star' : 'star-outline'"
      />
      <ion-icon
        @click="share()"
        slot="end"
        name="share-social-outline"
        class="copy-effect"
      />
    </span>
  </ion-toolbar>
</template>

<script>
export default {
  name: "SiteTitle",
  props: {
    title: {
      type: String,
    },
    icon: {
      type: String,
    },
  },
  data() {
    return {
      isBookmark: false,
    };
  },
  mounted() {
    this.siteLocation = "";
    if (
      window.location.pathname.charAt(window.location.pathname.length - 1) ==
      "/"
    ) {
      this.siteLocation = window.location.pathname.slice(0, -1);
    } else {
      this.siteLocation = window.location.pathname;
    }

    this.$watch(
      () => this.$route.params,
      () => {
        this.$axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?" +
              this.$qs.stringify({
                location: this.siteLocation,
                checkBookmark: "checkBookmark",
              })
          )
          .then((response) => {
            this.isBookmark = response.data;
          });
      }
    );
    this.$axios
      .post(
        "https://alex.polan.sk/control-center/bookmarks.php?" +
          this.$qs.stringify({
            location: this.siteLocation,
            checkBookmark: "checkBookmark",
          })
      )
      .then((response) => {
        this.isBookmark = response.data;
      });
  },
  methods: {
    share() {
      if (navigator.share) {
        navigator.share({ text: "", url: "", title: "gh" });
      } else {
        navigator.clipboard.writeText(window.location.href);
        this.$nextTick(() => {
          const shareButton = document.querySelector(".copy-effect");
          shareButton.classList.add("copied");
          setTimeout(() => {
            shareButton.classList.remove("copied");
          }, 2000);
        });
      }
    },

    toggleBookmark() {
      if (this.isBookmark) {
        this.isBookmark = false;
        this.$axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?deleteBookmark=deleteBookmark&location=" +
              this.siteLocation
          )
          .then(() => {//response
            this.$emit("updateSidebar");
          });
      } else {
        this.isBookmark = true;
        this.$axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?newBookmark=newBookmark&icon=" +
              this.icon +
              "&title=" +
              this.title +
              "&location=" +
              this.siteLocation
          )
          .then(() => {//response
            this.$emit("updateSidebar");
          });
      }
    },
  },
  //method
};
</script>
<style scoped>
h1 {
  margin-top: 55px !important;
}

.h2 {
  margin: 0;
  font-size: 1.4rem;
}

ion-icon {
font-size: 1.125rem;
}

ion-icon.before-title {
  font-size: 1.25rem;
  margin-right: 0.35rem;
}

.title-container {
  display: flex !important;
  align-items: center;
  color: black;
}

@media (prefers-color-scheme: dark) {
  ion-title {
    color: white;
  }
}

ion-toolbar {
  --background: #f8fafc !important;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.actions {
  margin-right: 10px;
  display: inline;
}

.actions > ion-icon {
  margin-right: 5px;
}

.copied {
  /* CSS für den Effekt nach dem Kopieren */
  animation-name: booom;
  animation-duration: 0.5s;
  animation-timing-function: linear;
}

@keyframes booom {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.5);
  }
  100% {
    transform: scale(1);
  }
}
</style>
