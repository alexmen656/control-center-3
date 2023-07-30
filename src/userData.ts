import axios from "axios";
import { ref } from "vue";
const data = ref({});

//localStorage.setItem("firstName", "Alex")
//localStorage.setItem("lastName", "Polan")
//localStorage.setItem("email", "alexx.polan1@gmail.com")

export async function getUserData() {
  try {
    if (navigator.onLine) {
      const response = await axios.post(
        "https://alex.polan.sk/control-center/user.php"
      );
      data.value = response.data;
    } else {
      const daten = {
        firstName: localStorage.getItem("firstName"),
        lastName: localStorage.getItem("lastName"),
        email: localStorage.getItem("email"),
        accountStatus: localStorage.getItem("email"),

      };
      data.value = daten;
    }

    return data.value;
  } catch (error) {
    console.error(error);
    return null;
  }
}
