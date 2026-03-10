<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { Lock, Mail, Loader2 } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen flex items-center justify-center bg-brand-900 overflow-hidden relative">
        <!-- Abstract background elements -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-brand-500 rounded-full blur-[120px] opacity-20 -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-brand-400 rounded-full blur-[120px] opacity-10 translate-x-1/2 translate-y-1/2"></div>

        <div class="w-full max-w-md px-6 z-10">
            <!-- Logo area -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white shadow-2xl shadow-black/50 mb-6 ring-1 ring-white/10 rounded-3xl overflow-hidden p-2.5">
                    <img src="/images/logo.png" alt="CMS Logo" class="w-full h-full object-contain drop-shadow-sm" />
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Contract Management System</h1>
                <!-- <p class="text-brand-400 mt-2 font-medium tracking-wide uppercase text-xs">Internal Access Only</p> -->
            </div>

            <!-- Form -->
            <div class="bg-white/10 backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl shadow-black/40">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-brand-200 mb-2">Corporate Email</label>
                        <div class="relative group">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-500 group-focus-within:text-brand-400 transition-colors" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="name@company.com"
                                class="w-full pl-11 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-brand-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-rose-400 text-xs mt-1.5 font-medium">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-brand-200 mb-2">Security Password</label>
                        <div class="relative group">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-500 group-focus-within:text-brand-400 transition-colors" />
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-11 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-brand-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center group cursor-pointer">
                            <input type="checkbox" v-model="form.remember" class="w-4 h-4 bg-white/5 border-white/10 rounded text-brand-500 focus:ring-brand-500 focus:ring-offset-brand-900" />
                            <span class="ml-2 text-sm text-brand-400 group-hover:text-brand-200 transition-colors">Keep me active</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 bg-brand-500 hover:bg-brand-400 text-brand-900 font-bold rounded-xl transition-all shadow-lg hover:shadow-brand-500/20 active:scale-[0.98] disabled:opacity-50 flex items-center justify-center space-x-2"
                    >
                        <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                        <span>{{ form.processing ? 'Verifying...' : 'Authenticate' }}</span>
                    </button>

                    <p class="text-center text-[10px] text-brand-600 uppercase tracking-widest font-bold mt-8">
                        Authorized Personnel Only
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
