<script setup>
import { UnrealBloom, EffectComposer } from '@tresjs/post-processing';

const props = defineProps({
    enabled: { type: Boolean, default: false }
});
</script>

<template>
    <EffectComposer v-if="enabled">
        <UnrealBloom
            :intensity="1.5"
            :threshold="0.4"
            :radius="0.3"
        />
    </EffectComposer>
</template>
