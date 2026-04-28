<!-- Skill roulette: 3 spinning reels for theater; backend rolls 1 real skill on STOP.
     The winning reel (random) reveals the actual skill; the other 2 are visual only.
     Forward-compatible: swap roll() for a 3-candidate API when ready.
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import { ref, computed, watch } from 'vue';
import ModalBox from '@/Components/ModalBox.vue';
import SkillRouletteReel from '@/Components/Skill/SkillRouletteReel.vue';
import SkillDetail from '@/Components/Skill/SkillDetail.vue';
import skillService from '@/services/skill';

const props = defineProps({
    show:        { type: Boolean, default: false },
    characterId: { type: String,  default: null },
});

const emit = defineEmits(['close', 'skill-acquired']);

// FSM: idle → spinning → revealing → revealed
const fsm     = ref('idle');  // 'idle' | 'spinning' | 'revealing' | 'revealed'
const error   = ref('');
const rolledSkill = ref(null);
const winnerReel  = ref(0);   // 0, 1, or 2 — which reel shows the real skill

// Template names for non-winner reels (visual)
const templateNames = ref([]);

const reel0 = ref(null);
const reel1 = ref(null);
const reel2 = ref(null);
const reels = computed(() => [reel0.value, reel1.value, reel2.value]);

async function loadTemplates() {
    try {
        const result = await skillService.listTemplates();
        templateNames.value = Array.isArray(result)
            ? result.map(t => t.name)
            : ['Fireball', 'Heal', 'Sprint', 'Lightning Strike', 'Shield Bash', 'Regen Aura'];
    } catch {
        templateNames.value = ['Fireball', 'Heal', 'Sprint', 'Lightning Strike', 'Shield Bash', 'Regen Aura'];
    }
}

watch(() => props.show, async (v) => {
    if (v) {
        fsm.value = 'idle';
        error.value = '';
        rolledSkill.value = null;
        winnerReel.value = Math.floor(Math.random() * 3);
        await loadTemplates();
    }
});

function spin() {
    if (fsm.value !== 'idle') return;
    fsm.value = 'spinning';
    const names = templateNames.value;
    reels.value.forEach(r => r?.start(names));
}

async function stop() {
    if (fsm.value !== 'spinning') return;
    fsm.value = 'revealing';
    error.value = '';

    let skill = null;
    try {
        skill = await skillService.roll(props.characterId);
        // skill is CharacterSkillResource: { id, instance_data, equipped, ... }
    } catch (e) {
        error.value = e?.message ?? 'Acquisition failed. Generator offline.';
        fsm.value = 'idle';
        reels.value.forEach(r => r?.reset());
        return;
    }

    rolledSkill.value = skill;
    const rolledName = skill.instance_data?.name ?? skill.name ?? 'Unknown';
    const decoys = templateNames.value.filter(n => n !== rolledName);

    // Stop reels one by one with a stagger
    reels.value.forEach((reel, i) => {
        setTimeout(() => {
            const target = i === winnerReel.value
                ? rolledName
                : decoys[i % decoys.length] || 'Unknown';
            reel?.stop(target);
            if (i === 2) {
                setTimeout(() => { fsm.value = 'revealed'; }, 400);
            }
        }, i * 500);
    });
}

function accept() {
    emit('skill-acquired', rolledSkill.value);
    emit('close');
}

function handleClose() {
    if (fsm.value === 'spinning') return; // prevent accidental close mid-spin
    emit('close');
}
</script>

<template>
    <ModalBox
        :show="show"
        title="Randomized Skill Acquisition"
        subtitle="Roulette protocol — one-time character event"
        max-width="3xl"
        :closeable="fsm !== 'spinning'"
        @close="handleClose"
    >
        <div class="space-y-8">
            <!-- Reel row -->
            <div class="flex items-center justify-center gap-6">
                <SkillRouletteReel
                    ref="reel0"
                    :names="templateNames"
                    :is-winner="winnerReel === 0 && (fsm === 'revealed')"
                />
                <SkillRouletteReel
                    ref="reel1"
                    :names="templateNames"
                    :is-winner="winnerReel === 1 && (fsm === 'revealed')"
                />
                <SkillRouletteReel
                    ref="reel2"
                    :names="templateNames"
                    :is-winner="winnerReel === 2 && (fsm === 'revealed')"
                />
            </div>

            <!-- Error -->
            <div v-if="error" class="px-4 py-3 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-[9px] text-upsilon-magenta uppercase text-center">
                ⚠ {{ error }}
            </div>

            <!-- CTA buttons -->
            <div class="flex justify-center gap-4">
                <button
                    v-if="fsm === 'idle'"
                    @click="spin"
                    class="px-8 py-3 font-scifi text-[11px] uppercase tracking-[0.3em] border border-upsilon-cyan text-upsilon-cyan hover:bg-upsilon-cyan hover:text-black transition-all duration-300"
                    style="box-shadow: 0 0 8px rgba(0, 242, 255, 0.3);"
                >
                    ◈ INITIATE SPIN
                </button>

                <button
                    v-if="fsm === 'spinning'"
                    @click="stop"
                    class="px-8 py-3 font-scifi text-[11px] uppercase tracking-[0.3em] border border-upsilon-magenta text-upsilon-magenta hover:bg-upsilon-magenta hover:text-white transition-all duration-300 animate-pulse"
                    style="box-shadow: 0 0 8px rgba(255, 0, 255, 0.4);"
                >
                    ⬡ STOP
                </button>

                <div v-if="fsm === 'revealing'" class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest animate-pulse py-3">
                    Resolving acquisition...
                </div>
            </div>

            <!-- Revealed skill detail -->
            <div v-if="fsm === 'revealed' && rolledSkill" class="border border-upsilon-magenta/30 bg-black/40 p-6 space-y-6">
                <div class="text-center">
                    <div class="font-mono text-[8px] text-upsilon-magenta uppercase tracking-[0.5em] mb-2 animate-pulse">
                        ◈ SKILL ACQUIRED ◈
                    </div>
                    <div class="font-mono text-[8px] text-upsilon-steel uppercase tracking-widest">
                        This protocol has been registered to your combatant.
                    </div>
                </div>

                <SkillDetail :skill="rolledSkill" />

                <div class="flex justify-center">
                    <button
                        @click="accept"
                        class="px-10 py-3 font-scifi text-[11px] uppercase tracking-[0.3em] bg-upsilon-magenta/20 border border-upsilon-magenta text-upsilon-magenta hover:bg-upsilon-magenta hover:text-white transition-all duration-300"
                        style="box-shadow: 0 0 12px rgba(255, 0, 255, 0.4);"
                    >
                        ⬢ ACCEPT & CLOSE
                    </button>
                </div>
            </div>
        </div>
    </ModalBox>
</template>
