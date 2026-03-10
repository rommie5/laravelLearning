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
import LiveSearch from '@/Components/Search/LiveSearch.vue';
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

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    isUserModalOpen.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles[0]?.name || '';
    form.is_active = user.is_active;
    form.two_factor_enabled = user.two_factor_enabled || false;
    form.password = ''; // Don't prefill password
    isUserModalOpen.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => isUserModalOpen.value = false
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => isUserModalOpen.value = false
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('admin.users.destroy', id));
    }
};

const forceLogout = (id) => {
    if (confirm('Force logout this user until re-authorization?')) {
        router.post(route('admin.users.force-logout', id));
    }
};

const isAuthorized = (user) => user.is_active && !user.admin_force_logout;
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-brand-900 dark:text-white">User <span class="text-brand-600 dark:text-primary font-display">Management</span></h2>
                    <p class="text-brand-500 dark:text-brand-400 mt-1">Control system access and assign authority levels.</p>
                    <p class="text-brand-500 dark:text-brand-400 mt-1">
                        _______________________________________________
                    </p>
                  <div class="flex items-center space-x-3 w-full md:w-auto">
                    <div class="flex-1 md:w-64">
                        <LiveSearch
                            v-model="search"
                            type="user"
                            placeholder="Find user or email..."
                        />
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-3 w-full md:w-auto">
                    <ExportDropdown
                        :pdf-url="route('admin.users.export.pdf')"
                        :excel-url="route('admin.users.export.excel')"
                        :filters="{ search, role: roleFilter, status: statusFilter }"
                    />

                    <div class="relative">
                        <button @click="isFiltersOpen = !isFiltersOpen" class="px-4 py-2.5 bg-white border border-brand-200 rounded-xl text-xs font-bold uppercase tracking-widest text-brand-600 dark:bg-brand-800 dark:text-brand-300 dark:border-brand-700  flex items-center shadow-sm hover:bg-brand-50 transition-colors">
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

                    <button @click="openCreateModal" class="btn-primary flex dark:bg-brand-600 dark:text-brand-200 dark:border-brand-700 items-center shadow-lg shadow-brand-500/20 py-2.5">
                        <UserPlus class="w-4 h-4 mr-2" />
                        <span class="hidden md:inline">Assign New User</span>
                    </button>
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
                            <th class="px-6 py-4"></th>
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
                                    <div :class="['w-2 h-2 rounded-full mr-2', isAuthorized(user) ? 'bg-emerald-500' : 'bg-rose-500']"></div>
                                    <span class="text-xs font-bold uppercase tracking-tighter" :class="isAuthorized(user) ? 'text-emerald-700' : 'text-rose-700'">
                                        {{ isAuthorized(user) ? 'Authorized' : 'Unauthorized' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-[10px] text-brand-400 dark:text-brand-500 font-bold uppercase tracking-tighter">Last Login: {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}</p>
                                <p class="text-[10px] text-brand-400 dark:text-brand-500 tracking-widest font-mono">{{ user.last_login_ip || '---.---.---.---' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="forceLogout(user.id)" class="p-2 text-brand-400 hover:text-amber-500 transition-colors" title="Force Logout">
                                        <PowerOff class="w-4 h-4" />
                                    </button>
                                    <button @click="openEditModal(user)" class="p-2 text-brand-400 hover:text-brand-900 transition-colors" title="Edit User">
                                        <Edit3 class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteUser(user.id)" class="p-2 text-brand-400 hover:text-rose-600 transition-colors" title="Delete User">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <!-- Provisioning Modal -->
            <div v-if="isUserModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/60 dark:bg-black/80 backdrop-blur-md">
                <div class="bg-white dark:bg-brand-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-bounce-in">
                    <div class="p-8 border-b border-brand-100 dark:border-brand-800 flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-brand-900 dark:text-white">{{ editingUser ? 'Update Authority' : 'Provision User' }}</h3>
                        <button @click="isUserModalOpen = false" class="p-2 hover:bg-brand-100 dark:hover:bg-brand-800 rounded-xl transition-colors"><X class="w-6 h-6 text-brand-400 dark:text-brand-500" /></button>
                    </div>
                    <form @submit.prevent="submit" class="p-8 space-y-6">
                        <div v-if="Object.keys(form.errors).length" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
                            Please correct the highlighted fields and try again.
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Full Identity</label>
                            <input v-model="form.name" type="text" class="input-field" placeholder="Full legal name" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Corporate Email</label>
                            <input v-model="form.email" type="email" class="input-field" placeholder="name@company.com" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Security Role</label>
                            <select v-model="form.role" class="input-field">
                                <option value="">Select a role</option>
                                <option v-for="role in props.roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <p v-if="form.errors.role" class="mt-1 text-xs text-rose-600">{{ form.errors.role }}</p>
                        </div>
                        <div v-if="!editingUser">
                            <label class="block text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Initial Password</label>
                            <input v-model="form.password" type="password" class="input-field" />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="checkbox" v-model="form.is_active" class="w-5 h-5 rounded-lg border-brand-200 dark:border-brand-700 text-brand-600 focus:ring-brand-500 bg-white dark:bg-brand-800" />
                                    <span class="ml-3 text-sm font-bold text-brand-700 dark:text-brand-400 group-hover:text-brand-900 dark:group-hover:text-white transition-colors uppercase tracking-tight">System access enabled</span>
                                </label>
                            </div>
                            <p v-if="form.errors.is_active" class="mt-1 text-xs text-rose-600">{{ form.errors.is_active }}</p>
                            <div class="flex items-center space-x-3">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="checkbox" v-model="form.two_factor_enabled" class="w-5 h-5 rounded-lg border-brand-200 dark:border-brand-700 text-brand-600 focus:ring-brand-500 bg-white dark:bg-brand-800" />
                                    <span class="ml-3 text-sm font-bold text-brand-700 dark:text-brand-400 group-hover:text-brand-900 dark:group-hover:text-white transition-colors uppercase tracking-tight">Require Two-Factor Authentication</span>
                                </label>
                            </div>
                            <p v-if="form.errors.two_factor_enabled" class="mt-1 text-xs text-rose-600">{{ form.errors.two_factor_enabled }}</p>
                        </div>
                    </form>
                    <div class="p-8 bg-brand-50 dark:bg-brand-800/50 border-t border-brand-100 dark:border-brand-800 flex items-center justify-end space-x-4">
                        <button @click="isUserModalOpen = false" class="px-6 py-2.5 font-bold text-brand-600 dark:text-brand-400 hover:text-brand-900 dark:hover:text-white transition-colors">Cancel</button>
                        <button @click="submit" class="btn-primary">
                            {{ editingUser ? 'Apply Changes' : 'Create Account' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
