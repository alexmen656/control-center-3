import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
  { path: "environment-variables", component: () => import("./components/Modul1View.vue") },
  { path: "env", component: () => import("./components/Modul1View.vue") },
  { path: "vercel", component: () => import("./components/Modul1View.vue") },
];

export default routes;
