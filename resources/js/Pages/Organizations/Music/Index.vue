<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    MusicalNoteIcon,
    DocumentArrowUpIcon,
    TrashIcon,
    LockClosedIcon,
    SparklesIcon,
    ShieldCheckIcon,
    StarIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import MusicPreviewModal from '@/Components/Music/MusicPreviewModal.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    entries: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    defaults: { type: Object, required: true },
});

const sectionFilter = ref('all');
const categoryFilter = ref('all');
const favoritesOnly = ref(false);
const previewing = ref(null);
const showUploadForm = ref(false);

const sections = computed(() => [...new Set(props.entries.map((e) => e.section).filter(Boolean))]);
const categories = computed(() => [...new Set(props.entries.map((e) => e.category).filter(Boolean))]);

const filteredEntries = computed(() => props.entries.filter((e) => {
    if (sectionFilter.value !== 'all' && e.section !== sectionFilter.value) return false;
    if (categoryFilter.value !== 'all' && e.category !== categoryFilter.value) return false;
    if (favoritesOnly.value && !e.is_favorited) return false;
    return true;
}));

const form = useForm({
    file: null,
    title: '',
    composer: '',
    arranger: '',
    section: '',
    category: '',
    is_restricted: props.defaults.restricted,
    apply_watermark: props.defaults.watermark,
});

const defaultsForm = useForm({ watermark: props.defaults.watermark, restricted: props.defaults.restricted });

function updateDefault(key, value) {
    defaultsForm[key] = value;
    defaultsForm.post(route('organizations.music.defaults.update', props.organization.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.is_restricted = defaultsForm.restricted;
            form.apply_watermark = defaultsForm.watermark;
        },
    });
}

function onFileChange(event) {
    form.file = event.target.files[0] ?? null;
}

function upload() {
    form.post(route('organizations.music.store', props.organization.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('file', 'title', 'composer', 'arranger', 'section', 'category');
            showUploadForm.value = false;
        },
    });
}

function destroy(entry) {
    if (!confirm(`Remove "${entry.title}"?`)) return;
    router.delete(route('organizations.music.destroy', [props.organization.id, entry.id]), { preserveScroll: true });
}

function toggleRestriction(entry) {
    router.post(route('organizations.music.toggle-restriction', [props.organization.id, entry.id]), {}, { preserveScroll: true });
}

