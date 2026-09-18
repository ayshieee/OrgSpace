<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { LinkIcon, ListBulletIcon, QueueListIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EventImagePicker from './EventImagePicker.vue';
import { vClickOutside } from '@/directives/clickOutside';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    editing: { type: Object, default: null }, // null = creating a new event
    canManageAttendance: { type: Boolean, default: false },
    attendanceModuleEnabled: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    category: '',
    description: '',
    location: '',
    starts_at: '',
    call_time: '',
    ends_at: '',
    image: null,
    remove_image: false,
    track_attendance: true,
});

const linkPopoverOpen = ref(false);
const linkUrlInput = ref('');

const editor = useEditor({
    content: '',
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false, autolink: false }),
        Placeholder.configure({ placeholder: 'Program notes, attire, or other instructions...' }),
    ],
    editorProps: {
        attributes: {
            class: 'event-editor-content min-h-[100px] focus:outline-none text-sm text-tertiary-800',
        },
    },
    onUpdate: ({ editor }) => {
        form.description = editor.getHTML();
    },
});

watch(() => props.show, (show) => {
    if (!show) return;

    form.clearErrors();
    linkPopoverOpen.value = false;
    form.image = null;
    form.remove_image = false;

    if (props.editing) {
        form.title = props.editing.title;
        form.category = props.editing.category ?? '';
        form.description = props.editing.description ?? '';
        form.location = props.editing.location ?? '';
        form.starts_at = props.editing.starts_at_input;
        form.call_time = props.editing.call_time ?? '';
        form.ends_at = props.editing.ends_at_input ?? '';
        form.track_attendance = props.editing.track_attendance;
    } else {
        form.title = '';
        form.category = '';
        form.description = '';
        form.location = '';
        form.starts_at = '';
        form.call_time = '';
        form.ends_at = '';
        form.track_attendance = true;
    }

    editor.value?.commands.setContent(form.description || '');
});

onBeforeUnmount(() => editor.value?.destroy());

function toggleLinkPopover() {
    if (!linkPopoverOpen.value) {
        linkUrlInput.value = editor.value?.getAttributes('link').href ?? '';
    }
    linkPopoverOpen.value = !linkPopoverOpen.value;
}

