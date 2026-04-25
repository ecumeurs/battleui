<script setup>
import { UnrealBloom, EffectComposer } from '@tresjs/post-processing';

const props = defineProps({
    enabled: { type: Boolean, default: false }
});
</script>

<template>
    <EffectComposer v-if="enabled">
        <UnrealBloom
            :intensity="1.4"
            :threshold="0.35"
            :radius="0.5"
        />
    </EffectComposer>
</template>
