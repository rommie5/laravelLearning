<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Bell, Check, Trash2, ExternalLink, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    notifications: Object, // paginated
    filter: { type: String, default: 'all' },
    unreadCount: { type: Number, default: 0 },
});

const filterTabs = [
    { key: 'all',          label: 'All' },
    { key: 'unread',       label: 'Unread' },
    { key: 'contracts',    label: 'Contracts' },
    { key: 'clauses',      label: 'Clauses' },
    { key: 'installments', label: 'Installments' },
    { key: 'system',       label: 'System' },
];

// ─── Color helpers ────────────────────────────────────────────────
const borderColorClass = (color) => {
    const map = {
        red:    'border-l-red-500',
        green:  'border-l-green-500',
        yellow: 'border-l-yellow-500',
        blue:   'border-l-blue-500',
        gray:   'border-l-gray-400',
    };
    return map[color] ?? 'border-l-gray-400';
};

const badgeBgClass = (color) => {
    const map = {
        red:    'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        green:  'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        blue:   'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        gray:   'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    };
    return map[color] ?? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
};

// ─── Filter navigation ─────────────────────────────────────────────
const setFilter = (key) => {
    router.get(route('notifications.index'), { filter: key }, { preserveScroll: true });
};

// ─── Mark one read ─────────────────────────────────────────────────
const markAsRead = async (notification) => {
    if (notification.read) return;
    try {
        await window.axios.post(`/notifications/${notification.id}/read`);
        notification.read = true;
        if (props.filter === 'unread') {
            const idx = props.notifications.data.indexOf(notification);
            if (idx > -1) props.notifications.data.splice(idx, 1);
        }
    } catch (e) {
        console.error('Failed to mark read', e);
    }
};

// ─── Mark one read & navigate ──────────────────────────────────────
const readAndNavigate = async (notification) => {
    if (!notification.read) {
        try {
            await window.axios.post(`/notifications/${notification.id}/read`);
            notification.read = true;
        } catch (e) {
            console.error('Failed to mark read before navigate', e);
        }
    }
    if (notification.action_url) {
        router.get(notification.action_url);
    }
};

// ─── Mark all read ─────────────────────────────────────────────────
const markAllAsRead = async () => {
    try {
        await window.axios.post('/notifications/read-all');
        if (props.filter === 'unread') {
            props.notifications.data.splice(0, props.notifications.data.length);
        } else {
            props.notifications.data.forEach(n => { n.read = true; });
        }
    } catch (e) {
        console.error('Failed to mark all read', e);
    }
};

// ─── Delete one ────────────────────────────────────────────────────
const deleteNotification = async (notification) => {
    try {
        await window.axios.delete(`/notifications/${notification.id}`);
        const idx = props.notifications.data.indexOf(notification);
        if (idx > -1) props.notifications.data.splice(idx, 1);
    } catch (e) {
        console.error('Failed to delete notification', e);
    }
};

// ─── Delete all read ───────────────────────────────────────────────
const deleteAllRead = async () => {
    const readItems = props.notifications.data.filter(n => n.read);
    await Promise.all(readItems.map(n => window.axios.delete(`/notifications/${n.id}`)));
    props.notifications.data = props.notifications.data.filter(n => !n.read);
};