function applyLink() {
    const url = linkUrlInput.value.trim();

    if (!url) {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        linkPopoverOpen.value = false;

        return;
    }

    if (!/^https?:\/\//i.test(url)) {
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    linkPopoverOpen.value = false;
}

function onImageRemoved() {
    form.remove_image = true;
}

function submit() {
    if (props.editing) {
        form.patch(route('organizations.events.update', [props.organizationId, props.editing.id]), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('organizations.events.store', props.organizationId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => emit('close'),
        });
    }
}
</script>

<template>
    <Modal :show="show" max-width="xl" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">{{ editing ? 'Edit Event' : 'Schedule Event' }}</h2>
                <p class="mt-0.5 text-sm text-tertiary-500">Plan rehearsals, meetings, or productions and sync RSVP tracking with your roster.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="event-title" value="Event Title" />
                    <TextInput id="event-title" v-model="form.title" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. Spring Choral Gala" />
                    <InputError :message="form.errors.title" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="event-category" value="Category (optional)" />
                    <TextInput id="event-category" v-model="form.category" type="text" class="mt-1 block w-full" placeholder="e.g. Major Production" />
                    <InputError :message="form.errors.category" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <InputLabel for="event-starts" value="Starts" />
                    <TextInput id="event-starts" v-model="form.starts_at" type="datetime-local" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.starts_at" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="event-call-time" value="Call Time (optional)" />
                    <TextInput id="event-call-time" v-model="form.call_time" type="time" class="mt-1 block w-full" />
                    <InputError :message="form.errors.call_time" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="event-ends" value="Ends (optional)" />
                    <TextInput id="event-ends" v-model="form.ends_at" type="datetime-local" class="mt-1 block w-full" />
                    <InputError :message="form.errors.ends_at" class="mt-1" />
                </div>
            </div>

            <div>
                <InputLabel for="event-location" value="Location (optional)" />
                <div class="mt-1 relative">
                    <MapPinIcon class="w-4 h-4 text-tertiary-300 absolute left-3 top-1/2 -translate-y-1/2" />
                    <TextInput id="event-location" v-model="form.location" type="text" class="block w-full pl-9" placeholder="e.g. University Symphony Hall" />
                </div>
                <InputError :message="form.errors.location" class="mt-1" />
            </div>

            <div>
                <InputLabel value="Program Notes (optional)" />
                <div class="mt-1 rounded-xl border border-tertiary-200 overflow-visible focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 transition-colors duration-200">
                    <EditorContent :editor="editor" class="px-3.5 py-3" />

                    <div class="flex items-center flex-wrap gap-0.5 border-t border-neutral-100 px-2 py-1.5 relative">
                        <button type="button" title="Bold" @mousedown.prevent="editor?.chain().focus().toggleBold().run()"
                            class="w-7 h-7 rounded-md text-sm font-bold flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('bold') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">B</button>
                        <button type="button" title="Italic" @mousedown.prevent="editor?.chain().focus().toggleItalic().run()"
                            class="w-7 h-7 rounded-md text-sm italic flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('italic') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">I</button>
                        <button type="button" title="Underline" @mousedown.prevent="editor?.chain().focus().toggleUnderline().run()"
                            class="w-7 h-7 rounded-md text-sm underline flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('underline') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">U</button>

                        <span class="w-px h-5 bg-neutral-200 mx-1"></span>

                        <button type="button" title="Bulleted List" @mousedown.prevent="editor?.chain().focus().toggleBulletList().run()"
                            class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('bulletList') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                            <ListBulletIcon class="w-4 h-4" />
                        </button>
                        <button type="button" title="Numbered List" @mousedown.prevent="editor?.chain().focus().toggleOrderedList().run()"
                            class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('orderedList') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                            <QueueListIcon class="w-4 h-4" />
                        </button>
                        <button type="button" title="Quote" @mousedown.prevent="editor?.chain().focus().toggleBlockquote().run()"
                            class="w-7 h-7 rounded-md text-base font-serif flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('blockquote') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">&ldquo;</button>

                        <span class="w-px h-5 bg-neutral-200 mx-1"></span>

                        <div class="relative" v-click-outside="() => (linkPopoverOpen = false)">
                            <button type="button" title="Insert Link" @mousedown.prevent="toggleLinkPopover"
                                class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                                :class="editor?.isActive('link') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                                <LinkIcon class="w-4 h-4" />
                            </button>
                            <div v-if="linkPopoverOpen" class="absolute bottom-9 left-0 bg-white border border-neutral-200/60 rounded-xl shadow-elevated p-2.5 flex items-center gap-1.5 z-20 w-64">
                                <input v-model="linkUrlInput" type="url" placeholder="https://example.com" @keydown.enter.prevent="applyLink"
                                    class="flex-1 text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                                <button type="button" @mousedown.prevent="applyLink" class="text-xs font-semibold text-primary-600 hover:text-primary-700 shrink-0">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <InputError :message="form.errors.description" class="mt-1" />
            </div>

            <div>
                <InputLabel value="Event Image (optional)" />
                <div class="mt-1">
                    <EventImagePicker v-model="form.image" :existing-url="editing?.image_url" @remove="onImageRemoved" />
                </div>
                <InputError :message="form.errors.image" class="mt-1" />
            </div>

            <div v-if="attendanceModuleEnabled" class="rounded-xl border border-neutral-200 p-3.5">
                <label class="flex items-start gap-2.5" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                    <input type="checkbox" v-model="form.track_attendance" :disabled="!canManageAttendance"
                        class="mt-0.5 rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                    <span>
                        <span class="block text-sm font-semibold text-tertiary-800">Track attendance for this event</span>
                        <span class="block text-xs text-tertiary-400 mt-0.5">
                            <template v-if="canManageAttendance">Automatically creates a linked attendance session so you can compare RSVPs against who actually showed up.</template>
                            <template v-else>Requires the Manage Attendance permission — ask an adviser to enable this for your role.</template>
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">{{ editing ? 'Save Changes' : 'Schedule & Publish Event' }}</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>

<style>
.event-editor-content ul { list-style: disc; padding-left: 1.5rem; }
.event-editor-content ol { list-style: decimal; padding-left: 1.5rem; }
.event-editor-content blockquote {
    border-left: 3px solid #2E418D;
    padding-left: 0.75rem;
    margin-left: 0;
    color: #4B5563;
    font-style: italic;
}
.event-editor-content a { color: #2E418D; text-decoration: underline; }
.event-editor-content p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9CA3AF;
    pointer-events: none;
    height: 0;
}
</style>
