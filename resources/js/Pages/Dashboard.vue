<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Users, Activity, ShieldAlert, FileText, Clock,
    CheckCircle, AlertCircle, ArrowUpRight, ExternalLink,
    UserPlus, Shield, ListFilter, Settings, UserMinus,
    MonitorSmartphone, TrendingUp, Calendar, Star
} from 'lucide-vue-next';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

// Navigate imperatively so query params are never stripped.
// Computed :href values were failing silently due to Inertia/Ziggy interactions.
const navigateFiltered = (params) => {
    const base = route('contracts.index');
    const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null))
    ).toString();
    window.location.href = qs ? `${base}?${qs}` : base;
};

const todayStr  = dayjs().format('YYYY-MM-DD');
const plus14Str = dayjs().add(14, 'day').format('YYYY-MM-DD');
const plus30Str = dayjs().add(30, 'day').format('YYYY-MM-DD');
const plus31Str = dayjs().add(31, 'day').format('YYYY-MM-DD');

const props = defineProps({
    stats:              Object,
    // Admin props
    recent_logins:      Array,
    deactivated_users:  Array,
    // Head props
    recently_submitted:  Array,
    recently_approved:   Array,
    expiry_timeline:     Array,
    high_priority:       Array,
    pending_updates:     Array,
    recent_activity:     Array,
    // Officer props
    my_recent:           Array,
    expiring_14:         Array,
    expiring_31:         Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.roles?.[0] ?? '');

function fmt(date) {
    return date ? dayjs(date).format('D MMM YYYY') : '—';
}

// Status color map
const statusColor = {
    draft:            'bg-gray-100 text-gray-600',
    pending_approval: 'bg-amber-100 text-amber-700',
    active:           'bg-emerald-100 text-emerald-700',
    terminated:       'bg-slate-200 text-slate-600',
    closed:           'bg-slate-200 text-slate-600',
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <!-- ══════════════════════════════════════════
             PAGE HEADER
        ══════════════════════════════════════════ -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-display font-black text-brand-900 dark:text-white tracking-tight">
                        Command <span class="text-primary italic">Center</span>
                    </h2>
                    <p class="text-brand-500 dark:text-brand-400 font-medium mt-1">
                        Welcome back, <span class="text-brand-900 dark:text-brand-200 font-bold underline decoration-primary decoration-2 underline-offset-4">{{ user.name }}</span>.
                        <span class="text-primary font-black uppercase text-[10px] tracking-widest ml-2">{{ role }}</span>
                    </p>
                </div>
                <div class="px-4 py-2 bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-2xl shadow-sm flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-[10px] font-black text-brand-500 dark:text-brand-400 uppercase tracking-widest">System Online</span>
                </div>
            </div>
        </template>

        <div class="space-y-8">

            <!-- ══════════════════════════════════════════
                 ADMIN DASHBOARD
            ══════════════════════════════════════════ -->
            <template v-if="role === 'Admin'">
                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">

                    <!-- Total Users -->
                    <Link :href="route('admin.users.index')"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-blue-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/60 transition-colors">
                                    <Users class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400">Total Users</p>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-brand-300 group-hover:text-blue-500 transition-colors" />
                        </div>
                        <p class="text-4xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.total_users ?? 0 }}</p>
                    </Link>

                    <!-- Active Users -->
                    <Link :href="route('admin.users.index')"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-emerald-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/60 transition-colors">
                                    <CheckCircle class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400">Active Users</p>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-brand-300 group-hover:text-emerald-500 transition-colors" />
                        </div>
                        <p class="text-4xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.active_users ?? 0 }}</p>
                    </Link>

                    <!-- Active Sessions -->
                    <Link :href="route('admin.logs.index')"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-sky-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center group-hover:bg-sky-200 dark:group-hover:bg-sky-800/60 transition-colors">
                                    <MonitorSmartphone class="w-4.5 h-4.5 text-sky-600 dark:text-sky-400" />
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400">Active Sessions</p>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-brand-300 group-hover:text-sky-500 transition-colors" />
                        </div>
                        <p class="text-4xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.active_sessions ?? 0 }}</p>
                    </Link>

                    <!-- Failed Logins -->
                    <Link :href="route('admin.logs.index', { search: 'failed_login' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-red-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center group-hover:bg-red-200 dark:group-hover:bg-red-800/60 transition-colors">
                                    <ShieldAlert class="w-4.5 h-4.5 text-red-600 dark:text-red-400" />
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400">Failed Logins (24h)</p>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-brand-300 group-hover:text-red-500 transition-colors" />
                        </div>
                        <p class="text-4xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.failed_logins ?? 0 }}</p>
                    </Link>

                </div>

                <!-- Recent Logins + Deactivated -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                            <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                                <MonitorSmartphone class="w-5 h-5 text-primary" /> Recent Authentication
                            </h3>
                            <Link :href="route('admin.logs.index', { search: 'login' })" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View Logs</Link>
                        </div>
                        <div class="p-2 divide-y divide-brand-50 dark:divide-brand-800/50">
                            <div v-if="!recent_logins?.length" class="py-12 text-center text-brand-400 text-sm italic font-medium">No recent logins.</div>
                            <div v-for="log in recent_logins" :key="log.id" class="p-5 hover:bg-brand-50 dark:hover:bg-brand-800/30 transition-colors rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-brand-100 dark:bg-brand-800 flex items-center justify-center text-xs font-black text-brand-600">
                                        {{ log.user?.name?.charAt(0) ?? 'S' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-brand-900 dark:text-white">{{ log.user?.name ?? 'System' }}</p>
                                        <p class="text-[10px] text-brand-400 font-bold uppercase">{{ log.ip_address }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-black text-brand-300">{{ dayjs(log.created_at).fromNow() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                            <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                                <UserMinus class="w-5 h-5 text-red-500" /> Deactivated Users
                            </h3>
                            <Link :href="route('admin.users.index')" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">Directory</Link>
                        </div>
                        <div class="p-2 divide-y divide-brand-50 dark:divide-brand-800/50">
                            <div v-if="!deactivated_users?.length" class="py-12 text-center text-brand-400 text-sm italic font-medium">All users are active.</div>
                            <div v-for="u in deactivated_users" :key="u.id" class="p-5 hover:bg-brand-50 dark:hover:bg-brand-800/30 transition-colors rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center text-xs font-black text-red-600">
                                        {{ u.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-brand-900 dark:text-white">{{ u.name }}</p>
                                        <p class="text-[10px] text-brand-400 font-bold">{{ u.email }}</p>
                                    </div>
                                </div>
                                <span class="text-[9px] font-black text-red-500 uppercase">Deactivated</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Controls -->
                <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] p-8 shadow-sm">
                    <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2 mb-6">
                        <Settings class="w-5 h-5 text-primary" /> System Controls
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link :href="route('admin.users.index')" class="p-4 bg-brand-50 dark:bg-brand-800/50 rounded-2xl hover:bg-primary/10 hover:text-primary transition-all group text-center border border-transparent hover:border-primary/20">
                            <UserPlus class="w-6 h-6 mx-auto mb-2 text-brand-400 group-hover:text-primary transition-colors" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Manage Users</span>
                        </Link>
                        <Link :href="route('admin.users.index')" class="p-4 bg-brand-50 dark:bg-brand-800/50 rounded-2xl hover:bg-primary/10 hover:text-primary transition-all group text-center border border-transparent hover:border-primary/20">
                            <Shield class="w-6 h-6 mx-auto mb-2 text-brand-400 group-hover:text-primary transition-colors" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Role Management</span>
                        </Link>
                        <Link :href="route('admin.logs.index')" class="p-4 bg-brand-50 dark:bg-brand-800/50 rounded-2xl hover:bg-primary/10 hover:text-primary transition-all group text-center border border-transparent hover:border-primary/20">
                            <Activity class="w-6 h-6 mx-auto mb-2 text-brand-400 group-hover:text-primary transition-colors" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Audit Logs</span>
                        </Link>
                        <Link :href="route('admin.logs.index')" class="p-4 bg-brand-50 dark:bg-brand-800/50 rounded-2xl hover:bg-primary/10 hover:text-primary transition-all group text-center border border-transparent hover:border-primary/20">
                            <MonitorSmartphone class="w-6 h-6 mx-auto mb-2 text-brand-400 group-hover:text-primary transition-colors" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Sessions</span>
                        </Link>
                    </div>
                </div>
            </template>

            <!-- ══════════════════════════════════════════
                 HEAD DASHBOARD
            ══════════════════════════════════════════ -->
            <template v-else-if="role === 'Head'">
                <!-- Head Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

                    <!-- Pending Approvals -->
                    <Link :href="route('contracts.index', { status: 'pending_approval' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-amber-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-800/60 transition-colors shrink-0">
                                    <Clock class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Pending Approvals</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-amber-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.pending_approvals ?? 0 }}</p>
                    </Link>

                    <!-- Active Contracts -->
                    <Link :href="route('contracts.index', { status: 'active' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-emerald-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/60 transition-colors shrink-0">
                                    <CheckCircle class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Active Contracts</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-emerald-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.active_contracts ?? 0 }}</p>
                    </Link>

                    <!-- Expiring in 14 days -->
                    <div @click="navigateFiltered({ status: 'active', expiry_from: todayStr, expiry_to: plus14Str })" role="button"
                          class="cursor-pointer bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-orange-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center group-hover:bg-orange-200 dark:group-hover:bg-orange-800/60 transition-colors shrink-0">
                                    <AlertCircle class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Expiring in 14 days</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-orange-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.expiring_14_days ?? 0 }}</p>
                    </div>

                    <!-- Expiring in 31 days -->
                    <div @click="navigateFiltered({ status: 'active', expiry_from: todayStr, expiry_to: plus31Str })" role="button"
                          class="cursor-pointer bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-red-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center group-hover:bg-red-200 dark:group-hover:bg-red-800/60 transition-colors shrink-0">
                                    <Calendar class="w-3.5 h-3.5 text-red-600 dark:text-red-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Expiring in 31 days</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-red-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.expiring_31_days ?? 0 }}</p>
                    </div>

                    <!-- Pending Updates -->
                    <Link :href="route('contracts.index', { status: 'pending_update' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-blue-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/60 transition-colors shrink-0">
                                    <FileText class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Pending Updates</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-blue-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.pending_updates ?? 0 }}</p>
                    </Link>

                </div>

                <!-- 2-col: Pending Reviews + Expiry Timeline -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Pending Approvals Queue -->
                    <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                            <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                                <Clock class="w-5 h-5 text-blue-400" /> Awaiting Your Decision
                            </h3>
                            <Link :href="route('contracts.index', { status: 'pending_approval' })" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View All</Link>
                        </div>
                        <div class="p-2">
                            <div v-if="!recently_submitted?.length" class="py-12 text-center text-brand-400 text-sm italic font-medium">
                                <CheckCircle class="w-10 h-10 mx-auto mb-3 opacity-20" />
                                No contracts awaiting approval.
                            </div>
                            <div v-for="c in recently_submitted" :key="c.id" class="flex items-center justify-between p-4 hover:bg-brand-50 dark:hover:bg-brand-800/30 rounded-2xl transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <FileText class="w-5 h-5 text-amber-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-brand-900 dark:text-white">{{ c.contract_name }}</p>
                                        <p class="text-[10px] text-brand-400 font-bold">{{ c.contract_number }} · by {{ c.creator?.name }}</p>
                                    </div>
                                </div>
                                <Link :href="route('contracts.show', c.id)" class="p-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl hover:text-primary hover:border-primary transition-all shadow-sm">
                                    <ArrowUpRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Expiry Timeline -->
                    <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                            <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                                <Calendar class="w-5 h-5 text-blue-400" /> Expiry Timeline (60 days)
                            </h3>
                            <Link :href="route('contracts.index')" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View All</Link>
                        </div>
                        <div class="p-2">
                            <div v-if="!expiry_timeline?.length" class="py-12 text-center text-brand-400 text-sm italic font-medium">No contracts expiring in 60 days.</div>
                            <div v-for="c in expiry_timeline" :key="c.id" class="flex items-center justify-between p-4 hover:bg-brand-50 dark:hover:bg-brand-800/30 rounded-2xl transition-colors">
                                <div>
                                    <p class="text-sm font-black text-brand-900 dark:text-white">{{ c.contract_name }}</p>
                                    <p class="text-[10px] text-orange-500 font-black uppercase">Expires {{ fmt(c.expiry_date) }}</p>
                                </div>
                                <Link :href="route('contracts.show', c.id)" class="p-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl hover:text-primary hover:border-primary transition-all shadow-sm">
                                    <ArrowUpRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recently Approved -->
                <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                        <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                            <CheckCircle class="w-5 h-5 text-emerald-500" /> Recently Approved
                        </h3>
                        <Link :href="route('contracts.index', { status: 'active' })" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View All</Link>
                    </div>
                    <div class="p-2 divide-y divide-brand-50 dark:divide-brand-800/50">
                        <div v-if="!recently_approved?.length" class="py-8 text-center text-brand-400 text-sm italic">No recently approved contracts.</div>
                        <div v-for="c in recently_approved" :key="c.id" class="flex items-center justify-between p-4 hover:bg-brand-50 dark:hover:bg-brand-800/30 rounded-2xl transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                    <CheckCircle class="w-4 h-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-brand-900 dark:text-white">{{ c.contract_name }}</p>
                                    <p class="text-[10px] text-brand-400 font-bold">Approved {{ fmt(c.approved_at) }}</p>
                                </div>
                            </div>
                            <Link :href="route('contracts.show', c.id)" class="p-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl hover:text-primary hover:border-primary transition-all shadow-sm">
                                <ArrowUpRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ══════════════════════════════════════════
                 OFFICER DASHBOARD
            ══════════════════════════════════════════ -->
            <template v-else-if="role === 'Officer'">
                <!-- Officer Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- My Drafts -->
                    <Link :href="route('contracts.index', { status: 'draft' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-blue-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/60 transition-colors shrink-0">
                                    <FileText class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">My Drafts</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-blue-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.my_drafts ?? 0 }}</p>
                    </Link>

                    <!-- Submitted -->
                    <Link :href="route('contracts.index', { status: 'pending_approval' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-amber-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-800/60 transition-colors shrink-0">
                                    <Clock class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Submitted</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-amber-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.submitted ?? 0 }}</p>
                    </Link>

                    <!-- My Active -->
                    <Link :href="route('contracts.index', { status: 'active' })"
                          class="bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-emerald-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/60 transition-colors shrink-0">
                                    <CheckCircle class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">My Active</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-emerald-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.active ?? 0 }}</p>
                    </Link>

                    <!-- Expiring Soon -->
                    <div @click="navigateFiltered({ status: 'active', expiry_from: todayStr, expiry_to: plus30Str })" role="button"
                          class="cursor-pointer bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl p-4 shadow-sm hover:shadow-lg hover:border-red-400 hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center group-hover:bg-red-200 dark:group-hover:bg-red-800/60 transition-colors shrink-0">
                                    <AlertCircle class="w-3.5 h-3.5 text-red-600 dark:text-red-400" />
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-500 dark:text-brand-400 leading-tight">Expiring Soon</p>
                            </div>
                            <ArrowUpRight class="w-3.5 h-3.5 text-brand-300 group-hover:text-red-500 transition-colors shrink-0" />
                        </div>
                        <p class="text-3xl font-black text-brand-500 dark:text-brand-400 tracking-tight">{{ stats?.expiring_soon ?? 0 }}</p>
                    </div>

                </div>

                <!-- My Recent Contracts + Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                            <h3 class="text-brand-800 dark:text-brand-500 flex items-center gap-2">
                                <Activity class="w-5 h-5 text-primary" /> My Recent Contracts
                            </h3>
                            <Link :href="route('contracts.index')" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View All</Link>
                        </div>
                        <div class="p-2">
                            <div v-if="!my_recent?.length" class="py-12 text-center text-brand-400 text-sm italic font-medium">
                                <FileText class="w-10 h-10 mx-auto mb-3 opacity-20" />
                                No contracts yet. <Link :href="route('contracts.create')" class="text-primary hover:underline font-bold">Create one →</Link>
                            </div>
                            <div v-for="c in my_recent" :key="c.id" class="flex items-center justify-between p-4 hover:bg-brand-50 dark:hover:bg-brand-800/30 rounded-2xl transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-brand-100 dark:bg-brand-800 flex items-center justify-center">
                                        <FileText class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-brand-900 dark:text-white">{{ c.contract_name }}</p>
                                        <p class="text-[10px] text-brand-400 font-bold">{{ c.contract_number }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="['text-[9px] font-black uppercase px-2 py-0.5 rounded-full', statusColor[c.status] ?? 'bg-gray-100 text-gray-600']">
                                        {{ c.status?.replace('_', ' ') }}
                                    </span>
                                    <Link :href="route('contracts.show', c.id)" class="p-1.5 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-lg hover:text-primary hover:border-primary transition-all shadow-sm">
                                        <ArrowUpRight class="w-3.5 h-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiring Contracts + Quick Actions -->
                    <div class="space-y-6">
                        <!-- Expiring in 14 days -->
                        <div v-if="expiring_14?.length" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-[2rem] p-6 shadow-sm">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-red-500 mb-4 flex items-center gap-2">
                                <AlertCircle class="w-4 h-4" /> Expiring in 14 days
                            </h4>
                            <div v-for="c in expiring_14" :key="c.id" class="mb-3">
                                <Link :href="route('contracts.show', c.id)" class="block text-sm font-bold text-red-700 dark:text-red-400 hover:underline truncate">
                                    {{ c.contract_name }}
                                </Link>
                                <p class="text-[10px] text-red-400">{{ fmt(c.expiry_date) }}</p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] p-6 shadow-sm">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-brand-800 mb-4">Quick Actions</h4>
                            <div class="space-y-3">
                                <Link :href="route('contracts.create')" class="flex items-center gap-3 p-3 bg-primary/5 border border-primary/20 rounded-xl hover:bg-primary/10 transition-all text-primary">
                                    <TrendingUp class="w-4 h-4 shrink-0" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">New Contract</span>
                                </Link>
                                <Link :href="route('contracts.index')" class="flex items-center gap-3 p-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-100 dark:border-brand-700 rounded-xl hover:bg-brand-100 transition-all text-brand-700 dark:text-brand-300">
                                    <FileText class="w-4 h-4 shrink-0" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">My Contracts</span>
                                </Link>
                                <Link :href="route('notifications.index')" class="flex items-center gap-3 p-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-100 dark:border-brand-700 rounded-xl hover:bg-brand-100 transition-all text-brand-700 dark:text-brand-300">
                                    <AlertCircle class="w-4 h-4 shrink-0" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">Notifications</span>
                                </Link>
            
                                <!--<Link :href="route('officer.logs.index')" class="flex items-center gap-3 p-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-100 dark:border-brand-700 rounded-xl hover:bg-brand-100 transition-all text-brand-700 dark:text-brand-300">
                                    <ListFilter class="w-4 h-4 shrink-0" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">View My Logs</span>
                                </Link> -->

                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn-primary-v2 { background: var(--color-brand-900); color: white; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; border-radius: 1rem; padding: 0.75rem 1.5rem; transition: all 0.2s; }
.dark .btn-primary-v2 { background: var(--color-primary); }
</style>
