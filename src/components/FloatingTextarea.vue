<template>
  <ion-textarea
    v-model="modelValue"
    @ionInput="updateContent"
    :value="defaultVal"
    :label="label"
    label-placement="floating"
    fill="outline"
    :placeholder="placeholder"
    :disabled="disabled"
    :rows="rows || 3"
    :maxlength="maxlength || 1000"
  />
</template>

<script setup>
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
  disabled: {
    type: Boolean,
    required: false,
  },
  rows: {
    type: Number,
    required: false,
    default: 3
  },
  maxlength: {
    type: Number,
    required: false,
    default: 1000
  }
});

const emit = defineEmits(["update:modelValue", "change"]);

const updateContent = (event) => {
  emit("update:modelValue", event.target.value);
  emit("change", event);
};
</script>
