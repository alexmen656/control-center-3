import axios from 'axios';

export async function getUserData() {
  try {
    const response = await axios.post("https://alex.polan.sk/control-center/user.php", { hello: "hello" });
    const data = response.data;
    return data;
  } catch (error) {
    console.error(error);
    return null;
  }
}
