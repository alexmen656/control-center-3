<template>
  <ion-page>
    <!--  <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button color="primary"></ion-menu-button>
        </ion-buttons>
        <ion-title>Settings Menu</ion-title>
      </ion-toolbar>
    </ion-header>
-->

    <ion-content :fullscreen="true">
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <h2>App Theme</h2>
            <ion-accordion-group>
              <ion-accordion value="Dark Themes">
                <ion-item slot="header">
                  <ion-label>Theme Selection</ion-label>
                </ion-item>
                <ion-list slot="content">
                  <ion-radio-group v-model="theme">
                    <ion-item style="--background: #d50000">
                      <ion-label>Red</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkRed"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #c51162; opacity: 1">
                      <ion-label>Pink</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkPink"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #aa00ff; opacity: 1">
                      <ion-label>Purple</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkPurple"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #6200ea; opacity: 1">
                      <ion-label>Deep Purple</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkDarkPurple"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #304ffe; opacity: 1">
                      <ion-label>Indigo</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkIndigo"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #2962ff">
                      <ion-label>Blue</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkBlue"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #0091ea; opacity: 1">
                      <ion-label>Light Blue</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkLightBlue"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #00b8d4; color: black; opacity: 1"
                    >
                      <ion-label>Cyan</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkCyan"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #00bfa5; color: black; opacity: 1"
                    >
                      <ion-label>Teal</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkTeal"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #00c853; opacity: 1">
                      <ion-label>Green</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkGreen"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #64dd17; color: black">
                      <ion-label>Light Green</ion-label>
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkLightGreen"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #aeea00; color: black; opacity: 1"
                    >
                      <ion-label>Lime</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkLime"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #ffd600; color: black; opacity: 1"
                    >
                      <ion-label>Yellow</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkYellow"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #ffab00; color: black; opacity: 1"
                    >
                      <ion-label>Amber</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkAmber"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item
                      style="--background: #ff6d00; color: black; opacity: 1"
                    >
                      <ion-label>Orange</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: black; --color: black"
                        value="darkOrange"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #dd2c00; opacity: 1">
                      <ion-label>Deep Orange</ion-label>
                      <!--<ion-label slot="end">(Pro Color)</ion-label>-->
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="darkDarkOrange"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                  </ion-radio-group>
                </ion-list> </ion-accordion
            ></ion-accordion-group>

        <!---    <ion-accordion-group>
              <ion-accordion value="Dark Themes">
                <ion-item slot="header">
                  <ion-label>Mode</ion-label>
                </ion-item>
                <ion-list slot="content">
                  <ion-radio-group v-model="mode" @ionChange="toggleChange(this.mode)">
                    <ion-item style="--background: #000">
                      <ion-label>Dark</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="dark"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: gray; opacity: 1">
                      <ion-label>Light</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="light"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                    <ion-item style="--background: #c51162; opacity: 1">
                      <ion-label>System</ion-label>
                      <ion-radio
                        style="--color-checked: white; --color: white"
                        value="system"
                        slot="end"
                      ></ion-radio>
                    </ion-item>
                  </ion-radio-group>
                </ion-list> </ion-accordion
            ></ion-accordion-group>-->

            <ion-button @click="$router.push({ path: '/' })">
              <ion-icon :icon="closeSharp"></ion-icon>
              <ion-label>Cancel</ion-label></ion-button
            >
            <ion-button @click="setTheme()">
              <ion-icon :icon="saveSharp"></ion-icon>
              <ion-label>Save</ion-label></ion-button
            >
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent} from "vue";//ref 
//import router from "@/router";
import { store } from "@/theme/theme";
import { closeSharp, saveSharp } from "ionicons/icons";
//import type { ToggleCustomEvent } from '@ionic/vue';

export default defineComponent({
  data() {
    return {
      theme: localStorage.getItem("themeSet"),
      mode: "dark",
    };
  },
  name: "FolderPage",
  methods: {
    setTheme() {
      // @ts -ignore: Object is possibly 'null'.
      localStorage.setItem("themeSet", this.theme);
      store.setItem();
   //   alert(this.mode);
      // router.replace({ path: "/" });
    },
  },

 setup() {
    /* const paletteToggle = ref(false);

    // Use matchMedia to check the user preference
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)");

    // Add or remove the "ion-palette-dark" class on the html element
    const toggleDarkPalette = (shouldAdd) => {
      if(shouldAdd == "dark") {
      document.documentElement.classList.toggle("ion-palette-dark", true);
      }
    };

    // Check/uncheck the toggle and update the palette based on isDark
    const initializeDarkPalette = (isDark) => {
      paletteToggle.value = isDark;
      toggleDarkPalette(isDark);
    };

    // Initialize the dark palette based on the initial
    // value of the prefers-color-scheme media query
    initializeDarkPalette(prefersDark.matches);

    // Listen for changes to the prefers-color-scheme media query
    prefersDark.addEventListener("change", (mediaQuery) =>
      initializeDarkPalette(mediaQuery.matches)
    );

    // Listen for the toggle check/uncheck to toggle the dark palette
    const toggleChange = (mode) => {
      //alert(mode);
      toggleDarkPalette(mode);
    };
*/
    return {
      closeSharp,
      saveSharp,
  //    initializeDarkPalette,
   //   toggleChange,
   //   toggleDarkPalette,
    //  paletteToggle,
    };
  },
});
</script>

<style scoped>
#container {
  text-align: center;
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
}

#container strong {
  font-size: 20px;
  line-height: 26px;
}

#container p {
  font-size: 16px;
  line-height: 22px;
  color: #8c8c8c;
  margin: 0;
}

#container a {
  text-decoration: none;
}
</style>
