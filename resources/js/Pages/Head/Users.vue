<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserPlus, 
    Shield, 
    UserCheck, 
    UserX, 
    Search,
    Edit3,
    Trash2,
    X,
    Lock,
    PowerOff
} from 'lucide-vue-next';
import { Head } from '@inertiajs/vue3';
import ExportDropdown from '@/Components/Common/ExportDropdown.vue';

const props = defineProps({
    users: Array,
    roles: Array,
    filters: Object
});

const isUserModalOpen = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    is_active: true,
    two_factor_enabled: false,
});

import { watch } from 'vue';
import { debounce } from 'lodash';
import { Filter } from 'lucide-vue-next';

const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const statusFilter = ref(props.filters?.status || '');
const isFiltersOpen = ref(false);

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: search.value,
        role: roleFilter.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true
    });
};

const clearFilters = () => {
    roleFilter.value = '';
    statusFilter.value = '';
    applyFilters();
    isFiltersOpen.value = false;
};

watch(search, debounce(() => {
    applyFilters();
}, 300));

// Action methods removed for Head view
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-brand-900 dark:text-white">User <span class="text-brand-600 dark:text-primary font-display">Management</span></h2>
                    <p class="text-brand-500 dark:text-brand-400 mt-1">Control system access and assign authority levels.</p>
                </div>
                
                <div class="flex items-center space-x-3 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-brand-900 dark:text-brand-300 dark:bg-brand-800 group-focus-within:text-brand-600 transition-colors" />
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Find user or email..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-200 rounded-xl text-sm focus:ring-2 dark:text-brand-300 dark:bg-brand-800 dark:border-brand-500 focus:ring-brand-500/20 transition-all shadow-sm"
                        />
                    </div>
                
                    <ExportDropdown 
                        :pdf-url="route('head.users.export.pdf')"
                        :excel-url="route('head.users.export.excel')"
                        :filters="{ search, role: roleFilter, status: statusFilter }"
                    />

                    <div class="relative">
                        <button @click="isFiltersOpen = !isFiltersOpen" class="px-4 py-2.5 bg-white border border-brand-200 rounded-xl text-xs font-bold uppercase tracking-widest dark:text-brand-300 dark:bg-brand-800 dark:border-brand-500 text-brand-600 flex items-center shadow-sm hover:bg-brand-50 transition-colors">
                            <Filter class="w-4 h-4 md:mr-2" /> <span class="hidden md:inline">Filters</span>
                            <span v-if="roleFilter || statusFilter" class="ml-2 w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        </button>
                        
                        <!-- Filter Dropdown -->
                        <div v-show="isFiltersOpen" class="absolute right-0 top-[110%] w-72 bg-white border border-brand-200 rounded-2xl shadow-xl p-5 z-50 animate-bounce-in origin-top-right">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-[10px] font-black text-brand-900 uppercase tracking-widest">Filter Users</h4>
                                <button @click="isFiltersOpen = false" class="text-brand-400 hover:text-danger p-1"><X class="w-4 h-4" /></button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[9px] font-black text-brand-400 uppercase tracking-widest mb-1.5">Authority Role</label>
                                    <select v-model="roleFilter" @change="applyFilters" class="w-full px-3 py-2 bg-brand-50 border-none rounded-lg text-xs font-bold text-brand-900 focus:ring-2 focus:ring-brand-500 transition-all">
                                        <option value="">All Roles</option>
                                        <option v-for="role in props.roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-brand-400 uppercase tracking-widest mb-1.5">Account Status</label>
                                    <select v-model="statusFilter" @change="applyFilters" class="w-full px-3 py-2 bg-brand-50 border-none rounded-lg text-xs font-bold text-brand-900 focus:ring-2 focus:ring-brand-500 transition-all">
                                        <option value="">All Statuses</option>
                                        <option value="active">Authorized</option>
                                        <option value="inactive">Deactivated</option>
                                    </select>
                                </div>
                                <div class="pt-2 flex items-center justify-between">
                                    <button @click="clearFilters" class="text-[9px] font-black text-brand-400 hover:text-danger uppercase tracking-widest transition-colors">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>

<!-- Provisioning hidden for head -->
                </div>
            </div>

            <div class="card overflow-hidden">
                <table class="w-full text-left">
                    <thead class="text-[10px] uppercase tracking-widest text-brand-500 dark:text-brand-400 bg-brand-50/50 dark:bg-brand-800/30 border-b border-brand-100 dark:border-brand-800 font-bold">
                        <tr>
                            <th class="px-6 py-4">User Identity</th>
                            <th class="px-6 py-4">Authority Level</th>
                            <th class="px-6 py-4">System Access</th>
                            <th class="px-6 py-4">Connectivity</th>
                            <th class="px-6 py-4 hidden"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-100 dark:divide-brand-800 bg-white dark:bg-brand-900">
                        <tr v-for="user in props.users" :key="user.id" class="hover:bg-brand-50/30 dark:hover:bg-brand-800/20 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-800 flex items-center justify-center font-bold text-brand-600 dark:text-brand-200 mr-3 ring-2 ring-white dark:ring-brand-800 shadow-sm">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-brand-900 dark:text-white">{{ user.name }}</div>
                                        <div class="text-xs text-brand-400 dark:text-brand-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge bg-brand-50 dark:bg-brand-800 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-700 uppercase font-bold text-[10px]">
                                    {{ user.roles[0]?.name || 'No Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div :class="['w-2 h-2 rounded-full mr-2', user.is_active ? 'bg-emerald-500' : 'bg-rose-500']"></div>
                                    <span class="text-xs font-bold uppercase tracking-tighter" :class="user.is_active ? 'text-emerald-700' : 'text-rose-700'">
                                        {{ user.is_active ? 'Authorized' : 'Deactivated' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-[10px] text-brand-400 dark:text-brand-500 font-bold uppercase tracking-tighter">Last Login: {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}</p>
                                <p class="text-[10px] text-brand-400 dark:text-brand-500 tracking-widest font-mono">{{ user.last_login_ip || '---.---.---.---' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right hidden">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

<!-- Modal removed for Head view -->
    </AuthenticatedLayout>
</template>
