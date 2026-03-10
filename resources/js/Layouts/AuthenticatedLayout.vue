<script setup>
import { ref, watchEffect } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import TopNav from '@/Components/Layout/TopNav.vue';
import Breadcrumb from '@/Components/Layout/Breadcrumb.vue';

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const page = usePage();
const showSuccessToast = ref(false);
const showErrorToast = ref(false);

let successTimeout = null;
let errorTimeout = null;

watchEffect(() => {
    // Handle success flash message
    if (page.props.flash.success) {
        showSuccessToast.value = true;
        if (successTimeout) clearTimeout(successTimeout);
        successTimeout = setTimeout(() => {
            showSuccessToast.value = false;
            // Do not mutate page.props.flash.success
        }, 5000); // Auto-dismiss after 5 seconds
    } else {
        showSuccessToast.value = false;
        if (successTimeout) clearTimeout(successTimeout);
    }

    // Handle error flash message
    if (page.props.flash.error) {
        showErrorToast.value = true;
        if (errorTimeout) clearTimeout(errorTimeout);
        errorTimeout = setTimeout(() => {
            showErrorToast.value = false;
            // Do not mutate page.props.flash.error
        }, 5000); // Auto-dismiss after 5 seconds
    } else {
        showErrorToast.value = false;
        if (errorTimeout) clearTimeout(errorTimeout);
    }
});
</script>

<template>
    <div class="min-h-screen flex bg-bg-light dark:bg-bg-dark transition-colors duration-300">
        <!-- Persistent Side Navigation -->
        <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

        <!-- Principal Layout Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Global Top Navigation -->
            <TopNav @open-sidebar="isSidebarOpen = true" />

            <!-- Core Content Area -->
            <main class="flex-1 overflow-y-auto px-6 lg:px-10 py-8">
                <div class="max-w-[1600px] mx-auto">
                    <!-- Navigational Breadcrumbs -->
                    <Breadcrumb />

                    <!-- Viewport Slot -->
                    <div class="animate-content-fade">
                        <slot />
                    </div>
                </div>
            </main>
        </div>

        <!-- Global Notifications Overlay -->
        <transition name="fade">
            <div v-if="showSuccessToast" class="fixed bottom-10 right-10 z-[100] bg-success text-white px-8 py-4 rounded-3xl shadow-2xl flex items-center border border-white/20 backdrop-blur-md">
                <div class="w-2 h-2 rounded-full bg-white mr-3 animate-ping"></div>
                <span class="font-bold tracking-tight uppercase text-xs italic">{{ $page.props.flash.success }}</span>
                <button @click="showSuccessToast = false" class="ml-4 text-white/80 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="showErrorToast" class="fixed bottom-10 right-10 z-[100] bg-red-600 text-white px-8 py-4 rounded-3xl shadow-2xl flex items-center border border-white/20 backdrop-blur-md">
                <div class="w-2 h-2 rounded-full bg-white mr-3 animate-ping"></div>
                <span class="font-bold tracking-tight uppercase text-xs italic">{{ $page.props.flash.error }}</span>
                <button @click="showErrorToast = false" class="ml-4 text-white/80 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </transition>
    </div>
</template>

<style>
.animate-content-fade {
    animation: content-fade 0.4s ease-out forwards;
}

@keyframes content-fade {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
