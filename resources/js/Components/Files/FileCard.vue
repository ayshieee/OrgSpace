<script setup>
import { computed, ref } from 'vue';
import {
    EllipsisVerticalIcon, EyeIcon, ArrowDownTrayIcon, PencilSquareIcon,
    TrashIcon, LockClosedIcon, LockOpenIcon, SparklesIcon, ShieldCheckIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline';
import { iconFor } from '@/utils/fileIcons';
import { vClickOutside } from '@/directives/clickOutside';

const OFFICE_MIMES = [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];

const props = defineProps({
    item: { type: Object, required: true },
    tier: { type: String, default: null }, // 'manage' | 'edit' | 'view' | null
});

const emit = defineEmits(['open', 'rename', 'delete', 'toggle-restriction', 'open-office', 'manage-permissions']);

const menuOpen = ref(false);
const icon = computed(() => iconFor(props.item.mime_type, props.item.is_folder));
const canEdit = computed(() => props.tier === 'edit' || props.tier === 'manage');
const canManage = computed(() => props.tier === 'manage');
const isOfficeDoc = computed(() => OFFICE_MIMES.includes(props.item.mime_type));

function toggleMenu() {
    menuOpen.value = !menuOpen.value;
}

function act(action) {
    menuOpen.value = false;
    emit(action, props.item);
}
</script>

<template>
    <div class="group relative bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-all duration-200 ease-ios p-4 cursor-pointer"
        @click="emit('open', item)">
        <div class="relative flex flex-col items-center text-center">
            <component :is="icon" class="w-10 h-10 text-primary-400 mb-2.5" />
            <p class="text-sm font-semibold text-tertiary-800 truncate max-w-full flex items-center gap-1">
                <span class="truncate">{{ item.name }}</span>
                <LockClosedIcon v-if="item.is_restricted" class="w-3 h-3 text-secondary-500 shrink-0" title="View-only" />
                <SparklesIcon v-if="item.is_watermarked" class="w-3 h-3 text-primary-500 shrink-0" title="Watermarked" />
            </p>
            <p class="text-xs text-tertiary-400 mt-0.5">
                <template v-if="item.is_folder">{{ item.items_count }} item{{ item.items_count === 1 ? '' : 's' }}</template>
                <template v-else>{{ item.size }} &middot; {{ item.uploaded_at }}</template>
            </p>
        </div>

        <div class="absolute top-2 right-2" v-click-outside="() => (menuOpen = false)" @click.stop>
            <button type="button" @click="toggleMenu"
                class="w-7 h-7 rounded-md flex items-center justify-center text-tertiary-400 hover:bg-neutral-100 hover:text-tertiary-700 transition-all duration-150 opacity-0 group-hover:opacity-100 focus:opacity-100"
                :class="menuOpen ? 'opacity-100 bg-neutral-100' : ''">
                <EllipsisVerticalIcon class="w-4 h-4" />
            </button>

            <div v-if="menuOpen"
                class="absolute right-0 mt-1 w-48 bg-white border border-neutral-200/60 rounded-xl shadow-elevated py-1.5 z-20 text-left">
                <button type="button" @click="act('open')"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                    <EyeIcon class="w-4 h-4 text-tertiary-400" /> {{ item.is_folder ? 'Open' : 'Preview' }}
                </button>
                <a v-if="item.download_url" :href="item.download_url"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                    <ArrowDownTrayIcon class="w-4 h-4 text-tertiary-400" /> Download
                </a>
                <button v-if="!item.is_folder && isOfficeDoc && canEdit" type="button" @click="act('open-office')"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                    <ArrowTopRightOnSquareIcon class="w-4 h-4 text-tertiary-400" /> Open in Office
                </button>
                <button v-if="canEdit" type="button" @click="act('rename')"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                    <PencilSquareIcon class="w-4 h-4 text-tertiary-400" /> Rename
                </button>
                <template v-if="canManage">
                    <button type="button" @click="act('manage-permissions')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                        <ShieldCheckIcon class="w-4 h-4 text-tertiary-400" /> Manage Permissions
                    </button>
                    <button v-if="!item.is_folder" type="button" @click="act('toggle-restriction')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">
                        <component :is="item.is_restricted ? LockOpenIcon : LockClosedIcon" class="w-4 h-4 text-tertiary-400" />
                        {{ item.is_restricted ? 'Make Downloadable' : 'Make View-only' }}
                    </button>
                    <button type="button" @click="act('delete')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                        <TrashIcon class="w-4 h-4" /> Delete
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
