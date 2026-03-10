<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, Home } from 'lucide-vue-next';

const page = usePage();

const breadcrumbs = computed(() => {
    const url = page.url.split('?')[0];
    const segments = url.split('/').filter(Boolean);
    
    let path = '';
    let items = segments.map((segment) => {
        path += `/${segment}`;
        
        // Humanize segment name
        let label = segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' ');
        
        // Handle specific route names if needed
        if (segment === 'admin') label = 'System Administration';
        if (segment === 'contracts') label = 'Registry';
        if (segment === 'logs') label = 'Audit Trail';
        
        return {
            segment,
            label,
            href: path,
            current: false
        };
    });

    // Remove non-routable group prefixes (admin, head)
    items = items.filter(item => !['admin', 'head'].includes(item.segment));
    
    // Set 'current' flag appropriately for the last available valid segment
    if (items.length > 0) {
        items[items.length - 1].current = true;
    }

    return items;
});
</script>

<template>
    <nav v-if="breadcrumbs.length > 0" class="flex px-1 py-4 mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <Link 
                    :href="route('dashboard')" 
                    class="inline-flex items-center text-xs font-bold text-brand-400 hover:text-primary transition-colors uppercase tracking-widest"
                >
                    <Home class="w-3.5 h-3.5 mr-2" />
                    Dashboard
                </Link>
            </li>
            <li v-for="(crumb, index) in breadcrumbs" :key="index">
                <div class="flex items-center">
                    <ChevronRight class="w-4 h-4 text-blue-600 text-brand-300 mx-1" />
                    <Link 
                        v-if="!crumb.current" 
                        :href="crumb.href" 
                        class="ml-1 text-xs font-bold text-blue-600 hover:text-primary transition-colors uppercase tracking-widest"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span 
                        v-else 
                        class="ml-1 text-xs font-bold text-blue-900 dark:text-white uppercase tracking-widest"
                        aria-current="page"
                    >
                        {{ crumb.label }}
                    </span>
                </div>
            </li>
        </ol>
    </nav>
</template>
