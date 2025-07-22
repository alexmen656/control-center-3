<template>
  <ion-page class="ion-padding">
    <ion-content>
      <ion-header collapse="condense">
        <ion-toolbar color="primary">
          <ion-title>GitHub Repositories</ion-title>
        </ion-toolbar>
      </ion-header>
      <ion-grid>
        <ion-row>
          <ion-col size="12" v-if="loading" class="ion-text-center">
            <ion-spinner name="crescent"></ion-spinner>
          </ion-col>
          <ion-col size="12" v-if="error" class="ion-text-center">
            <ion-text color="danger">{{ error }}</ion-text>
          </ion-col>
        </ion-row>
        <ion-row>
          <ion-col v-for="repo in repos" :key="repo.id" size="12" size-md="6" size-lg="4">
            <ion-card>
              <ion-card-header>
                <ion-card-title>
                  <a :href="repo.html_url" target="_blank">{{ repo.name }}</a>
                </ion-card-title>
                <ion-card-subtitle>{{ repo.full_name }}</ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <p>{{ repo.description }}</p>
                <p v-if="repo.language"><strong>Language:</strong> {{ repo.language }}</p>
                <p><ion-icon name="star-outline"></ion-icon> {{ repo.stargazers_count }}
                  <ion-icon name="git-branch-outline" style="margin-left:1em;"></ion-icon> {{ repo.forks_count }}
                </p>
                <p v-if="repo.private"><ion-badge color="warning">Private</ion-badge></p>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>
<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { getUserData } from '@/userData';

export default {
  name: 'ModulView',
  setup() {
    const repos = ref([]);
    const loading = ref(true);
    const error = ref('');

    onMounted(async () => {
      loading.value = true;
      error.value = '';
      try {
        const user = getUserData();
        if (!user || !user.userID) {
          error.value = 'No user logged in.';
          loading.value = false;
          return;
        }
        const res = await axios.get(`github_repos.php?userID=${user.userID}`);
        if (Array.isArray(res.data)) {
          repos.value = res.data;
        } else if (res.data && res.data.length === undefined && res.data.error) {
          error.value = res.data.error;
        } else {
          error.value = 'Unexpected response.';
        }
      } catch (e) {
        error.value = 'Fehler beim Laden der Repositories.';
      } finally {
        loading.value = false;
      }
    });

    return { repos, loading, error };
  }
};
</script>
<style scoped>
ion-card {
  margin-bottom: 1.5em;
}
a {
  color: var(--ion-color-primary);
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}
</style>