import { createStore } from "vuex";

interface UserData {
  profileImg: string;
  firstName: string;
  lastName: string;
  email: string;
  accountStatus: string;
  login_with_google: string;
  [key: string]: string;
}

interface State {
  user: UserData;
}

const state: State = {
  user: {
    profileImg: "",
    firstName: "",
    lastName: "",
    email: "",
    accountStatus: "",
    login_with_google: "false",
  },
};

const mutations = {
  updateUser(state: State, payload: { valueName: string; newValue: string }) {
    state.user[payload.valueName] = payload.newValue;
  },
};

const store = createStore({
  state,
  mutations,
});

export default store;