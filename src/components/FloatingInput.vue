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
    :disabled="disabled"
  />
</template>

<script setup>
//const props = 
defineProps({
  defaultVal: {
    type: String,
    required: true,
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
