<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    DocumentArrowUpIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    PlusIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import WizardWorkspaceLayout from '@/Layouts/WizardWorkspaceLayout.vue';
import WizardFooterNav from '@/Components/Onboarding/WizardFooterNav.vue';
import RosterPreviewTable from '@/Components/Onboarding/RosterPreviewTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    members: { type: Array, required: true },
    roles: { type: Array, required: true },
    fromReview: { type: Boolean, default: false },
});

const tab = ref('import'); // 'import' | 'permissions'
const showManualEntry = ref(false);
const showFormatReference = ref(false);
const dragging = ref(false);
const checking = ref(false);
const fileInput = ref(null);
const previewRows = reactive([]);

function blankRow() {
    return { first_name: '', m_i: '', surname: '', student_id: '', email: '', status: null, errors: {} };
}

const manualRows = reactive([blankRow()]);

function defaultRoleId() {
    const member = props.roles.find((r) => r.name.trim().toLowerCase() === 'member');
    return member?.id ?? props.roles[0]?.id ?? null;
}

function applyResults(results) {
    const previousRoles = new Map(previewRows.map((r) => [r.email, r.role_id]));

    previewRows.splice(0, previewRows.length, ...results.map((r) => ({
        ...r.row,
        status: r.status,
        errors: r.errors,
        role_id: previousRoles.has(r.row.email) ? previousRoles.get(r.row.email) : defaultRoleId(),
    })));

    if (previewRows.length) {
        tab.value = 'permissions';
    }
}

async function checkBatch(rows) {
    checking.value = true;
    try {
        const { data } = await window.axios.post(route('onboarding.members.import.validate'), { rows });
        applyResults(data.results);
    } finally {
        checking.value = false;
    }
}

async function importFile(file) {
    if (!file) return;

    checking.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        const { data } = await window.axios.post(route('onboarding.members.import.validate'), formData);
        applyResults(data.results);
    } finally {
        checking.value = false;
    }
}

function onFileChange(event) {
    importFile(event.target.files[0]);
    event.target.value = '';
}

function onDrop(event) {
    dragging.value = false;
    importFile(event.dataTransfer.files[0]);
}

function addManualRow() {
    manualRows.push(blankRow());
}

function removeManualRow(index) {
    manualRows.splice(index, 1);
}

async function validateManualRows() {
    await checkBatch(manualRows.map(({ first_name, m_i, surname, student_id, email }) => ({ first_name, m_i, surname, student_id, email })));
}

function updatePreviewRow(index, field, value) {
    previewRows[index][field] = value;
}

function removePreviewRow(index) {
    previewRows.splice(index, 1);
}

async function recheck() {
    await checkBatch(previewRows.map(({ first_name, m_i, surname, student_id, email }) => ({ first_name, m_i, surname, student_id, email })));
}

const hasErrors = computed(() => previewRows.some((r) => r.status === 'error'));
const readyToCommit = computed(() => previewRows.length > 0 && !hasErrors.value);

const commitForm = useForm({
    rows: [],
    return_to: props.fromReview ? 'review' : null,
});

function commit() {
    commitForm.rows = previewRows.map(({ first_name, m_i, surname, student_id, email, role_id }) => ({ first_name, m_i, surname, student_id, email, role_id }));
    commitForm.post(route('onboarding.members.commit'));
}

const skipForm = useForm({ return_to: props.fromReview ? 'review' : null });
function skip() {
    skipForm.post(route('onboarding.members.skip'));
}

