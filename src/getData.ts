import axios from "axios";
//import { ref } from "vue";
//import store from "@/store";
import qs from "qs";

/*interface UserData {
  profileImg: string;
  userID: string;
  firstName: string;
  lastName: string;
  email: string;
  accountStatus: string;
  login_with_google: string;
}
*/
//const data = ref([]);


export async function getUsers() {
    console.log("dfghdfgjf");
  if (navigator.onLine) {
    try {
      const users = await axios.post(
        "https://alex.polan.sk/control-center/users.php",
        qs.stringify({ getAllUsers: "getAllUsers"})
      );
      return users.data;
    } catch (error) {
      console.error(error);
    }
  }
}

/*export function getUsers(): UserData | null {
  try {
    return {
      profileImg: store.state.user.profileImg,
      userID: store.state.user.userID,
      firstName: store.state.user.firstName,
      lastName: store.state.user.lastName,
      email: store.state.user.email,
      accountStatus: store.state.user.accountStatus,
      login_with_google: store.state.user.login_with_google,
    };
  } catch (error) {
    console.error(error);
    return null;
  }
}*/
