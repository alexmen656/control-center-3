import axios from "axios";

if (localStorage.getItem("token")) {
  axios.defaults.headers.common["Authorization"] =
    localStorage.getItem("token");
}
axios.defaults.timeout = 120000;
axios.defaults.baseURL = "https://alex.polan.sk/control-center/";
