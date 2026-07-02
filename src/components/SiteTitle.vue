<template>
  <div class="navigation-tree" :style="'--background: ' + bg_color">
    <div class="tree-container">
      <!-- Breadcrumb Navigation -->
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-path">
          <template v-for="(crumb, idx) in crumbs" :key="idx">
            <li class="breadcrumb-segment">
              <router-link v-if="crumb.to" :to="crumb.to" class="breadcrumb-item breadcrumb-link"
                :class="{ 'home-item': idx === 0 }">
                <ion-icon v-if="crumb.icon" :name="crumb.icon" class="home-icon"></ion-icon>
                <span>{{ crumb.label }}</span>
              </router-link>
              <span v-else class="breadcrumb-item breadcrumb-current"
                :class="{ 'home-item': idx === 0 }" aria-current="page">
                <ion-icon v-if="crumb.icon" :name="crumb.icon" class="home-icon"></ion-icon>
                <span>{{ crumb.label }}</span>
              </span>
            </li>
            <ion-icon v-if="idx < crumbs.length - 1" name="chevron-forward-outline"
              class="separator-icon"></ion-icon>
          </template>
        </ol>
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
      default: "Page"
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
  computed: {
    // Build a real, navigable breadcrumb trail from the current route.
    // Every ancestor crumb links to its actual route (validated against the
    // router so we never link to a dead path); the final crumb is the page.
    crumbs() {
      const segs = this.$route.path
        .replace(/\/+$/, "")
        .split("/")
        .filter(Boolean);

      const items = [{ label: "Home", icon: "home-outline", to: "/projects" }];

      // The projects overview is itself "Home" — show it as the current page.
      if (segs.length === 0 || (segs.length === 1 && segs[0] === "projects")) {
        items[0].to = null;
        return items;
      }

      let rest = segs;
      let base = "";

      // Project routes get a dedicated, clickable project crumb.
      if (segs[0] === "project" && segs[1]) {
        base = `/project/${segs[1]}`;
        items.push({ label: this.humanize(segs[1]), to: base });
        rest = segs.slice(2);
      }

      // Structural URL segments that only group their child (no page of their own).
      const FOLD = new Set(["manage", "new", "table"]);
      // Resource detail pages link their type crumb back to the list/manage page.
      const LISTS = {
        forms: "manage/forms",
        apis: "manage/apis",
        page: "manage/pages",
        pages: "manage/pages",
        codespaces: "manage/codespaces",
      };

      let acc = base;
      for (let j = 0; j < rest.length; j++) {
        const seg = rest[j];
        acc += "/" + seg;
        const isLast = j === rest.length - 1;
        if (FOLD.has(seg) && !isLast) continue;

        let to = this.isRealRoute(acc) ? acc : null;
        if (!isLast && !to && base && LISTS[seg]) {
          const listPath = `${base}/${LISTS[seg]}`;
          if (this.isRealRoute(listPath)) to = listPath;
        }
        items.push({ label: this.humanize(seg), to });
      }

      // The leaf is the current page: prefer a meaningful page-provided title
      // (falling back to the humanized URL segment), and never link it.
      const leaf = items[items.length - 1];
      if (this.title && this.title !== "Page") {
        leaf.label = this.title[0].toUpperCase() + this.title.slice(1);
      }
      leaf.to = null;

      return items;
    },
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
          .get(
            "v2/bookmarks/check?" +
            this.$qs.stringify({
              location: this.siteLocation,
            })
          )
          .then((response) => {
            this.isBookmark = response.data;
          });
      }
    );
    this.$axios
      .get(
        "v2/bookmarks/check?" +
        this.$qs.stringify({
          location: this.siteLocation,
        })
      )
      .then((response) => {
        this.isBookmark = response.data;
      });

    if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
      this.bg_color = this.bg || "#121212";
    } else {
      this.bg_color = "#f8fafc";
    }
  },
  methods: {
    // "very-cool_project" -> "Very Cool Project"
    humanize(seg) {
      const s = decodeURIComponent(seg).replace(/[-_]+/g, " ").trim();
      return s.replace(/\b\w/g, (c) => c.toUpperCase());
    },
    // True only when the path matches a concrete route (not the catch-all).
    isRealRoute(path) {
      try {
        const resolved = this.$router.resolve(path);
        return (
          resolved.matched.length > 0 &&
          !resolved.matched.some((m) => /:url|\(\.\*\)/.test(m.path))
        );
      } catch (e) {
        return false;
      }
    },
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
          .delete(
            "v2/bookmarks?location=" +
            this.siteLocation
          )
          .then(() => {
            this.$emit("updateSidebar");
          });
      } else {
        this.isBookmark = true;
        this.$axios
          .post(
            "v2/bookmarks?icon=" +
            this.icon +
            "&title=" +
            this.title +
            "&location=" +
            this.siteLocation
          )
          .then(() => {
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
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;

  background: var(--background);
  padding: 16px 0px 0px 0px;
  /* margin-bottom: 24px;*/
}

.tree-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
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
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
}

.breadcrumb-segment {
  display: flex;
  align-items: center;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  padding: 4px 8px;
  border-radius: var(--radius);
  text-decoration: none;
}

/* First crumb (Home) sits flush with the page title's left edge. */
.breadcrumb-item.home-item {
  padding-left: 0;
}

.breadcrumb-link {
  cursor: pointer;
}

.breadcrumb-link:hover {
  color: var(--primary-color);
  background: rgba(249, 115, 22, 0.08);
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
  background: rgba(249, 115, 22, 0.08);
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
    padding: 12px 0px 0px 0px;
    /* margin-bottom: 16px;*/
  }

  .tree-container {
    padding: 0 16px;
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
    padding: 10px 0px 0px 0px;
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
    --background: #121212;
    --surface: #1e1e1e;
    --border: #2a2a2a;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --text-muted: #777777;
  }
}
</style>
