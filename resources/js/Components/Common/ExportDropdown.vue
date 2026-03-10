<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Download, FileText, Table, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    pdfUrl: {
        type: String,
        required: true
    },
    excelUrl: {
        type: String,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdown);
});

const buildUrl = (baseUrl) => {
    const url = new URL(baseUrl, window.location.origin);
    Object.keys(props.filters).forEach(key => {
        if (props.filters[key]) {
            url.searchParams.append(key, props.filters[key]);
        }
    });
    return url.toString();
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button 
            @click="toggleDropdown"
            class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-brand-800 border border-brand-200 dark:border-brand-700 rounded-2xl text-[10px] font-black uppercase tracking-widest text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-brand-700 transition-all shadow-sm active:scale-95"
        >
            <Download class="w-4 h-4" />
            <span>Export</span>
            <ChevronDown class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
        </button>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-2"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-2"
        >
            <div 
                v-if="isOpen"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl shadow-2xl z-[60] overflow-hidden"
            >
                <div class="p-2 space-y-1">
                    <a 
                        :href="buildUrl(pdfUrl)" 
                        @click="isOpen = false"
                        class="flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-800 rounded-xl transition-colors group"
                    >
                        <div class="p-1.5 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-lg group-hover:bg-red-100 dark:group-hover:bg-red-900/40 transition-colors">
                            <FileText class="w-4 h-4" />
                        </div>
                        Export as PDF
                    </a>
                    
                    <a 
                        :href="buildUrl(excelUrl)" 
                        @click="isOpen = false"
                        class="flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-800 rounded-xl transition-colors group"
                    >
                        <div class="p-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 rounded-lg group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 transition-colors">
                            <Table class="w-4 h-4" />
                        </div>
                        Export as Excel
                    </a>
                </div>
            </div>
        </Transition>
    </div>
</template>
