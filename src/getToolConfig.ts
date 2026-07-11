import axios from "axios";
import { ref } from "vue";
//import store from "@/store";

interface UserData {
  config: object;
}

const data = ref<UserData>({
 config: {}
});

export async function getConfig(tool: string, project: string) {
  if (navigator.onLine) {
    try {
      const response = await axios.get<UserData>(
        "v2/tools/config", { params: { tool: tool, project: project } }
      );
      data.value = response.data;
    /*  store.commit("updateUser", {
        valueName: "firstName",
        newValue: data.value.firstName,
      });*/
      console.log(data.value);

      return {data: data.value};
    } catch (error) {
      console.error(error);
    }
  }
}