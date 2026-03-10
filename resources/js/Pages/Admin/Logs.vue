<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search,
    History,
    Terminal,
    Database,
    ShieldAlert,
    Download
} from 'lucide-vue-next';
import LiveSearch from '@/Components/Search/LiveSearch.vue';
import ExportDropdown from '@/Components/Common/ExportDropdown.vue';

const props = defineProps({
    logs: Object,
    filters: Object
});

const search = ref(props.filters?.search || '');

import { usePage } from '@inertiajs/vue3';
const page = usePage();
const user = computed(() => page.props.auth.user);

const indexUrl = computed(() => {
    if (user.value.roles.includes('Admin')) {
        return route('admin.logs.index');
    } else if (user.value.roles.includes('Head')) {
        return route('head.logs.index');
    } else {
        return route('officer.logs.index');
    }
});

watch(search, debounce((value) => {
    router.get(indexUrl.value, { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

const pdfExportUrl = computed(() => {
    if (user.value.roles.includes('Admin')) return route('admin.logs.export.pdf');
    if (user.value.roles.includes('Head')) return route('head.logs.export.pdf');
    return route('officer.logs.export.pdf');
});

const excelExportUrl = computed(() => {
    if (user.value.roles.includes('Admin')) return route('admin.logs.export.excel');
    if (user.value.roles.includes('Head')) return route('head.logs.export.excel');
    return route('officer.logs.export.excel');
});

</script>

<template>
    <Head title="System Audit Logs" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight dark:text-white text-brand-900">System <span class="text-brand-600 font-display">Audit Trail</span></h2>
                    <p class="text-brand-500 mt-1">Immutable record of all system state changes.</p>
                </div>
                <ExportDropdown 
                    :pdf-url="pdfExportUrl"
                    :excel-url="excelExportUrl"
                    :filters="{ search }"
                />
            </div>

            <div class="card p-4 mb-6">
                <div class="w-full max-w-md">
                    <LiveSearch 
                        v-model="search"
                        type="log"
                        placeholder="Filter by action, user, or IP..."
                    />
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] uppercase tracking-widest text-brand-500 border-b border-brand-100 font-bold">
                            <tr>
                                <th class="px-6 py-4 dark:text-brand-400">Timestamp</th>
                                <th class="px-6 py-4 dark:text-brand-400">Identity</th>
                                <th class="px-6 py-4 dark:text-brand-400">Operation Type</th>
                                <th class="px-6 py-4 dark:text-brand-400">Affected Resource</th>
                                <th class="px-6 py-4 dark:text-brand-400">Network & Device</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-100 bg-white font-mono text-[11px] dark:bg-brand-900">
                            <tr v-for="log in props.logs.data" :key="log.id" class="hover:bg-brand-50/30 transition-colors group">
                                <td class="px-6 py-4 text-brand-500 whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="mr-2 px-1.5 py-0.5 bg-brand-100 text-brand-700 rounded text-[9px] font-bold uppercase">{{ log.role || 'SYS' }}</div>
                                        <span class="font-bold text-brand-900 dark:text-brand-300 uppercase font-sans">{{ log.user?.name || 'SYSTEM' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-brand-800 dark:text-brand-500 font-bold uppercase tracking-tight">
                                    {{ log.action_type }}
                                </td>
                                <td class="px-6 py-4 text-brand-600">
                                    <span v-if="log.model_affected" class="bg-slate-100 px-2 py-0.5 rounded mr-1">{{ log.model_affected.split('\\').pop() }}</span>
                                    <span class="text-brand-400">#{{ log.model_id || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-brand-400">
                                    <div class="flex flex-col">
                                        <span>{{ log.ip_address }}</span>
                                        <span v-if="log.action_type === 'login' && log.new_values?.user_agent" class="text-[9px] mt-1 text-brand-300 max-w-[200px] truncate" :title="log.new_values.user_agent">
                                            {{ log.new_values.user_agent }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-6 bg-brand-50/50 border-t border-brand-100 dark:bg-brand-900">
                    <!-- Simple pagination -->
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold text-brand-400 uppercase dark:text-brand-500 dark:bg-brand-900 tracking-widest">Page {{ props.logs.current_page }} of {{ props.logs.last_page }}</p>
                        <div class="flex space-x-2">
                             <Link v-if="props.logs.prev_page_url" :href="props.logs.prev_page_url" class="px-3 py-1 bg-white border dark:text-brand-900 border-brand-200 rounded text-[10px] font-bold hover:bg-brand-50">PREV</Link>
                             <Link v-if="props.logs.next_page_url" :href="props.logs.next_page_url" class="px-3 py-1 bg-white border dark:text-brand-900 border-brand-200 rounded text-[10px] font-bold hover:bg-brand-50">NEXT</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
