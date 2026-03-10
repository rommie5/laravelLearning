<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import {
    FileText, Download, Plus, Search, Filter,
    ShieldAlert, ArrowUpRight, X, ChevronRight
} from 'lucide-vue-next';
import LiveSearch from '@/Components/Search/LiveSearch.vue';
import ExportDropdown from '@/Components/Common/ExportDropdown.vue';
import dayjs from 'dayjs';

const props = defineProps({
    contracts: Object,
    filters:   Object,
});

const search         = ref(props.filters?.search   || '');
const statusFilter   = ref(props.filters?.status   || '');
const priorityFilter = ref(props.filters?.priority || '');
const expiryFrom     = ref(props.filters?.expiry_from || '');
const expiryTo       = ref(props.filters?.expiry_to   || '');
const isFiltersOpen  = ref(false);

const applyFilters = () => {
    router.get(route('contracts.index'), {
        search:      search.value,
        status:      statusFilter.value,
        priority:    priorityFilter.value,
        expiry_from: expiryFrom.value,
        expiry_to:   expiryTo.value,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    statusFilter.value   = '';
    priorityFilter.value = '';
    expiryFrom.value     = '';
    expiryTo.value       = '';
    applyFilters();
    isFiltersOpen.value = false;
};

watch(search, debounce(() => applyFilters(), 300));

// Status display helpers
const statusBadgeClass = {
    draft:            'bg-gray-100 text-gray-600 border-gray-200',
    pending_approval: 'bg-amber-100 text-amber-700 border-amber-200',
    active:           'bg-emerald-100 text-emerald-700 border-emerald-200',
    pending_update:   'bg-blue-100 text-blue-700 border-blue-200',
    terminated:       'bg-slate-200 text-slate-700 border-slate-300',
    closed:           'bg-slate-200 text-slate-600 border-slate-300',
};

const priorityBorderClass = {
    sensitive: 'border-l-red-500',
    high:      'border-l-orange-400',
    medium:    'border-l-amber-300',
    low:       'border-l-transparent',
};
</script>

<template>
    <Head title="Contract Registry" />

    <AuthenticatedLayout>
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-display font-black text-brand-900 dark:text-white tracking-tight uppercase">
                        Contract <span class="text-primary italic">Registry</span>
                    </h2>
                    <p class="text-brand-500 dark:text-brand-400 font-medium mt-1">
                        Authoritative ledger of all institutional commitments.
                    </p>
                </div>
                <Link :href="route('contracts.create')"
                      class="flex items-center gap-2 px-6 py-3 bg-primary text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all w-max">
                    <Plus class="w-4 h-4" /> New Contract
                </Link>
            </div>

            <!-- Search + Filters -->
            <div class="bg-white dark:bg-brand-900/50 border border-brand-200 dark:border-brand-800 p-5 rounded-3xl shadow-sm">
                <div class="flex flex-col lg:flex-row items-center gap-4">
                    <!-- Search -->
                    <div class="flex-1 w-full">
                        <LiveSearch 
                            v-model="search"
                            type="contract"
                            placeholder="Search by name, number, or vendor..."
                        />
                    </div>

                    <!-- Filter toggle -->
                    <div class="flex items-center gap-4 relative">
                        <ExportDropdown 
                            :pdf-url="route('contracts.export.pdf')"
                            :excel-url="route('contracts.export.excel')"
                            :filters="{ search, status: statusFilter, priority: priorityFilter, expiry_from: expiryFrom, expiry_to: expiryTo }"
                        />
                        
                        <button @click="isFiltersOpen = !isFiltersOpen"
                                class="flex items-center gap-2 px-5 py-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-100 dark:border-brand-700/50 rounded-2xl text-[10px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-700 transition-colors">
                            <Filter class="w-4 h-4" /> Filters
                            <span v-if="statusFilter || priorityFilter || expiryFrom || expiryTo" class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        </button>

                        <div v-show="isFiltersOpen"
                             class="absolute right-0 top-[110%] w-64 bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-3xl shadow-2xl p-5 z-50">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-[10px] font-black text-brand-900 dark:text-white uppercase tracking-widest">Filter</h4>
                                <button @click="isFiltersOpen = false" class="text-brand-400 hover:text-brand-700 p-1">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[9px] font-black text-brand-400 uppercase tracking-widest mb-1">Status</label>
                                    <select v-model="statusFilter" @change="applyFilters"
                                            class="w-full px-3 py-2 bg-brand-50 dark:bg-brand-800/50 border-none rounded-xl text-xs font-bold text-brand-900 dark:text-white focus:ring-2 focus:ring-primary transition-all">
                                        <option value="">All Statuses</option>
                                        <option value="draft">Draft</option>
                                        <option value="pending_approval">Pending Approval</option>
                                        <option value="active">Active</option>
                                        <option value="pending_update">Pending Update</option>
                                        <option value="terminated">Terminated</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-brand-400 uppercase tracking-widest mb-1">Priority</label>
                                    <select v-model="priorityFilter" @change="applyFilters"
                                            class="w-full px-3 py-2 bg-brand-50 dark:bg-brand-800/50 border-none rounded-xl text-xs font-bold text-brand-900 dark:text-white focus:ring-2 focus:ring-primary transition-all">
                                        <option value="">All Priorities</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="sensitive">Sensitive</option>
                                    </select>
                                </div>
                                <button @click="clearFilters" class="text-[9px] font-black text-brand-400 hover:text-red-500 uppercase tracking-widest transition-colors">
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-[2rem] overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-brand-50 dark:bg-brand-950/50 border-b border-brand-100 dark:border-brand-800">
                            <tr>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Status</th>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Contract Number</th>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Contract Name</th>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Awarded To</th>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Priority</th>
                                <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Expiry</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-50 dark:divide-brand-800/50">
                            <!-- Empty state -->
                            <tr v-if="!contracts?.data?.length">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <FileText class="w-12 h-12 mx-auto mb-4 text-brand-200 dark:text-brand-700" />
                                    <p class="text-sm font-semibold text-brand-400">No contracts found.</p>
                                    <Link :href="route('contracts.create')" class="mt-3 inline-block text-xs font-black text-primary uppercase tracking-widest hover:underline">
                                        Create the first one →
                                    </Link>
                                </td>
                            </tr>

                            <tr
                                v-for="c in contracts.data"
                                :key="c.id"
                                :class="[
                                    'group hover:bg-brand-50/50 dark:hover:bg-brand-800/20 transition-all cursor-pointer border-l-4',
                                    priorityBorderClass[c.priority_level] ?? 'border-l-transparent'
                                ]"
                                @click="router.visit(route('contracts.show', c.id))"
                            >
                                <td class="px-6 py-5">
                                    <span v-if="c.has_pending_update" :class="['text-[9px] font-black uppercase px-2.5 py-1 rounded-full border tracking-widest', statusBadgeClass['pending_update']]">
                                        PENDING UPDATE
                                    </span>
                                    <span v-else :class="['text-[9px] font-black uppercase px-2.5 py-1 rounded-full border tracking-widest', statusBadgeClass[c.status] ?? 'bg-gray-100 text-gray-600 border-gray-200']">
                                        {{ c.status?.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-xs font-mono font-black text-brand-700 dark:text-brand-300 uppercase">
                                    {{ c.contract_number }}
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-2">
                                        <ShieldAlert v-if="c.priority_level === 'sensitive'" class="w-3.5 h-3.5 text-red-500 shrink-0" />
                                        <span class="font-bold text-brand-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors text-sm">
                                            {{ c.contract_name }}
                                        </span>
                                    </div>
                                    <p class="text-[9px] text-brand-400 font-bold uppercase mt-0.5">by {{ c.creator?.name }}</p>
                                </td>
                                <td class="px-6 py-5 text-xs font-bold text-brand-600 dark:text-brand-400">
                                    {{ c.awarded_to }}
                                </td>
                                <td class="px-6 py-5">
                                    <span :class="[
                                        'text-[9px] font-black uppercase px-2 py-0.5 rounded-full tracking-widest',
                                        c.priority_level === 'sensitive' ? 'bg-red-100 text-red-700' :
                                        c.priority_level === 'high'      ? 'bg-orange-100 text-orange-700' :
                                        c.priority_level === 'medium'    ? 'bg-amber-100 text-amber-700' :
                                                                           'bg-blue-100 text-blue-700'
                                    ]">{{ c.priority_level }}</span>
                                </td>
                                <td class="px-6 py-5 text-xs text-brand-500 font-medium">
                                    {{ c.expiry_date ? dayjs(c.expiry_date).format('D MMM YYYY') : '—' }}
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="route('contracts.show', c.id)"
                                              class="p-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl hover:text-primary hover:border-primary transition-all shadow-sm"
                                              @click.stop>
                                            <ArrowUpRight class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="contracts?.last_page > 1" class="p-5 bg-brand-50/30 dark:bg-brand-800/20 border-t border-brand-100 dark:border-brand-800 flex items-center justify-between">
                    <p class="text-[10px] font-bold text-brand-400 uppercase tracking-widest">
                        Page {{ contracts.current_page }} of {{ contracts.last_page }}
                        · {{ contracts.total }} total
                    </p>
                    <div class="flex gap-2">
                        <Link v-if="contracts.prev_page_url" :href="contracts.prev_page_url"
                              class="px-4 py-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-300 hover:bg-brand-50 transition-colors shadow-sm">
                            ← Prev
                        </Link>
                        <Link v-if="contracts.next_page_url" :href="contracts.next_page_url"
                              class="px-4 py-2 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-300 hover:bg-brand-50 transition-colors shadow-sm">
                            Next →
                        </Link>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
