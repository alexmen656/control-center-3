import { ref } from 'vue';
import { defineStore } from 'pinia';

const fetchedMedia = ref(null);
const errorImages = ref(null);
const errorsImages = ref(null);
const isLoadingImages = ref(false);
const isErrorImages = ref(false);
const isSuccessImages = ref(false);

const handleGetImages = async (url, options) => {
  isLoadingImages.value = true;
  isErrorImages.value = false;
  isSuccessImages.value = false;
  errorImages.value = null;
  errorsImages.value = null;

  try {
    const response = await fetch(url, options);
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || `API error: ${response.status}`);
    }
    const data = await response.json();
    fetchedMedia.value = data;
    isSuccessImages.value = true;
  } catch (err) {
    errorImages.value = err.message || err;
    isErrorImages.value = true;
  } finally {
    isLoadingImages.value = false;
  }
};

export const useUnsplashStore = defineStore('unsplash', {
  state: () => ({
    unsplashImages: null,
    searchTerm: '',
    currentPageNumber: 1,
    orientationValue: null,
  }),
  getters: {
    getUnsplashImages: (state) => {
      return state.unsplashImages;
    },
    getSearchTerm: (state) => state.searchTerm,
    getCurrentPageNumber: (state) => state.currentPageNumber,
    getOrientationValue: (state) => state.orientationValue,
  },
  actions: {
    setUnsplashImages(payload) {
      this.unsplashImages = payload;
    },
    setSearchTerm(payload) {
      this.searchTerm = payload;
    },
    setCurrentPageNumber(payload) {
      this.currentPageNumber = payload;
    },
    setOrientationValue(payload) {
      this.orientationValue = payload;
    },
    async setLoadUnsplashImages(payload) {
      this.setUnsplashImages({
        fetchedMedia: null,
        isError: null,
        error: null,
        errors: null,
        isLoading: true,
        isSuccess: null,
      });

      let orientationType = payload.orientation
        ? `&orientation=${payload.orientation}`
        : '';

      const unsplashKey = import.meta.env.VITE_UNSPLASH_KEY;

      try {
        await handleGetImages(
          `https://api.unsplash.com/search/photos?page=${payload.currentPage}&per_page=24&query=${payload.searchTerm || 'a'}${orientationType}`,
          {
            headers: {
              'Accept-Version': 'v1',
              Authorization: unsplashKey,
            },
          },
          { additionalCallTime: 500 }
        );
      } catch (err) {
        console.log(`Error: ${err}`);
      }

      this.setUnsplashImages({
        fetchedMedia: fetchedMedia.value,
        isError: isErrorImages.value,
        error: errorImages.value,
        errors: errorsImages.value,
        isLoading: isLoadingImages.value,
        isSuccess: isSuccessImages.value,
      });
    },
  },
});
