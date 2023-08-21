<template>
  <ion-input
    v-model="modelValue"
    @ionInput="updateContent"
    :value="defaultVal"
    :label="label"
    label-placement="floating"
    fill="outline"
    :placeholder="placeholder"
    :type="type"
  />
</template>

<script setup>
import { IonInput } from "@ionic/vue";

const props = defineProps({
  defaultVal: String,
  label: String,
  placeholder: String,
  type: String,
});

const emit = defineEmits(["update:modelValue"]);

const updateContent = (event) => {
  emit("update:modelValue", event.target.value);
};
</script>
