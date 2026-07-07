import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
  {
    path: "codespace/:codespace",
    component: () => import("./components/CodeSpace.vue"),
  },
];

export default routes;
