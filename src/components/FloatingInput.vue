<template>
  <ion-input
    :value="modelValue"
    @input="updateContent"
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
  modelValue: String,
  label: String,
  placeholder: String,
  type: String,
});

const emit = defineEmits(["update:modelValue"]); //'update:title',

const updateContent = (event) => {
  emit("update:modelValue", event.target.value);
};
</script>
