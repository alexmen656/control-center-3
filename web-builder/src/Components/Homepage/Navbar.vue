<script setup>
const emit = defineEmits(['handleButton', 'logout']);
const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

// first button function
const handleButton = function () {
  emit('handleButton');
};

// logout function
const handleLogout = function() {
  emit('logout');
};
</script>

<template>
  <nav
    class="px-6 py-6 mx-auto flex items-center justify-between bg-gray-50 px-4 lg:h-[10vh] h-[16vh]"
    aria-label="Global"
  >
    <img
      class="h-6"
      src="/logo/logo.png"
      alt="Logo"
    />

    <div class="flex lg:gap-x-12 myPrimaryGap">
      <p
        @click="handleButton"
        class="text-lg font-medium text-myPrimaryDarkGrayColor flex gap-2 items-center cursor-pointer"
      >
        Start Builder
      </p>
      
      <!-- Benutzermenü -->
      <div class="relative ml-4 flex-shrink-0 flex items-center">
        <div class="flex items-center gap-3">
          <span class="text-gray-700 text-sm">
            {{ user?.username || 'Benutzer' }}
          </span>
          <button 
            @click="handleLogout"
            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            title="Abmelden"
          >
            <span class="material-symbols-outlined text-sm mr-1">logout</span>
            Logout
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>
