import axios from "axios";
import { ref } from "vue";
import qs from "qs";
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
      const response = await axios.post<UserData>(
        "https://alex.polan.sk/control-center/tools.php", qs.stringify({getToolConfig: "getToolConfig", tool: tool, project: project})
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