const hasUnread = computed(() => props.notifications.data.some(n => !n.read));
const hasRead   = computed(() => props.notifications.data.some(n =>  n.read));
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-brand-900 dark:text-white">
                        System <span class="text-brand-600 dark:text-primary font-display">Notifications</span>
                    </h2>
                    <p class="text-brand-500 dark:text-brand-400 mt-1 text-sm">
                        Review alerts, required actions, and system updates.
                    </p>
                </div>

                <!-- Bulk Toolbar -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="hasUnread"
                        @click="markAllAsRead"
                        class="flex items-center gap-1.5 px-4 py-2 bg-primary/10 hover:bg-primary/20 text-primary dark:text-primary rounded-xl text-[11px] font-black uppercase tracking-widest transition-all"
                    >
                        <Check class="w-3.5 h-3.5" /> Mark All Read
                    </button>
                    <button
                        v-if="hasRead"
                        @click="deleteAllRead"
                        class="flex items-center gap-1.5 px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all"
                    >
                        <Trash2 class="w-3.5 h-3.5" /> Delete Read
                    </button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-1 p-1 bg-brand-50 dark:bg-brand-800/40 rounded-2xl overflow-x-auto">
                <button
                    v-for="tab in filterTabs"
                    :key="tab.key"
                    @click="setFilter(tab.key)"
                    class="flex-shrink-0 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all"
                    :class="filter === tab.key
                        ? 'bg-white dark:bg-brand-900 text-primary shadow-sm'
                        : 'text-brand-500 dark:text-brand-400 hover:text-brand-900 dark:hover:text-white'"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.key === 'unread' && unreadCount > 0"
                        class="ml-1 px-1.5 py-0.5 bg-primary text-white text-[9px] rounded-full"
                    >
                        {{ unreadCount }}
                    </span>
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="notifications.data.length === 0" class="card p-16 text-center flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-brand-50 dark:bg-brand-800/50 rounded-[2rem] flex items-center justify-center mb-6 ring-8 ring-brand-50/50 dark:ring-brand-800/30">
                    <Bell class="w-10 h-10 text-brand-300 dark:text-brand-600" />
                </div>
                <h3 class="text-xl font-bold text-brand-900 dark:text-white mb-2">Inbox Zero</h3>
                <p class="text-brand-500 dark:text-brand-400 text-sm">
                    No notifications in this category. You're all caught up!
                </p>
            </div>

            <!-- Notification Cards -->
            <div v-else class="card overflow-hidden">
                <div class="divide-y divide-brand-100 dark:divide-brand-800/50">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        class="flex items-start gap-0 border-l-4 transition-colors group"
                        :class="[
                            borderColorClass(notification.color),
                            !notification.read
                                ? 'bg-primary/[0.04] dark:bg-primary/[0.08] hover:bg-primary/[0.08] dark:hover:bg-primary/[0.14]'
                                : 'bg-white dark:bg-brand-900 hover:bg-brand-50/80 dark:hover:bg-brand-800/30'
                        ]"
                    >
                        <div class="flex-1 p-5 min-w-0">
                            <div class="flex items-start gap-3">

                                <!-- Color Badge -->
                                <div :class="['w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-xs font-black', badgeBgClass(notification.color)]">
                                    {{ notification.type?.charAt(0).toUpperCase() ?? '?' }}
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1">
                                        <h4
                                            class="text-sm tracking-tight"
                                            :class="!notification.read ? 'font-black text-primary dark:text-primary' : 'font-bold text-brand-900 dark:text-white'"
                                        >
                                            {{ notification.title }}
                                        </h4>
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest text-brand-400 whitespace-nowrap"
                                            :title="notification.created_at_full"
                                        >
                                            {{ notification.created_at }}
                                        </span>
                                    </div>

                                    <!-- Priority badge -->
                                    <span
                                        v-if="notification.priority === 'high' || notification.priority === 'critical'"
                                        class="inline-block mb-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-widest"
                                        :class="notification.priority === 'critical' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'"
                                    >
                                        {{ notification.priority }}
                                    </span>

                                    <p class="text-xs text-brand-600 dark:text-brand-300 leading-relaxed max-w-2xl">
                                        {{ notification.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Column -->
                        <div class="flex flex-col gap-1 p-3 shrink-0 items-end justify-center">
                            <!-- View Button -->
                            <button
                                v-if="notification.action_url"
                                @click="readAndNavigate(notification)"
                                class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                                :class="!notification.read
                                    ? 'text-primary hover:bg-primary/10'
                                    : 'text-brand-500 hover:bg-brand-100 dark:hover:bg-brand-800 dark:text-brand-400'"
                            >
                                View <ExternalLink class="w-3 h-3" />
                            </button>

                            <!-- Mark Read (if unread) -->
                            <button
                                v-if="!notification.read"
                                @click="markAsRead(notification)"
                                class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-brand-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all"
                            >
                                <Check class="w-3 h-3" />
                            </button>

                            <!-- Delete -->
                            <button
                                @click="deleteNotification(notification)"
                                class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-brand-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                            >
                                <Trash2 class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="notifications.last_page > 1"
                    class="p-5 bg-brand-50/30 dark:bg-brand-800/20 border-t border-brand-100 dark:border-brand-800 flex items-center justify-between"
                >
                    <p class="text-[10px] font-bold text-brand-400 uppercase tracking-widest">
                        {{ notifications.from ?? 0 }}–{{ notifications.to ?? 0 }} of {{ notifications.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="notifications.prev_page_url"
                            :href="notifications.prev_page_url + (filter !== 'all' ? '&filter=' + filter : '')"
                            class="flex items-center gap-1 px-3 py-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-brand-700 transition-colors shadow-sm"
                        >
                            <ChevronLeft class="w-3 h-3" /> Prev
                        </Link>
                        <Link
                            v-if="notifications.next_page_url"
                            :href="notifications.next_page_url + (filter !== 'all' ? '&filter=' + filter : '')"
                            class="flex items-center gap-1 px-3 py-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-brand-700 transition-colors shadow-sm"
                        >
                            Next <ChevronRight class="w-3 h-3" />
                        </Link>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
