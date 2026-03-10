<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard, FileText, Users, History, Bell,
    User, ChevronRight, X, ShieldCheck, Shield,
    MonitorSmartphone, Activity, Settings, LogOut
} from 'lucide-vue-next';

const props = defineProps({ isOpen: Boolean });
const emit  = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const isProfileOpen = ref(false);

// Safe role check helpers
const isAdmin   = computed(() => user.value?.roles?.includes('Admin')  ?? false);
const isHead    = computed(() => user.value?.roles?.includes('Head')   ?? false);
const isOfficer = computed(() => user.value?.roles?.includes('Officer') ?? false);

// Build navigation lazily inside a computed so route() is never called for
// routes the current user doesn't have access to.
const navigation = computed(() => {
    const items = [];

    // ── Shared ──────────────────────────────────────
    items.push({
        name:    'Dashboard',
        href:    route('dashboard'),
        icon:    LayoutDashboard,
        current: route().current('dashboard'),
    });
    items.push({
        name:    'Notifications',
        href:    route('notifications.index'),
        icon:    Bell,
        current: route().current('notifications.*'),
    });

    // ── Officer / Head: Contracts ────────────────────
    if (isOfficer.value || isHead.value) {
        items.push({
            name:    'Contracts',
            href:    route('contracts.index'),
            icon:    FileText,
            current: route().current('contracts.*'),
        });
    }

    // ── Head only ────────────────────────────────────
    if (isHead.value) {
        items.push({
            name:    'Users',
            href:    route('head.users.index'),
            icon:    Users,
            current: route().current('head.users.index'),
        });
        items.push({
            name:    'Audit Logs',
            href:    route('head.logs.index'),
            icon:    History,
            current: route().current('head.logs.*'),
        });
        items.push({
            name:    'Profile',
            href:    route('profile.edit'),
            icon:    User,
            current: route().current('profile.edit'),
        });
    }

    
    // ── Officer only ─────────────────────────────────
    if (isOfficer.value) {
        /*items.push({
            name:    'Audit Logs',
            href:    route('officer.logs.index'),
            icon:    History,
            current: route().current('officer.logs.*'),
        });*/
        items.push({
            name:    'Profile',
            href:    route('profile.edit'),
            icon:    User,
            current: route().current('profile.edit'),
        });
    }
    
    // ── Admin only ───────────────────────────────────
    if (isAdmin.value) {
        
        items.push({
            name:    'Audit Logs',
            href:    route('admin.logs.index'),
            icon:    History,
            current: route().current('admin.logs.*'),
        });
        items.push({
            name:    'System Settings',
            href:    route('admin.users.index'),
            icon:    Settings,
            current: route().current('admin.users.*'),
        });
        items.push({
            name:    'Profile',
            href:    route('profile.edit'),
            icon:    User,
            current: route().current('profile.edit'),
        });
    }

    return items;
});
</script>

