<!-- @spec-link [[ui_tactical_action_report]] -->
<script setup>
defineProps({
    action: {
        type: Object,
        default: null
    },
    show: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <Transition name="fade">
        <div v-if="show && action" class="action-report">
            <div class="action-report__type">{{ action.type.toUpperCase() }}</div>
            <div class="action-report__details">
                <template v-if="action.type === 'attack'">
                    <span class="text-danger">DAMAGED</span> Target for <span class="text-warning">{{ action.damage }}</span> HP
                    <div class="action-report__hp-change">
                        HP: {{ action.prev_hp }} → {{ action.new_hp }}
                    </div>
                </template>
                <template v-else-if="action.type === 'move'">
                    REPOSITIONED across <span class="text-info">{{ action.path?.length || 0 }}</span> tiles
                </template>
                <template v-else-if="action.type === 'pass'">
                    STANCE RESET (Turn Passed)
                </template>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* ─── ACTION REPORT ─── */
.action-report {
    position: absolute;
    top: 15%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 500;
    pointer-events: none;
    text-align: center;
    background: rgba(10, 10, 11, 0.85);
    border-top: 2px solid #00f2ff;
    border-bottom: 2px solid #00f2ff;
    padding: 20px 60px;
    backdrop-filter: blur(10px);
    box-shadow: 0 0 30px rgba(0, 242, 255, 0.2);
}

.action-report__type {
    font-family: 'Orbitron', sans-serif;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 0.4em;
    color: #00f2ff;
    margin-bottom: 8px;
    text-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
}

.action-report__details {
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    color: #e0e0e0;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.action-report__hp-change {
    font-size: 11px;
    margin-top: 4px;
    opacity: 0.7;
}

.text-danger { color: #ff2020; }
.text-warning { color: #ff9f43; }
.text-info { color: #00a8ff; }

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translate(-50%, -20px);
}
</style>
