<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Search, 
    FileText, 
    User, 
    History,
    ChevronRight,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    query: String,
    results: Object
});
</script>

<template>
    <Head title="Global Search" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold tracking-tight text-brand-900 dark:text-white">Global <span class="text-primary font-display">Search</span></h2>
                <p class="text-brand-500 dark:text-brand-400 mt-1">Found results across all modules for "<span class="font-bold text-brand-900 dark:text-white">{{ props.query }}</span>"</p>
            </div>

            <!-- Contracts Section -->
            <div v-if="props.results.contracts?.length > 0" class="card overflow-hidden">
                <div class="p-5 border-b border-brand-100 dark:border-brand-800 bg-brand-50/50 dark:bg-brand-800/30 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-brand-900 dark:text-white flex items-center">
                        <FileText class="w-4 h-4 mr-2 text-primary" /> Contracts Registry
                    </h3>
                </div>
                <div class="divide-y divide-brand-100 dark:divide-brand-800">
                    <Link v-for="contract in props.results.contracts" :key="contract.id" :href="route('contracts.show', contract.id)" class="group block p-4 hover:bg-brand-50 dark:hover:bg-brand-800/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-brand-900 dark:text-white group-hover:text-primary transition-colors">{{ contract.title }}</h4>
                                <p class="text-xs text-brand-400 dark:text-brand-500 mt-1">{{ contract.reference_number }} &bull; {{ contract.vendor_name }}</p>
                            </div>
                            <ChevronRight class="w-5 h-5 text-brand-300 dark:text-brand-600 group-hover:text-primary transition-colors" />
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Users Section -->
            <div v-if="props.results.users?.length > 0" class="card overflow-hidden">
                 <div class="p-5 border-b border-brand-100 dark:border-brand-800 bg-brand-50/50 dark:bg-brand-800/30 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-brand-900 dark:text-white flex items-center">
                        <User class="w-4 h-4 mr-2 text-primary" /> Personnel Details
                    </h3>
                </div>
                <div class="divide-y divide-brand-100 dark:divide-brand-800">
                    <div v-for="user in props.results.users" :key="user.id" class="group block p-4 hover:bg-brand-50 dark:hover:bg-brand-800/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-brand-900 dark:text-white">{{ user.name }}</h4>
                                <p class="text-xs text-brand-400 dark:text-brand-500 mt-1">{{ user.email }}</p>
                            </div>
                            <span class="badge bg-brand-100 dark:bg-brand-800 text-brand-600 dark:text-brand-300 text-[10px] font-bold uppercase">{{ user.roles[0]?.name || 'User' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Section -->
            <div v-if="props.results.logs?.length > 0" class="card overflow-hidden">
                 <div class="p-5 border-b border-brand-100 dark:border-brand-800 bg-brand-50/50 dark:bg-brand-800/30 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-brand-900 dark:text-white flex items-center">
                        <History class="w-4 h-4 mr-2 text-primary" /> Audit Logs
                    </h3>
                </div>
                <div class="divide-y divide-brand-100 dark:divide-brand-800">
                    <div v-for="log in props.results.logs" :key="log.id" class="p-4 hover:bg-brand-50 dark:hover:bg-brand-800/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-brand-900 dark:text-white uppercase">{{ log.action_type }}</h4>
                                <p class="text-xs text-brand-400 dark:text-brand-500 mt-1">Performed by: {{ log.user?.name || 'System' }} &bull; {{ new Date(log.created_at).toLocaleString() }}</p>
                            </div>
                             <span class="text-[10px] text-brand-400 font-mono">{{ log.ip_address }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="(!props.results.contracts?.length) && (!props.results.users?.length) && (!props.results.logs?.length)" class="card p-12 text-center flex flex-col items-center justify-center">
                <AlertCircle class="w-12 h-12 text-brand-300 dark:text-brand-600 mb-4" />
                <h3 class="text-lg font-bold text-brand-900 dark:text-white mb-2">No intelligence gathered.</h3>
                <p class="text-brand-500 dark:text-brand-400 text-sm">We couldn't find anything matching "<span class="font-bold">{{ props.query }}</span>". Try adjusting your search parameters.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
