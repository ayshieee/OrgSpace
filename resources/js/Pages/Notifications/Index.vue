<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { BellIcon, CheckIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
});

function open(notification) {
    if (!notification.read) {
        router.post(route('notifications.read', notification.id), {}, { preserveScroll: true });
    }
    if (notification.link) {
        router.visit(notification.link);
    }
}

function markAllRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}

function destroy(notification) {
    router.delete(route('notifications.destroy', notification.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>Notifications</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Notifications</h1>
            <button type="button" @click="markAllRead" class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                Mark all read
            </button>
        </div>

        <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 divide-y divide-neutral-100">
            <div v-for="n in notifications.data" :key="n.id"
                class="flex items-start gap-3 px-5 py-3.5 cursor-pointer"
                :class="n.read ? 'hover:bg-neutral-50' : 'bg-primary-50/40 hover:bg-primary-50/70'"
                @click="open(n)">
                <div class="w-8 h-8 rounded-full bg-primary-50 flex items-center justify-center shrink-0 mt-0.5">
                    <BellIcon class="w-4 h-4 text-primary-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-tertiary-800">{{ n.title }}</p>
                    <p class="text-xs text-tertiary-500 mt-0.5">{{ n.body }}</p>
                    <p class="text-[11px] text-tertiary-400 mt-1">{{ n.created_at }}</p>
                </div>
                <span v-if="!n.read" class="w-2 h-2 rounded-full bg-primary-500 shrink-0 mt-1.5" />
                <button type="button" @click.stop="destroy(n)" class="text-tertiary-300 hover:text-red-500 shrink-0">
                    <TrashIcon class="w-4 h-4" />
                </button>
            </div>
            <p v-if="!notifications.data.length" class="px-5 py-10 text-center text-sm text-tertiary-400">
                No notifications yet — you'll see updates here when something needs your attention.
            </p>
        </div>

        <div v-if="notifications.links?.length > 3" class="flex items-center justify-center gap-1 mt-4">
            <Link v-for="link in notifications.links" :key="link.label" :href="link.url ?? '#'"
                class="px-3 py-1.5 text-xs rounded-md"
                :class="[link.active ? 'bg-primary text-white' : 'text-tertiary-500 hover:bg-neutral-100', !link.url && 'opacity-30 pointer-events-none']"
                v-html="link.label" />
        </div>
    </AuthenticatedLayout>
</template>
