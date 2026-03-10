<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { 
    Bell, 
    Moon, 
    Sun, 
    LogOut, 
    User,
    Menu,
    Search,
    X,
    Check,
    Loader2,
    FileText,
    History,
    Users,
    Activity,
    MonitorSmartphone,
    ShieldAlert
} from 'lucide-vue-next';

const emit = defineEmits(['openSidebar']);
const page = usePage();
const user = page.props.auth.user;

const isDark = ref(false);
const isAdmin = computed(() => user?.roles?.includes('Admin') ?? false);

const isNotificationsOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);

const toggleNotifications = () => {
    isNotificationsOpen.value = !isNotificationsOpen.value;
};

const getColorClass = (color) => {
    const map = {
        red:    'bg-red-500',
        green:  'bg-green-500',
        yellow: 'bg-yellow-500',
        blue:   'bg-blue-500',
        gray:   'bg-gray-400',
    };
    return map[color] ?? 'bg-gray-400';
};

// Lightweight 10-second poll — badge only
const fetchUnreadCount = async () => {
    try {
        const res = await fetch('/notifications/unread-count');
        if (res.ok) {
            const data = await res.json();
            unreadCount.value = data.count ?? 0;
        }
    } catch (e) { /* silent */ }
};

// Full 30-second poll — dropdown content
const fetchNotifications = async () => {
    try {
        const res = await fetch('/notifications/data?limit=5');
        if (res.ok) {
            const data = await res.json();
            notifications.value = data.notifications ?? [];
            unreadCount.value   = data.count ?? 0;
        }
    } catch (e) {
        console.error('Failed to fetch notifications', e);
    }
};

const clearNotifications = async () => {
    try {
        await window.axios.post('/notifications/read-all');
        notifications.value.forEach(n => n.read = true);
        unreadCount.value = 0;
        isNotificationsOpen.value = false;
    } catch (e) {
        console.error('Failed to mark all as read', e);
    }
};

const readNotification = async (notification) => {
    if (!notification.read) {
        try {
            await window.axios.post(`/notifications/${notification.id}/read`);
            notification.read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {
            console.error('Failed to mark notification as read', e);
        }
    }
    isNotificationsOpen.value = false;
    router.get(notification.url ?? notification.link ?? '/dashboard');
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('search')) {
        globalSearch.value = params.get('search');
    }

    // Initial fetch
    fetchNotifications();
    isDark.value = document.documentElement.classList.contains('dark');
    document.addEventListener('click', closeDropdown);
    document.addEventListener('keydown', handleKeyDown);

    // Lightweight 10s badge poll
    const badgePoll = setInterval(fetchUnreadCount, 10_000);
    // Full 30s dropdown poll
    const dropdownPoll = setInterval(fetchNotifications, 30_000);

    // Clean up intervals on unmount (stored via onUnmounted below)
    window.__notifBadgePoll    = badgePoll;
    window.__notifDropdownPoll = dropdownPoll;
});

import { onUnmounted, watch } from 'vue';
import { debounce } from 'lodash';
import LiveSearch from '@/Components/Search/LiveSearch.vue';

const closeDropdown = (e) => {
    if (!e.target.closest('.notification-container')) {
        isNotificationsOpen.value = false;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        isNotificationsOpen.value = false;
    }
};

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
    document.removeEventListener('keydown', handleKeyDown);
    clearInterval(window.__notifBadgePoll);
    clearInterval(window.__notifDropdownPoll);
});

const toggleDark = () => {
    const root = document.documentElement;
    if (root.classList.contains('dark')) {
        root.classList.remove('dark');
        localStorage.theme = 'light';
        isDark.value = false;
    } else {
        root.classList.add('dark');
        localStorage.theme = 'dark';
        isDark.value = true;
    }
};

const globalSearch = ref('');
const isProfileOpen = ref(false);

</script>

