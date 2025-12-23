<template>
    <div
        class="w-full inset-x-0 h-[100vh] z-10 bg-white overflow-x-scroll"
    ><!-- lg:pt-2 pt-2-->
        <div id="page-builder-editor">
            <div class="" v-html="htmlPage"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";

// router
// const router = useRouter();
// preview content
const htmlPage = ref("");
// get preview item from local storage
htmlPage.value = localStorage.getItem("preview");
// parse
htmlPage.value = JSON.parse(htmlPage.value);
// join
htmlPage.value = htmlPage.value.join("");

// Funktion zum Wiederherstellen der Original-Links in der Preview
const restoreOriginalLinks = () => {
  setTimeout(() => {
    const pageBuilder = document.getElementById('page-builder-editor');
    if (!pageBuilder) return;
    
    // Alle Links in der Preview suchen
    const links = pageBuilder.querySelectorAll('a');
    
    // Durch alle Links iterieren und Original-Attribute wiederherstellen
    links.forEach(link => {
      // Original-URL wiederherstellen
      if (link.getAttribute('data-original-href')) {
        link.href = link.getAttribute('data-original-href');
      }
      
      // Original-Target wiederherstellen
      if (link.getAttribute('data-original-target')) {
        link.target = link.getAttribute('data-original-target');
      }
    });
  }, 100); // Kurze Verzögerung für das Rendern des DOM
};

onMounted(() => {
  restoreOriginalLinks();
});
</script>
