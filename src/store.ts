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

interface ApiData {
  id?: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  type: string;
  base_url: string;
  auth_type: string;
  status: string;
  rate_limit: number;
  keys?: any[];
  endpoints?: any[];
}

interface State {
  user: UserData;
  apis: ApiData[];
  currentProject: string;
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
  apis: [],
  currentProject: "",
};

const mutations = {
  updateUser(state: State, payload: { valueName: string; newValue: string }) {
    state.user[payload.valueName] = payload.newValue;
  },
  setApis(state: State, apis: ApiData[]) {
    state.apis = apis;
  },
  addApi(state: State, api: ApiData) {
    state.apis.push(api);
  },
  updateApi(state: State, payload: { id: number; api: ApiData }) {
    const index = state.apis.findIndex(api => api.id === payload.id);
    if (index !== -1) {
      state.apis[index] = payload.api;
    }
  },
  removeApi(state: State, apiId: number) {
    state.apis = state.apis.filter(api => api.id !== apiId);
  },
  setCurrentProject(state: State, project: string) {
    state.currentProject = project;
  },
};

const actions = {
  async loadApis({ commit }, projectName: string) {
    try {
      const token = localStorage.getItem('controlCenter_auth_token');
      const response = await fetch('/backend/apis.php', {
        method: 'POST',
        headers: {
          'Authorization': token || '',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          getApis: 'true',
          project: projectName
        })
      });
      
      const data = await response.json();
      if (data && !data.error) {
        commit('setApis', data);
      }
    } catch (error) {
      console.error('Error loading APIs:', error);
    }
  },
  
  async createApi({ commit }, payload: { project: string; api: ApiData }) {
    try {
      const token = localStorage.getItem('controlCenter_auth_token');
      const response = await fetch('/backend/apis.php', {
        method: 'POST',
        headers: {
          'Authorization': token || '',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          addApi: 'true',
          project: payload.project,
          name: payload.api.name,
          slug: payload.api.slug,
          description: payload.api.description || '',
          icon: payload.api.icon,
          type: payload.api.type,
          base_url: payload.api.base_url || '',
          auth_type: payload.api.auth_type
        })
      });
      
      const data = await response.json();
      if (data && data.success) {
        commit('addApi', payload.api);
        return { success: true };
      }
      return { error: data.error || 'Failed to create API' };
    } catch (error) {
      console.error('Error creating API:', error);
      return { error: 'Network error' };
    }
  },
  
  async deleteApi({ commit }, apiId: number) {
    try {
      const token = localStorage.getItem('controlCenter_auth_token');
      const response = await fetch('/backend/apis.php', {
        method: 'POST',
        headers: {
          'Authorization': token || '',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          deleteApi: 'true',
          apiId: apiId.toString()
        })
      });
      
      const data = await response.json();
      if (data && data.success) {
        commit('removeApi', apiId);
        return { success: true };
      }
      return { error: data.error || 'Failed to delete API' };
    } catch (error) {
      console.error('Error deleting API:', error);
      return { error: 'Network error' };
    }
  }
};

const getters = {
  getApiBySlug: (state: State) => (slug: string) => {
    return state.apis.find(api => api.slug === slug);
  },
  getActiveApis: (state: State) => {
    return state.apis.filter(api => api.status === 'active');
  },
  getApisByType: (state: State) => (type: string) => {
    return state.apis.filter(api => api.type === type);
  }
};

const store = createStore({
  state,
  mutations,
  actions,
  getters,
});

export default store;