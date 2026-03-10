<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ArrowDownRight, Minus } from 'lucide-vue-next';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: Object,
    trend: String, // 'up', 'down', 'neutral'
    trendValue: String,
    href: String,
    variant: {
        type: String,
        default: 'primary' // 'primary', 'success', 'warning', 'danger'
    }
});

const variantClasses = {
    primary: 'text-primary bg-primary/10 dark:bg-primary/20',
    success: 'text-success bg-success/10 dark:bg-success/20',
    warning: 'text-warning bg-warning/10 dark:bg-warning/20',
    danger: 'text-danger bg-danger/10 dark:bg-danger/20',
};
</script>

<template>
    <component 
        :is="href ? Link : 'div'" 
        :href="href"
        class="bg-white dark:bg-bg-dark/50 border border-brand-200 dark:border-brand-800 p-6 rounded-[2rem] shadow-sm hover:shadow-xl hover:border-primary/30 transition-all duration-300 group block relative overflow-hidden"
    >
        <!-- Background Gradient Decor -->
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>

        <div class="flex items-start justify-between relative z-10">
            <div>
                <p class="text-[10px] font-black text-brand-400 dark:text-brand-500 uppercase tracking-widest mb-2">{{ title }}</p>
                <h3 class="text-3xl font-display font-black text-brand-900 dark:text-white tracking-tight">{{ value }}</h3>
            </div>
            <div :class="['p-3 rounded-2xl transition-transform group-hover:scale-110 duration-300', variantClasses[variant]]">
                <component :is="icon" class="w-6 h-6" />
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between relative z-10">
            <div v-if="trend" class="flex items-center space-x-1.5">
                <div :class="[
                    'flex items-center text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full',
                    trend === 'up' ? 'bg-success/10 text-success' : 
                    trend === 'down' ? 'bg-danger/10 text-danger' : 
                    'bg-brand-100 dark:bg-brand-800 text-brand-400'
                ]">
                    <ArrowUpRight v-if="trend === 'up'" class="w-2.5 h-2.5 mr-0.5" />
                    <ArrowDownRight v-if="trend === 'down'" class="w-2.5 h-2.5 mr-0.5" />
                    <Minus v-if="trend === 'neutral'" class="w-2.5 h-2.5 mr-0.5" />
                    {{ trendValue }}
                </div>
                <span class="text-[10px] font-bold text-brand-400 dark:text-brand-500 uppercase">vs last month</span>
            </div>
            
            <div v-if="href" class="text-xs font-black text-primary opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0 transition-all duration-300 flex items-center uppercase tracking-widest">
                Explore <ArrowUpRight class="w-3 h-3 ml-1" />
            </div>
        </div>
    </component>
</template>
