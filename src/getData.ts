import axios from "axios";

export async function getUsers() {
  if (navigator.onLine) {
    try {
      const users = await axios.get("v2/users");
      return users.data;
    } catch (error) {
      console.error(error);
    }
  }
}