<template>
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-bg-dark border-r border-brand-200 dark:border-brand-800 transition-transform duration-300 ease-in-out',
            props.isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
    >
        <div class="flex flex-col h-full">
            <!-- Strategic Logo Area -->
            <div class="p-8 pb-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-xl flex items-center justify-center shadow-lg shadow-black/5 overflow-hidden p-1.5">
                        <img src="/images/logo.png" alt="CMS Logo" class="w-full h-full object-contain" />
                    </div>
                    <div>
                        <h1 class="text-xl font-display font-black tracking-tight text-brand-900 dark:text-white uppercase italic">CM <span class="text-primary not-italic">system</span></h1>
                        <p class="text-[10px] font-bold tracking-[0.3em] text-brand-400 uppercase">- STANDARD -</p>
                    </div>
                </div>
                <button @click="emit('close')" class="lg:hidden p-2 text-brand-400 hover:text-brand-900 dark:hover:text-white transition-colors">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name + item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center px-4 py-3.5 rounded-2xl transition-all duration-200 group relative',
                        item.current
                            ? 'bg-primary/10 text-primary font-bold shadow-sm'
                            : 'text-brand-500 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-800/50 hover:text-brand-900 dark:hover:text-white'
                    ]"
                >
                    <div v-if="item.current" class="absolute left-0 w-1.5 h-6 bg-primary rounded-r-full"></div>
                    <component
                        :is="item.icon"
                        :class="['w-5 h-5 mr-3 transition-all duration-300', item.current ? 'text-primary scale-110' : 'text-brand-400 group-hover:text-brand-700 dark:group-hover:text-brand-300']"
                    />
                    <span>{{ item.name }}</span>
                    <ChevronRight v-if="item.current" class="ml-auto w-4 h-4 opacity-50" />
                </Link>
            </nav>

            <!-- Strategic User Profile -->
            <div class="mt-auto p-4 border-t border-brand-100 dark:border-brand-800 relative">
                <div class="flex items-center p-3 rounded-2xl bg-brand-50/50 dark:bg-brand-900/50 border border-brand-100 dark:border-brand-800 group hover:border-primary/30 transition-all duration-300">
                    <div class="relative flex-shrink-0 cursor-pointer" @click="isProfileOpen = !isProfileOpen">
                        <div 
                            class="w-11 h-11 rounded-xl flex items-center justify-center text-sm font-black text-white ring-2 ring-white dark:ring-brand-700 transition-all shadow-sm overflow-hidden group-hover:ring-primary/50" 
                            :style="!user.avatar_url ? `background-color: ${user.avatar_color}` : 'background-color: #F3F4F6'"
                        >
                            <img v-if="user.avatar_url" :src="user.avatar_url" alt="Avatar" class="w-full h-full object-cover" />
                            <span v-else>{{ user.initials }}</span>
                        </div>
                    </div>
                    
                    <div class="ml-3 min-w-0 flex-1 cursor-pointer" @click="isProfileOpen = !isProfileOpen">
                        <p class="text-sm font-bold text-brand-900 dark:text-white truncate">{{ user.name }}</p>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest leading-none mt-1">{{ user.roles?.[0] || 'User' }}</p>
                    </div>

                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="ml-2 p-2 text-brand-400 hover:text-danger hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all"
                        title="Logout"
                    >
                        <LogOut class="w-5 h-5" />
                    </Link>
                </div>

                <!-- Profile Dropdown Menu -->
                <transition 
                    enter-active-class="transition ease-out duration-200" 
                    enter-from-class="opacity-0 translate-y-2 scale-95" 
                    enter-to-class="opacity-100 translate-y-0 scale-100" 
                    leave-active-class="transition ease-in duration-150" 
                    leave-from-class="opacity-100 translate-y-0 scale-100" 
                    leave-to-class="opacity-0 translate-y-2 scale-95"
                >
                    <div v-show="isProfileOpen" class="absolute bottom-full left-4 mb-3 w-64 bg-white dark:bg-brand-900 rounded-2xl shadow-2xl border border-brand-100 dark:border-brand-800 overflow-hidden z-[60]">
                        <div class="p-4 border-b border-brand-50 dark:border-brand-800 bg-brand-50/30 dark:bg-brand-800/20">
                            <p class="text-xs font-bold text-brand-400 uppercase tracking-widest mb-1">Signed in as</p>
                            <p class="text-sm font-black text-brand-900 dark:text-white truncate">{{ user.email }}</p>
                        </div>
                        <div class="p-2">
                            <Link 
                                :href="route('profile.edit')" 
                                @click="isProfileOpen = false" 
                                class="flex items-center px-4 py-3 text-xs font-bold text-brand-600 dark:text-brand-300 hover:text-brand-900 dark:hover:text-white hover:bg-brand-100/50 dark:hover:bg-brand-800/50 rounded-xl transition-all group"
                            >
                                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center mr-3 group-hover:bg-primary group-hover:text-white transition-all">
                                    <User class="w-4 h-4" />
                                </div>
                                Profile Settings
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </aside>
</template>
