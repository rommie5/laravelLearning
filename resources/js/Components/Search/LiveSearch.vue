<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { debounce } from 'lodash';
import { Link, router } from '@inertiajs/vue3';
import { 
    Search, 
    Loader2, 
    User, 
    FileText, 
    History,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: String,
    placeholder: {
        type: String,
        default: 'Search...'
    },
    type: {
        type: String,
        default: '' // 'contract', 'user', 'log'
    },
    containerClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'select']);

const searchResults = ref([]);
const isSearching = ref(false);
const isDropdownOpen = ref(false);
const searchInput = ref(props.modelValue);

const scrollContainer = ref(null);

const fetchResults = debounce(async (value) => {
    if (!value || value.length < 2) {
        searchResults.value = [];
        isDropdownOpen.value = false;
        return;
    }

    isSearching.value = true;
    try {
        const url = new URL('/api/search', window.location.origin);
        url.searchParams.append('q', value);
        if (props.type) {
            url.searchParams.append('type', props.type);
        }

        const response = await fetch(url);
        if (response.ok) {
            searchResults.value = await response.json();
            isDropdownOpen.value = searchResults.value.length > 0;
        }
    } catch (e) {
        console.error("Search failed", e);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(() => props.modelValue, (newVal) => {
    searchInput.value = newVal;
});

watch(searchInput, (newVal) => {
    emit('update:modelValue', newVal);
    fetchResults(newVal);
});

const handleEnter = () => {
    isDropdownOpen.value = false;
    // Optional: trigger a full search page redirect if not section-specific
    if (!props.type && searchInput.value) {
        router.get('/search', { q: searchInput.value });
    }
};

const closeDropdown = (e) => {
    if (!e.target.closest('.live-search-container')) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

const getIcon = (iconName) => {
    switch (iconName) {
        case 'User': return User;
        case 'FileText': return FileText;
        case 'History': return History;
        default: return FileText;
    }
};
</script>

<template>
    <div class="relative live-search-container" :class="containerClass">
        <div class="relative group">
            <Search v-if="!isSearching" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-brand-400 group-focus-within:text-primary transition-colors" />
            <Loader2 v-else class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-primary animate-spin" />
            
            <input 
                v-model="searchInput"
                type="text" 
                :placeholder="placeholder"
                @focus="isDropdownOpen = searchResults.length > 0"
                @keydown.enter="handleEnter"
                class="w-full pl-12 pr-4 py-3 bg-brand-50/50 dark:bg-brand-800/30 border border-brand-100 dark:border-brand-700/50 rounded-2xl text-sm focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all dark:text-white dark:placeholder:text-brand-500 font-semibold" 
            />
        </div>

        <!-- Results Dropdown -->
        <transition 
            enter-active-class="transition ease-out duration-200" 
            enter-from-class="opacity-0 translate-y-2 scale-95" 
            enter-to-class="opacity-100 translate-y-0 scale-100" 
            leave-active-class="transition ease-in duration-150" 
            leave-from-class="opacity-100 translate-y-0 scale-100" 
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div v-if="isDropdownOpen && searchResults.length > 0" class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow-2xl overflow-hidden z-50">
                <div class="max-h-[28rem] overflow-y-auto custom-scrollbar p-2 space-y-1" ref="scrollContainer">
                    <Link 
                        v-for="result in searchResults" 
                        :key="result.type + result.id"
                        :href="result.url"
                        @click="isDropdownOpen = false"
                        class="flex flex-col p-3 hover:bg-brand-50 dark:hover:bg-brand-800/50 rounded-2xl transition-colors group/item"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center text-xs font-bold text-brand-900 dark:text-white group-hover/item:text-primary transition-colors uppercase">
                                {{ result.name }}
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="result.context" class="text-[8px] font-black uppercase text-primary/60 tracking-wider bg-primary/5 px-2 py-0.5 rounded">{{ result.context }}</span>
                                <span class="text-[9px] font-black uppercase text-brand-400 tracking-widest bg-brand-100 dark:bg-brand-800 px-2 py-0.5 rounded">{{ result.type }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-brand-500 flex items-center">
                                <component :is="getIcon(result.icon)" class="w-3 h-3 mr-1" />
                                {{ result.detail }}
                            </span>
                            <ChevronRight class="w-3 h-3 text-brand-300 group-hover/item:text-primary transition-colors" />
                        </div>
                    </Link>
                </div>
                <div v-if="!type" class="p-3 bg-brand-50/50 dark:bg-brand-800/30 border-t border-brand-100 dark:border-brand-800 text-center">
                    <button @click="handleEnter" class="text-[10px] font-black text-primary uppercase tracking-widest hover:text-brand-900 dark:hover:text-white transition-colors">
                        View Full Results &rarr;
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
}
</style>