function toggleFavorite(entry) {
    router.post(route('organizations.music.favorite', [props.organization.id, entry.id]), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Music Library" />

    <AuthenticatedLayout>
        <template #header>Music Library</template>

        <h1 class="font-heading text-xl font-extrabold text-tertiary-900 mb-5">Music Library</h1>

        <div v-if="canManage" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 mb-6">
            <h2 class="flex items-center gap-1.5 font-heading font-bold text-tertiary-900 mb-4">
                <ShieldCheckIcon class="w-4 h-4 text-primary-500" /> Digital Rights Defaults
            </h2>
            <div class="flex items-center justify-between py-1.5">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800">Watermark new scans by default</p>
                    <p class="text-xs text-tertiary-400">Applies to newly uploaded JPG/PNG scans — doesn't affect scores already uploaded.</p>
                </div>
                <ToggleSwitch :model-value="defaultsForm.watermark" @update:model-value="(v) => updateDefault('watermark', v)" />
            </div>
            <div class="flex items-center justify-between py-1.5 mt-2 pt-2 border-t border-neutral-100">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800">Restrict new uploads by default</p>
                    <p class="text-xs text-tertiary-400">New scores are view-only for members unless you turn this off per upload.</p>
                </div>
                <ToggleSwitch :model-value="defaultsForm.restricted" @update:model-value="(v) => updateDefault('restricted', v)" />
            </div>
        </div>

        <div v-if="canManage" class="mb-6">
            <button v-if="!showUploadForm" type="button" @click="showUploadForm = true"
                class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
                <DocumentArrowUpIcon class="w-4 h-4" /> Upload Score
            </button>

            <form v-else @submit.prevent="upload" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input v-model="form.title" type="text" placeholder="Title *" required
                        class="rounded-md border-neutral-200 text-sm focus:border-primary-400 focus:ring-primary-400" />
                    <input v-model="form.composer" type="text" placeholder="Composer"
                        class="rounded-md border-neutral-200 text-sm focus:border-primary-400 focus:ring-primary-400" />
                    <input v-model="form.arranger" type="text" placeholder="Arranger"
                        class="rounded-md border-neutral-200 text-sm focus:border-primary-400 focus:ring-primary-400" />
                    <input v-model="form.section" type="text" placeholder="Section (e.g. Soprano)"
                        class="rounded-md border-neutral-200 text-sm focus:border-primary-400 focus:ring-primary-400" />
                    <input v-model="form.category" type="text" placeholder="Category (e.g. Sacred)"
                        class="rounded-md border-neutral-200 text-sm focus:border-primary-400 focus:ring-primary-400" />
                    <input type="file" accept=".pdf,.png,.jpg,.jpeg" @change="onFileChange"
                        class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-700" />
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-1.5 text-xs text-tertiary-600">
                        <input type="checkbox" v-model="form.is_restricted" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                        View-only (no download for members)
                    </label>
                    <label class="flex items-center gap-1.5 text-xs text-tertiary-600">
                        <input type="checkbox" v-model="form.apply_watermark" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                        Watermark (JPG/PNG only)
                    </label>
                </div>
                <p v-if="form.errors.file" class="text-xs text-red-600">{{ form.errors.file }}</p>
                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center rounded-lg bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98] disabled:opacity-50">
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </button>
                    <button type="button" @click="showUploadForm = false" class="text-xs font-semibold text-tertiary-500 hover:text-tertiary-700">Cancel</button>
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-4">
            <select v-model="sectionFilter" class="text-xs rounded-md border-neutral-200 focus:border-primary-400 focus:ring-primary-400">
                <option value="all">All sections</option>
                <option v-for="s in sections" :key="s" :value="s">{{ s }}</option>
            </select>
            <select v-model="categoryFilter" class="text-xs rounded-md border-neutral-200 focus:border-primary-400 focus:ring-primary-400">
                <option value="all">All categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <button type="button" @click="favoritesOnly = !favoritesOnly"
                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-md"
                :class="favoritesOnly ? 'bg-secondary-100 text-secondary-700' : 'bg-neutral-100 text-tertiary-500'">
                <StarIconSolid class="w-3.5 h-3.5" /> Favorites
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="entry in filteredEntries" :key="entry.id" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-4 flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <MusicalNoteIcon class="w-7 h-7 text-primary-400 shrink-0" />
                    <button type="button" @click="toggleFavorite(entry)" class="shrink-0">
                        <StarIconSolid v-if="entry.is_favorited" class="w-4 h-4 text-secondary-500" />
                        <StarIcon v-else class="w-4 h-4 text-tertiary-300 hover:text-secondary-500" />
                    </button>
                </div>
                <p class="text-sm font-semibold text-tertiary-800 flex items-center gap-1.5">
                    {{ entry.title }}
                    <LockClosedIcon v-if="entry.is_restricted" class="w-3.5 h-3.5 text-secondary-500 shrink-0" title="View-only" />
                    <SparklesIcon v-if="entry.is_watermarked" class="w-3.5 h-3.5 text-primary-500 shrink-0" title="Watermarked" />
                </p>
                <p class="text-xs text-tertiary-400 mb-1">{{ [entry.composer, entry.arranger].filter(Boolean).join(' · arr. ') || ' ' }}</p>
                <p class="text-xs text-tertiary-400">{{ [entry.section, entry.category].filter(Boolean).join(' · ') || ' ' }}</p>
                <p class="text-xs text-tertiary-300 mt-1">{{ entry.size }} &middot; {{ entry.uploader }}</p>

                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-neutral-100">
                    <button v-if="entry.url" type="button" @click="previewing = entry"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                        <EyeIcon class="w-3.5 h-3.5" /> View
                    </button>
                    <span v-else class="text-xs text-tertiary-300 italic">Restricted</span>
                    <button v-if="canManage" type="button" @click="toggleRestriction(entry)"
                        class="ml-auto text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded"
                        :class="entry.is_restricted ? 'bg-secondary-100 text-secondary-700' : 'bg-neutral-100 text-tertiary-400'">
                        {{ entry.is_restricted ? 'View-only' : 'Downloadable' }}
                    </button>
                    <button v-if="canManage" type="button" @click="destroy(entry)" class="text-tertiary-300 hover:text-red-500">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <p v-if="!filteredEntries.length" class="col-span-full text-center text-sm text-tertiary-400 py-10">
                No scores match these filters.
            </p>
        </div>

        <MusicPreviewModal v-if="previewing" :organization-id="organization.id" :entry="previewing" @close="previewing = null" />
    </AuthenticatedLayout>
</template>