function removeExistingMember(id) {
    router.delete(route('onboarding.members.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Add Members" />

    <WizardWorkspaceLayout step="members" :breadcrumbs="['Dashboard', 'Members', 'Add Members']" content-class="max-w-4xl">
        <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Add Members</h1>

        <!-- Already-added roster -->
        <div v-if="members.length" class="mt-5 border border-neutral-200 rounded-xl divide-y divide-neutral-100 bg-white">
            <div v-for="member in members" :key="member.id" class="flex items-center justify-between px-4 py-2.5 text-sm">
                <div>
                    <span class="font-medium text-tertiary-800">{{ member.name }}</span>
                    <span class="text-tertiary-400 ml-2">{{ member.email }}</span>
                </div>
                <button type="button" @click="removeExistingMember(member.id)" class="text-tertiary-300 hover:text-red-500">
                    <TrashIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-6 flex items-center gap-6 border-b border-neutral-200">
            <button type="button" @click="tab = 'import'"
                class="pb-3 text-sm border-b-2 -mb-px transition-colors"
                :class="tab === 'import' ? 'font-semibold text-primary border-primary' : 'font-medium text-tertiary-400 border-transparent hover:text-tertiary-600'">
                Import
            </button>
            <button type="button" @click="previewRows.length && (tab = 'permissions')"
                class="pb-3 text-sm border-b-2 -mb-px transition-colors"
                :class="[
                    tab === 'permissions' ? 'font-semibold text-primary border-primary' : 'font-medium text-tertiary-400 border-transparent',
                    previewRows.length ? 'hover:text-tertiary-600 cursor-pointer' : 'opacity-40 cursor-not-allowed',
                ]">
                Edit Permissions
                <span v-if="previewRows.length" class="ml-1 text-xs">({{ previewRows.length }})</span>
            </button>
        </div>

        <!-- Import tab -->
        <div v-if="tab === 'import'" class="mt-6 space-y-4">
            <div class="border border-neutral-200 rounded-lg bg-white">
                <button type="button" @click="showFormatReference = !showFormatReference"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-tertiary-600">
                    <span>Format Reference</span>
                    <ChevronDownIcon class="w-4 h-4 transition-transform" :class="showFormatReference ? 'rotate-180' : ''" />
                </button>
                <div v-if="showFormatReference" class="px-4 pb-3 text-xs text-tertiary-500 border-t border-neutral-100 pt-3">
                    Columns, in order: First Name, M.I., Surname, Student ID, Email.
                    <a :href="route('onboarding.members.template')" class="inline-flex items-center gap-1 ml-2 font-semibold text-primary-600 hover:text-primary-700">
                        <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Download Template
                    </a>
                </div>
            </div>

            <div
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
                class="rounded-xl border-2 border-dashed p-10 flex flex-col items-center justify-center text-center transition-colors"
                :class="dragging ? 'border-primary-400 bg-primary-50/40' : 'border-neutral-300 bg-white'"
            >
                <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center mb-3">
                    <DocumentArrowUpIcon class="w-6 h-6 text-primary-400" />
                </div>
                <p class="text-sm font-semibold text-tertiary-700">Drag and drop your file here</p>
                <p class="text-xs text-tertiary-400 mt-1">Supports .xlsx, .xls, .csv up to 10MB</p>
                <button type="button" @click="fileInput.click()" :disabled="checking"
                    class="mt-4 inline-flex items-center rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98] disabled:opacity-50">
                    {{ checking ? 'Checking…' : 'Browse Files' }}
                </button>
                <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileChange" />
            </div>

            <div class="text-center">
                <button type="button" @click="showManualEntry = !showManualEntry" class="text-sm font-semibold text-primary-600 hover:text-primary-700">
                    {{ showManualEntry ? 'Hide manual entry' : 'or add members individually' }}
                </button>
            </div>

            <div v-if="showManualEntry" class="space-y-3 border border-neutral-200 rounded-xl bg-white p-4">
                <div v-for="(row, index) in manualRows" :key="index" class="flex flex-wrap gap-2 items-start">
                    <input v-model="row.first_name" placeholder="First Name" class="w-28 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    <input v-model="row.m_i" placeholder="M.I." class="w-14 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    <input v-model="row.surname" placeholder="Surname" class="w-32 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    <input v-model="row.student_id" placeholder="Student ID" class="w-32 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    <input v-model="row.email" placeholder="Email" type="email" class="w-56 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    <button type="button" @click="removeManualRow(index)" class="text-tertiary-300 hover:text-red-500 mt-1.5">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="addManualRow" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700">
                        <PlusIcon class="w-4 h-4" /> Add Another
                    </button>
                    <SecondaryButton type="button" @click="validateManualRows" :disabled="checking">
                        {{ checking ? 'Checking…' : 'Validate & Preview' }}
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <!-- Edit Permissions tab (roster preview) -->
        <div v-else-if="previewRows.length" class="mt-6 space-y-3">
            <h3 class="text-sm font-semibold text-tertiary-700">Preview ({{ previewRows.length }} row{{ previewRows.length === 1 ? '' : 's' }})</h3>
            <RosterPreviewTable :rows="previewRows" :roles="roles" @update-row="updatePreviewRow" @remove-row="removePreviewRow" />
            <div class="flex items-center justify-between">
                <button type="button" @click="recheck" :disabled="checking" class="text-sm font-semibold text-primary-600 hover:text-primary-700">
                    {{ checking ? 'Re-checking…' : 'Re-check Rows' }}
                </button>
                <div v-if="hasErrors" class="text-sm text-red-600 font-medium">Fix the highlighted rows before continuing.</div>
            </div>
            <p class="text-xs text-tertiary-400">Each member's Role determines their permissions in this organization — set it per person above.</p>
        </div>

        <InputError :message="commitForm.errors.rows" class="mt-2" />

        <WizardFooterNav :back-route="fromReview ? route('onboarding.review.show') : route('onboarding.roles.show')">
            <template #secondary>
                <button type="button" @click="skip" :disabled="skipForm.processing" class="text-sm font-semibold text-tertiary-400 hover:text-tertiary-600">
                    Skip — Add Members Later
                </button>
            </template>
            <template #primary>
                <PrimaryButton :disabled="!readyToCommit || commitForm.processing" @click="commit">
                    {{ fromReview ? 'Save & Return to Review' : 'Add to Roster & Continue' }}
                </PrimaryButton>
            </template>
        </WizardFooterNav>
    </WizardWorkspaceLayout>
</template>
