import axios from "axios";
import { ref } from "vue";
import store from "@/store";

interface UserData {
  profileImg: string;
  firstName: string;
  lastName: string;
  email: string;
  accountStatus: string;
}

const data = ref<UserData>({
  profileImg: "",
  firstName: "",
  lastName: "",
  email: "",
  accountStatus: "",
});

export async function loadUserData() {
  if (navigator.onLine) {
    try {
      const response = await axios.post<UserData>(
        "https://alex.polan.sk/control-center/user.php"
      );
      data.value = response.data;
      store.commit("updateUser", {
        valueName: "firstName",
        newValue: data.value.firstName,
      });
      store.commit("updateUser", {
        valueName: "lastName",
        newValue: data.value.lastName,
      });
      store.commit("updateUser", {
        valueName: "email",
        newValue: data.value.email,
      });
      store.commit("updateUser", {
        valueName: "accountStatus",
        newValue: data.value.accountStatus,
      });
      store.commit("updateUser", {
        valueName: "profileImg",
        newValue: data.value.profileImg,
      });
    } catch (error) {
      console.error(error);
    }
  }
}

export function getUserData(): UserData | null {
  try {
    return {
      profileImg: store.state.user.profileImg,
      firstName: store.state.user.firstName,
      lastName: store.state.user.lastName,
      email: store.state.user.email,
      accountStatus: store.state.user.accountStatus,
    };
  } catch (error) {
    console.error(error);
    return null;
  }
}
