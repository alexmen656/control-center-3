<template>

      <ion-list id="inbox-list">
        <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
          <ion-item
            button
            @click="this.selectedIndex = i"
            lines="none"
            detail="false"
            :router-link="'/' + p.name[0].toLowerCase() + p.name.substring(1)"
            class="hydrated menu-item"
            :class="{ selected: this.selectedIndex === i }"
          >
            <ion-icon slot="start" :name="p.icon"></ion-icon>
            <ion-label
              >{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label
            >
          </ion-item>
        </ion-menu-toggle>
      </ion-list>
      <ion-note class="projects-headline"
        ><h4>Projects</h4>
        <div>
          <router-link to="/manage/projects/"
            ><ion-icon
              style="color: var(--ion-color-medium-shade)"
              name="ellipsis-horizontal-circle-outline" /></router-link
          ><router-link to="/info/projects/"
            ><ion-icon
              style="color: var(--ion-color-medium-shade)"
              name="information-circle-outline"
            ></ion-icon></router-link
          ><router-link to="/new/project/"
            ><ion-icon
              style="color: var(--ion-color-medium-shade)"
              name="add-circle-outline"
            ></ion-icon
          ></router-link></div
      ></ion-note>
      <ion-list>
        <ion-menu-toggle auto-hide="false" v-for="(p, i) in projects" :key="i">
          <ion-item
            button
            lines="none"
            detail="false"
            @click="goToProject(p.name)"
            class="hydrated menu-item"
            ><!-- @click="this.selectedIndex = i" //  :class="{ selected: this.selectedIndex === i }"-->
            <ion-icon slot="start" :name="p.icon"></ion-icon>
            <ion-label
              >{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label
            >
          </ion-item>
        </ion-menu-toggle>
      </ion-list>

      <ion-note class="projects-headline"
        ><h4>Bookmarks</h4>
        <div>
          <router-link to="/manage/bookmarks/"
            ><ion-icon
              style="color: var(--ion-color-medium-shade)"
              name="ellipsis-horizontal-circle-outline" /></router-link
          ><router-link to="/info/bookmarks/"
            ><ion-icon
              style="color: var(--ion-color-medium-shade)"
              name="information-circle-outline"
            ></ion-icon></router-link
          ><!--<router-link to="/new/bookmark/"><ion-icon style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>-->
        </div></ion-note
      >
      <ion-list>
        <ion-menu-toggle auto-hide="false" v-for="(p, i) in bookmarks" :key="i">
          <ion-item
            button
            lines="none"
            detail="false"
            @click="goToBookmark(p.location)"
            class="hydrated menu-item"
            ><!-- @click="this.selectedIndex = i" //  :class="{ selected: this.selectedIndex === i }"-->
            <ion-icon slot="start" :name="p.icon"></ion-icon>
            <ion-label
              >{{ p.title[0].toUpperCase()
              }}{{ p.title.substring(1) }}</ion-label
            >
          </ion-item>
        </ion-menu-toggle>
      </ion-list>
   
</template>

<script>
import { defineComponent, ref } from "vue";
//import axios from "axios";

export default defineComponent({
  name: "SideBar",
  props: {
    tools: Array,
    bookmarks: Array,
    projects: Array,
  },
  methods: {
    goToProject(name) {
      /* window.location.href =
        "/project/" +
        (name[0].toLowerCase() + name.substring(1))
          .replaceAll(` `, `-`)
          .replaceAll(`'`, ``)
          .toLowerCase();*/

      this.$router.push(
        "/project/" +
          (name[0].toLowerCase() + name.substring(1))
            .replaceAll(` `, `-`)
            .replaceAll(`'`, ``)
            .toLowerCase()
      );
    },
    goToBookmark(link) {
      // window.location.href = link;
      this.$router.push(link);
    },
  },

  setup() {
    const selectedIndex = ref(0);
    return {
      selectedIndex,
    };
  },
});
</script>

<style>
ion-menu.md ion-list[data-v-7ba5bd90] {
  padding-top: 56px;
}
.btn-red {
  --background: var(--ion-color-primary) !important;
}
.ion-menu {
  width: 300px;
}

ion-menu ion-content {
  --background: var(--ion-item-background, var(--ion-background-color, #fff));
}

/*ion-menu.md ion-content {
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 20px;
  --padding-bottom: 20px;
}*/

ion-menu.md ion-list {
  padding: 20px 0;
}

ion-list {
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
  margin-bottom: 1rem;
}

ion-menu.md ion-note {
  margin-bottom: 30px;
}

ion-menu.md ion-list-header,
ion-menu.md ion-note {
  padding-left: 10px;
}

ion-menu.md ion-list#inbox-list {
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
}

ion-menu.md ion-list#inbox-list ion-list-header {
  font-size: 22px;
  font-weight: 600;

  min-height: 20px;
}

ion-menu.md ion-list#labels-list ion-list-header {
  font-size: 16px;

  margin-bottom: 18px;

  color: #757575;

  min-height: 26px;
}

ion-menu.md ion-item {
  --padding-start: 10px;
  --padding-end: 10px;
  border-radius: 4px;
}

ion-menu.md ion-item.selected {
  --background: rgba(var(--ion-color-primary-rgb), 0.14);
}

ion-menu.md ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.md ion-item ion-icon {
  color: #616e7e;
}

ion-menu.md ion-item ion-label {
  font-weight: 500;
}

ion-menu.ios ion-content {
  --padding-bottom: 20px;
}

ion-menu.ios ion-list {
  padding: 20px 0 0 0;
}

ion-menu.ios ion-note {
  line-height: 24px;
  margin-bottom: 20px;
}

ion-menu.ios ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 50px;
}