<template>
    <header class="h-20 bg-white/80 dark:bg-bg-dark/80 backdrop-blur-xl border-b border-brand-200 dark:border-brand-800 sticky top-0 z-40 px-6 lg:px-10 flex items-center justify-between transition-colors duration-300">
        <!-- Sidebar Toggle (Mobile) -->
        <button @click="emit('openSidebar')" class="lg:hidden p-3 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800 rounded-2xl group transition-all">
            <Menu class="w-6 h-6 group-active:scale-90" />
        </button>
        
        <!-- Search Command Center (hidden for Admin — Admin does not search contracts) -->
        <div v-if="!isAdmin" class="flex-1 max-w-xl mx-8 hidden sm:block">
            <LiveSearch 
                v-model="globalSearch"
                placeholder="Search across contracts, users, and audit logs..."
            />
        </div>

        <!-- Admin Quick Nav-->
        <div v-if="isAdmin" class="flex items-center gap-1 hidden sm:flex">
            <Link
                :href="route('admin.users.index')"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl text-brand-500 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800 hover:text-sky-700 dark:hover:text-sky-400 transition-all group"
            >
                <Users class="w-4 h-4 group-hover:scale-110 transition-transform" />
                <span class="text-[11px] font-black uppercase tracking-widest">Users</span>
            </Link>
            <Link
                :href="route('admin.logs.index')"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl text-brand-500 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800 hover:text-sky-700 dark:hover:text-sky-400 transition-all group"
            >
                <Activity class="w-4 h-4 group-hover:scale-110 transition-transform" />
                <span class="text-[11px] font-black uppercase tracking-widest">Audit Logs</span>
            </Link>
            <Link
                :href="route('admin.logs.index')"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl text-brand-500 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800 hover:text-sky-700 dark:hover:text-sky-400 transition-all group"
            >
                <MonitorSmartphone class="w-4 h-4 group-hover:scale-110 transition-transform" />
                <span class="text-[11px] font-black uppercase tracking-widest">Sessions</span>
            </Link>
            <Link
                :href="route('admin.logs.index', { search: 'failed_login' })"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl text-brand-500 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800 hover:text-sky-700 dark:hover:text-sky-400 transition-all group"
            >
                <ShieldAlert class="w-4 h-4 group-hover:scale-110 transition-transform" />
                <span class="text-[11px] font-black uppercase tracking-widest">Failed Logins</span>
            </Link>
        </div>

        <!-- Global Actions -->
        <div class="flex items-center space-x-3">
            <!-- Theme Toggle -->
            <button 
                @click="toggleDark" 
                class="p-3 text-brand-500 hover:text-primary hover:bg-brand-50 dark:hover:bg-brand-800 rounded-2xl transition-all relative overflow-hidden group"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
                <div class="relative w-5 h-5">
                    <Sun :class="['w-5 h-5 absolute inset-0 transition-transform duration-500', isDark ? 'rotate-0 scale-100' : '-rotate-90 scale-0']" />
                    <Moon :class="['w-5 h-5 absolute inset-0 transition-transform duration-500', isDark ? 'rotate-90 scale-0' : 'rotate-0 scale-100']" />
                </div>
            </button>

            <!-- Notifications -->
            <div class="relative notification-container">
                <button @click="toggleNotifications" class="p-3 text-brand-500 hover:text-primary hover:bg-brand-50 dark:hover:bg-brand-800 rounded-2xl transition-all relative group">
                    <Bell class="w-5 h-5" />
                    <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[9px] font-black text-white bg-danger rounded-full ring-2 ring-white dark:ring-bg-dark shadow-sm">
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                </button>

                <!-- Notifications Dropdown -->
                <transition 
                    enter-active-class="transition ease-out duration-200" 
                    enter-from-class="opacity-0 translate-y-1 scale-95" 
                    enter-to-class="opacity-100 translate-y-0 scale-100" 
                    leave-active-class="transition ease-in duration-150" 
                    leave-from-class="opacity-100 translate-y-0 scale-100" 
                    leave-to-class="opacity-0 translate-y-1 scale-95"
                >
                    <div v-show="isNotificationsOpen" class="absolute right-0 mt-3 w-80 lg:w-96 bg-white dark:bg-brand-900 rounded-[2rem] shadow-2xl border border-brand-100 dark:border-brand-800 overflow-hidden z-50 origin-top-right">
                        <div class="p-5 flex items-center justify-between border-b border-brand-100 dark:border-brand-800 bg-brand-50/80 dark:bg-brand-800/30 backdrop-blur-md">
                            <h3 class="text-xs font-black uppercase tracking-widest text-brand-900 dark:text-white flex items-center">
                                <Bell class="w-4 h-4 mr-2 text-primary" /> Notifications
                            </h3>
                            <button v-if="unreadCount > 0" @click="clearNotifications" class="px-3 py-1.5 bg-brand-100/50 dark:bg-brand-800/50 hover:bg-danger/10 hover:text-danger text-[10px] font-black text-brand-500 rounded-xl transition-all uppercase tracking-wider">
                                Clear All
                            </button>
                        </div>
                        <div class="max-h-[28rem] overflow-y-auto custom-scrollbar">
                            <div v-if="notifications.length === 0" class="p-12 text-center flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-brand-50 dark:bg-brand-800/50 rounded-full flex items-center justify-center mb-4 ring-4 ring-white dark:ring-bg-dark shadow-inner">
                                    <Check class="w-8 h-8 text-brand-300 dark:text-brand-600" />
                                </div>
                                <h4 class="text-sm font-black text-brand-900 dark:text-white mb-1">You're all caught up!</h4>
                                <p class="text-[10px] font-bold text-brand-500 uppercase tracking-widest">No new notifications</p>
                            </div>
                            <div v-else class="divide-y divide-brand-100 dark:divide-brand-800/50">
                                <button 
                                    v-for="notification in notifications" 
                                    :key="notification.id"
                                    @click="readNotification(notification)"
                                    class="w-full text-left p-5 hover:bg-brand-50/80 dark:hover:bg-brand-800/30 transition-all group flex relative cursor-pointer"
                                    :class="!notification.read ? 'bg-primary/5 dark:bg-primary/10' : ''"
                                >
                                    <!-- Color indicator dot -->
                                    <div class="shrink-0 mr-3 mt-1">
                                        <div :class="['w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-bg-dark', getColorClass(notification.color)]" />
                                    </div>
                                    <div class="flex-1 min-w-0 pr-6">
                                        <div class="flex items-start justify-between mb-1.5">
                                            <p class="text-[11px] font-black text-brand-900 dark:text-white leading-tight" :class="!notification.read ? 'text-primary' : ''">{{ notification.title }}</p>
                                            <span class="text-[9px] font-black text-brand-400 uppercase whitespace-nowrap ml-3 tracking-wider">{{ notification.created_at }}</span>
                                        </div>
                                        <p class="text-[10px] text-brand-500 dark:text-brand-400 font-medium leading-relaxed line-clamp-2">{{ notification.message }}</p>
                                    </div>
                                    <div v-if="!notification.read" class="absolute right-5 top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full bg-primary ring-4 ring-primary/20 animate-pulse"></div>
                                </button>
                            </div>
                        </div>
                        <div class="p-3 bg-brand-50/50 dark:bg-brand-800/30 border-t border-brand-100 dark:border-brand-800 text-center">
                            <Link :href="route('notifications.index')" @click="isNotificationsOpen = false" class="text-[10px] font-black text-primary uppercase tracking-widest hover:text-brand-900 dark:hover:text-white transition-colors">
                                View Full Ledger &rarr;
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>

            <div class="h-8 w-px bg-brand-200 dark:bg-brand-800 mx-2"></div>

            <!-- ACTIVE STATE -->
            <div class="p-6 mt-auto">
                
                    <p class="text-[10px] font-black text-brand-400 uppercase tracking-widest mb-1">System Status</p>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
                        <span class="text-xs font-bold text-brand-900 dark:text-brand-200">Secure Node: Active</span>
                    </div>
                    <p class="text-[9px] font-black text-brand-300 dark:text-brand-600 uppercase tracking-widest truncate">
                        {{ user?.name }}
                        <span class="text-primary ml-1">· {{ user?.roles?.[0] }}</span>
                    </p>
                
            </div>
        </div>
    </header>
</template>
