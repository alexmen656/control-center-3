<template>
  <ion-input
    :model-value="modelValue || defaultVal"
    @ionInput="updateContent"
    :label="label"
    label-placement="floating"
    fill="outline"
    :placeholder="placeholder"
    :type="type"
    :disabled="disabled"
  />
</template>

<script setup>
//const props = 
defineProps({
  modelValue: {
    type: String,
    required: false,
    default: "",
  },
  defaultVal: {
    type: String,
    required: false,
    default: "",
  },
  label: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
  disabled: {
    type: Boolean,
    required: false,
  },
});

const emit = defineEmits(["update:modelValue"]);

const updateContent = (event) => {
  emit("update:modelValue", event.target.value);
  emit("change", event);
};
</script>