ion-menu.ios ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.ios ion-item ion-icon {
  font-size: 24px;
  color: #73849a;
}

ion-menu.ios ion-list#labels-list ion-list-header {
  margin-bottom: 8px;
}

ion-menu.ios ion-list-header,
ion-menu.ios ion-note {
  padding-left: 16px;
  padding-right: 16px;
}

ion-menu.ios ion-note {
  margin-bottom: 8px;
}

ion-note {
  display: inline-block;
  font-size: 16px;

  color: var(--ion-color-medium-shade);
}

ion-item.selected {
  --color: var(--ion-color-primary);
}

a {
  text-decoration: none;
  color: var(--ion-color-primary);
}

/*ion-header,
ion-toolbar,*/
.header {
  --background: #000;
}

.mobile-only {
  display: none;
}

.desktop-only {
  display: block;
}

@media only screen and (max-width: 600px) {
  .only-web {
    display: none;
  }

  .mobile-only {
    display: block;
  }

  .desktop-only {
    display: none;
  }
}

router-link,
a {
  color: var(--ion-color-primary);
}

ion-menu-button {
  color: var(--ion-color-primary) !important;
}

a {
  color: var(--ion-color-primary) !important;
}

.link-container {
  display: flex;
  justify-content: center;
}
ion-footer ion-toolbar {
  color: #000;
}

/*ion-title {
  color: white;
}*/
.link {
  text-decoration: none;
}

/*ion-menu.md ion-item.selected {
  --background: rgba(255, 0, 0, 0.14) !important;
}*/

ion-item.selected {
  --color: var(--ion-color-primary) !important;
}

ion-menu ion-item.selected ion-icon {
  color: var(--ion-color-primary) !important;
}

ion-item:focus {
  --background: var(--ion-color-primary);
}

.list-md.articles {
  background: var(--ion-background-color);
}

.projects-headline {
  display: flex;
  margin: 0 !important;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
}

.projects-headline > h4 {
  margin: 0;
  margin-top: 0.35rem !important;
  padding: 0;
}

.menu-item {
  cursor: default;
}
</style>
