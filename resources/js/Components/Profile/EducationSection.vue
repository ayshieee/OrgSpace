<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { AcademicCapIcon, PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EducationFormModal from './EducationFormModal.vue';

const props = defineProps({
    educations: { type: Array, required: true },
});

const expanded = ref(false);
const LIMIT = 3;
const visible = computed(() => expanded.value ? props.educations : props.educations.slice(0, LIMIT));

const modalOpen = ref(false);
const editing = ref(null);

function openAdd() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(education) {
    editing.value = education;
    modalOpen.value = true;
}

function remove(education) {
    if (!confirm(`Remove "${education.institution}" from your education?`)) return;
    router.delete(route('profile.education.destroy', education.id), { preserveScroll: true });
}

function formatRange(education) {
    const fmt = (d) => new Date(`${d}T00:00:00`).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    const start = education.start_date ? fmt(education.start_date) : null;
    const end = education.is_current ? 'Present' : (education.end_date ? fmt(education.end_date) : null);

    if (start && end) return `${start} — ${end}`;
    if (start) return start;
    if (end) return end;
    return null;
}
</script>

<template>
    <section>
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 font-heading text-lg font-bold text-tertiary-900">
                <AcademicCapIcon class="w-5 h-5 text-primary-500" /> Education
            </h2>
            <button type="button" @click="openAdd"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                <PlusIcon class="w-4 h-4" /> Add
            </button>
        </div>

        <div v-if="educations.length" class="mt-4 divide-y divide-neutral-100">
            <div v-for="edu in visible" :key="edu.id" class="py-4 first:pt-0 flex items-start gap-3 group">
                <div class="w-11 h-11 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                    <AcademicCapIcon class="w-5 h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-tertiary-900 text-sm">{{ edu.institution }}</p>
                    <p v-if="edu.program" class="text-sm text-tertiary-500">{{ edu.program }}</p>
                    <p v-if="formatRange(edu)" class="text-xs text-tertiary-400 mt-0.5">{{ formatRange(edu) }}</p>
                </div>
                <div class="hidden group-hover:flex items-center gap-1 shrink-0">
                    <button type="button" @click="openEdit(edu)" class="p-1.5 rounded-lg text-tertiary-400 hover:bg-neutral-100 hover:text-tertiary-700 transition-colors">
                        <PencilSquareIcon class="w-4 h-4" />
                    </button>
                    <button type="button" @click="remove(edu)" class="p-1.5 rounded-lg text-tertiary-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
        <p v-else class="mt-4 text-sm text-tertiary-400">No education added yet.</p>

        <button v-if="educations.length > LIMIT" type="button" @click="expanded = !expanded"
            class="mt-3 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
            {{ expanded ? 'Show less' : `Show all (${educations.length})` }}
        </button>

        <EducationFormModal :show="modalOpen" :education="editing" @close="modalOpen = false" />
    </section>
</template>
