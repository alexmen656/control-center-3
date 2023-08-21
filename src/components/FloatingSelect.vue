<template>
  <ion-select
    fill="outline"
    :aria-label="select.label"
    v-model="modelValue"
    @ionChange="updateContent"
    interface="popover"
    :placeholder="select.label"
    :value="defaultVal"
  >
    <ion-select-option
      v-for="option in select.options"
      :key="option.value"
      :value="option.value"
    >
      {{ option.label }}
    </ion-select-option>
  </ion-select>
</template>

<script setup>
import { ref, defineProps, defineEmits } from "vue";
import { IonSelect, IonSelectOption } from "@ionic/vue";

const props = defineProps({
  select: {
    type: Object,
    required: true,
  },
  defaultVal: {
    type: String,
    required: false,
  },
});
//console.log(props.select);
const emit = defineEmits(["update:modelValue"]);

const modelValue = ref(props.select.value);

const updateContent = (event) => {
  emit("update:modelValue", event.detail.value);
};
</script>
