import { reactive } from "vue";

export const store = reactive({
  theme: localStorage.getItem("themeSet"),
  
  setItem(): void {
    //alert(localStorage.getItem("themeSet"));
    this.theme = localStorage.getItem("themeSet");
   // localStorage.setItem('theme', this.theme);
  },
});