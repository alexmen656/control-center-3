<script setup>
/**
 * DynamicContentBadge Component
 * 
 * Displays a dynamic content reference as a colored badge.
 * Green = Valid reference (content exists)
 * Red = Invalid reference (content not found)
 * 
 * Props:
 * - tableName: The content table name
 * - columnName: The column name
 * - index: The array index
 * - isValid: Whether the reference resolves to actual data
 * - inline: Whether to display inline (default: true)
 */

import { computed } from 'vue';

const props = defineProps({
    tableName: {
        type: String,
        required: true
    },
    columnName: {
        type: String,
        required: true
    },
    index: {
        type: [Number, String],
        required: true
    },
    isValid: {
        type: Boolean,
        default: false
    },
    inline: {
        type: Boolean,
        default: true
    }
});

const displayText = computed(() => {
    return `${props.tableName}.${props.columnName}[${props.index}]`;
});

const badgeClasses = computed(() => {
    const baseClasses = 'cc-dynamic-badge font-mono text-xs font-medium px-2 py-0.5 rounded select-none';
    const validityClasses = props.isValid
        ? 'bg-green-100 text-green-800 border border-green-300'
        : 'bg-red-100 text-red-800 border border-red-300';
    const displayClasses = props.inline ? 'inline-flex' : 'flex';

    return `${baseClasses} ${validityClasses} ${displayClasses} items-center`;
});
</script>

<template>
    <span :class="badgeClasses" :data-cc-dynamic="true" :data-cc-table="tableName" :data-cc-column="columnName"
        :data-cc-index="index" contenteditable="false">
        <span class="opacity-60 mr-0.5"></span>
        <span>{{ displayText }}</span>
        <span class="opacity-60 ml-0.5"></span>
    </span>
</template>

<style scoped>
.cc-dynamic-badge {
    line-height: 1.4;
    white-space: nowrap;
    cursor: default;
    margin: 0 2px;
    vertical-align: middle;
}
</style>
