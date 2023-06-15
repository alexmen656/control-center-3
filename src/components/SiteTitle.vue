<template>
   <ion-header>
  <ion-toolbar>
    <ion-title size="large">
      <ion-icon :name="icon"></ion-icon>
      {{ title }}
    </ion-title>
    <span class="actions" slot="end">
      <ion-icon
        slot="end"
        @click="toggleBookmark()"
        :name="isBookmark ? 'star' : 'star-outline'"
      ></ion-icon>
      <ion-icon
        @click="share()"
        slot="end"
        name="share-social-outline"
        class="copy-effect"
      ></ion-icon>
    </span>
  </ion-toolbar>
  </ion-header>
</template>

<script>
import axios from "axios";
import qs from "qs";
import { IonTitle, IonIcon } from "@ionic/vue";

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
  components: {
    IonTitle,
    IonIcon,
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
        axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?" +
              qs.stringify({
                location: this.siteLocation,
                checkBookmark: "checkBookmark",
              })
          )
          .then((response) => {
            this.isBookmark = response.data;
          });
      }
    );
    axios
      .post(
        "https://alex.polan.sk/control-center/bookmarks.php?" +
          qs.stringify({
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
        axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?deleteBookmark=deleteBookmark&location=" +
              this.siteLocation
          )
          .then((response) => {
            this.$emit("updateSidebar");
          });
      } else {
        this.isBookmark = true;
        axios
          .post(
            "https://alex.polan.sk/control-center/bookmarks.php?newBookmark=newBookmark&icon=" +
              this.icon +
              "&title=" +
              this.title +
              "&location=" +
              this.siteLocation
          )
          .then((response) => {
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

ion-icon {
  font-size: 1rem;
}

ion-title {
  display: flex;
  align-items: center;
  color: black;


}

@media (prefers-color-scheme: dark) {
  ion-title {
    color: white;
  }
}

ion-toolbar {
  --background: transparent !important;
  padding-top: .5rem;
  padding-bottom: .5rem;
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
