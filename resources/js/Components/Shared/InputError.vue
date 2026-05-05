<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-ui-md text-red-600">
            {{ message }}
        </p>
    </div>
</template>
