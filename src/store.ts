import { createStore } from 'vuex';

interface UserData {
  profileImg: string;
  firstName: string;
  lastName: string;
  email: string;
  accountStatus: string;
  [key: string]: string; // Add this index signature
}

interface State {
  user: UserData;
  // ... other properties
}

const state: State = {
  user: {
    profileImg: "",
    firstName: "",
    lastName: "",
    email: "",
    accountStatus: "",
  },
  // ... other properties
};

const mutations = {
  updateUser(state: State, payload: { valueName: string; newValue: string }) {
    state.user[payload.valueName] = payload.newValue;
  },
  // ... other mutations
};

const store = createStore({
  state,
  mutations,
});

export default store;
