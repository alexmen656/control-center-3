<template>
  <div class="navigation-tree" :style="'--background: ' + bg_color">
    <div class="tree-container">
      <!-- Breadcrumb Navigation -->
      <nav class="breadcrumb-nav">
        <div class="breadcrumb-path">
          <span class="breadcrumb-item home-item">
            <ion-icon name="home-outline" class="home-icon"></ion-icon>
            <span>Home</span>
          </span>
          <ion-icon name="chevron-forward-outline" class="separator-icon"></ion-icon>
          <span class="breadcrumb-current">{{ title[0].toUpperCase() + title.slice(1) }}</span>
        </div>
        <!-- Actions
        <div class="title-actions">
          <button class="action-icon" @click="toggleBookmark()"
            :title="isBookmark ? 'Remove bookmark' : 'Add bookmark'">
            <ion-icon :name="isBookmark ? 'star' : 'star-outline'"></ion-icon>
          </button>
          <button class="action-icon" @click="share()" title="Share">
            <ion-icon name="share-social-outline" class="copy-effect"></ion-icon>
          </button>
        </div> -->
      </nav>
    </div>
  </div>
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
    bg: {
      type: String,
      required: false
    }
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

    if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
      this.bg_color = this.bg || "#0f172a";
    } else {
      this.bg_color = "#f8fafc";
    }
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
.navigation-tree {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;

  background: var(--background);
  padding: 16px 20px 0px 20px;
 /* margin-bottom: 24px;*/
}

.tree-container {
  max-width: 1400px;
  margin: 0 auto;
}

/* Breadcrumb Navigation
.breadcrumb-nav {
  margin-bottom: 12px;
} */

.breadcrumb-path {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  padding: 4px 8px 4px 0px;
  border-radius: var(--radius);
}

.breadcrumb-item.home-item {
  cursor: pointer;
}

.breadcrumb-item.home-item:hover {
  color: var(--primary-color);
  background: rgba(37, 99, 235, 0.08);
}

.home-icon {
  font-size: 14px;
}

.separator-icon {
  font-size: 12px;
  color: var(--text-muted);
}

.breadcrumb-current {
  color: var(--text-primary);
  font-weight: 500;
}

/* Title Section */
.title-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.title-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.title-icon {
  font-size: 28px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.main-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

/* Title Actions */
.title-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-icon:hover {
  color: var(--primary-color);
  background: rgba(37, 99, 235, 0.08);
  border-color: var(--primary-color);
  transform: scale(1.05);
}

.action-icon ion-icon {
  font-size: 16px;
}

.action-icon.copied,
.action-icon .copy-effect.copied {
  animation: pulse 0.5s ease;
  color: #10b981;
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.15);
  }

  100% {
    transform: scale(1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .navigation-tree {
    padding: 12px 16px;
   /* margin-bottom: 16px;*/
  }

  .title-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .title-icon {
    font-size: 24px;
  }

  .main-title {
    font-size: 20px;
  }

  .breadcrumb-path {
    font-size: 12px;
    gap: 4px;
  }
}

@media (max-width: 480px) {
  .navigation-tree {
    padding: 10px 12px;
    margin-bottom: 12px;
  }

  .title-icon {
    font-size: 20px;
  }

  .main-title {
    font-size: 18px;
  }

  .action-icon {
    width: 32px;
    height: 32px;
  }

  .action-icon ion-icon {
    font-size: 14px;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .navigation-tree {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}
</style>
