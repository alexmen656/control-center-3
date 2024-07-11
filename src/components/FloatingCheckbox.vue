<template>
  <ion-checkbox
    justify="space-between"
    :value="modelValue"
    @ionChange="updateContent"
    >{{label}}</ion-checkbox
  >
</template>

<script setup>
//const props = 
defineProps({
  modelValue: String,
  label: String
});

const emit = defineEmits(["update:modelValue"]);

const updateContent = (event) => {
  emit("update:modelValue", event.detail.checked);
};
</script>
