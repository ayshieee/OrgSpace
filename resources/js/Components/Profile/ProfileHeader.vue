<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CameraIcon, EllipsisHorizontalIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import { getInitials, colorForId } from '@/utils/initials';
import EditPersonalDetailsModal from './EditPersonalDetailsModal.vue';

const props = defineProps({
    user: { type: Object, required: true },
    memberships: { type: Array, required: true },
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
});

const vClickOutside = {
    mounted(el, binding) {
        el.__clickOutsideHandler__ = (event) => {
            if (!el.contains(event.target)) {
                binding.value(event);
            }
        };
        document.addEventListener('mousedown', el.__clickOutsideHandler__);
    },
    unmounted(el) {
        document.removeEventListener('mousedown', el.__clickOutsideHandler__);
    },
};

const editOpen = ref(false);
const menuOpen = ref(false);

const primaryMembership = props.memberships[0] ?? null;

const avatarInput = ref(null);
const preview = ref(null);
const avatarForm = useForm({ avatar: null });

function onAvatarChange(event) {
    const file = event.target.files[0] ?? null;
    if (!file) return;

    preview.value = URL.createObjectURL(file);
    avatarForm.avatar = file;
    avatarForm.post(route('profile.avatar.update'), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { preview.value = null; },
    });
}

function removeAvatar() {
    if (!confirm('Remove your profile picture?')) return;
    router.delete(route('profile.avatar.destroy'), { preserveScroll: true });
    menuOpen.value = false;
}

function scrollTo(id) {
    menuOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-neutral-200 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start gap-6">
            <!-- Avatar -->
            <div class="relative shrink-0 mx-auto sm:mx-0">
                <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full flex items-center justify-center text-3xl font-bold overflow-hidden ring-4 ring-white shadow-elevated"
                    :class="(preview || user.avatar_path) ? '' : colorForId(user.id)">
                    <img v-if="preview || user.avatar_path" :src="preview || `/storage/${user.avatar_path}`" class="w-full h-full object-cover" :alt="user.name" />
                    <template v-else>{{ getInitials(user.name) }}</template>
                </div>
                <button type="button" @click="avatarInput.click()" title="Change photo"
                    class="absolute bottom-0 right-0 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center shadow-elevated hover:bg-primary-600 transition-colors duration-200 ease-ios">
                    <CameraIcon class="w-4 h-4" />
                </button>
                <input ref="avatarInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onAvatarChange" />
            </div>

            <!-- Identity -->
            <div class="flex-1 min-w-0 text-center sm:text-left">
                <div class="flex items-center gap-2 justify-center sm:justify-start flex-wrap">
                    <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">{{ user.name }}</h1>
                    <span v-if="user.pronouns" class="text-sm text-tertiary-400">({{ user.pronouns }})</span>
                </div>

                <p v-if="primaryMembership" class="mt-1 text-sm font-medium text-primary-600">
                    {{ primaryMembership.role }} at {{ primaryMembership.organization_name }}
                </p>

                <p v-if="user.bio" class="mt-3 text-sm text-tertiary-600 max-w-xl">{{ user.bio }}</p>

                <button v-if="user.avatar_path" type="button" @click="removeAvatar"
                    class="mt-3 text-xs font-semibold text-tertiary-400 hover:text-red-500 transition-colors">
                    Remove Photo
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0 justify-center sm:justify-start">
                <button type="button" @click="editOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-sm font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
                    <PencilSquareIcon class="w-4 h-4" /> Edit Profile
                </button>

                <div class="relative" v-click-outside="() => (menuOpen = false)">
                    <button type="button" @click="menuOpen = !menuOpen"
                        class="w-9 h-9 rounded-lg border border-neutral-200 flex items-center justify-center text-tertiary-400 hover:bg-neutral-50 hover:text-tertiary-700 transition-colors">
                        <EllipsisHorizontalIcon class="w-5 h-5" />
                    </button>
                    <Transition
                        enter-active-class="transition duration-150 ease-ios"
                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="menuOpen" class="absolute right-0 mt-2 w-44 bg-white border border-neutral-200/60 rounded-2xl shadow-elevated py-1.5 z-20 origin-top-right">
                            <button type="button" @click="scrollTo('password')" class="w-full text-left block px-3.5 py-2 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">Change Password</button>
                            <button type="button" @click="scrollTo('delete-account')" class="w-full text-left block px-3.5 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors">Delete Account</button>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <EditPersonalDetailsModal :show="editOpen" :user="user" :must-verify-email="mustVerifyEmail" :status="status" @close="editOpen = false" />
    </div>
</template>
