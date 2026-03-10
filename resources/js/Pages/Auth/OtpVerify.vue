<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Loader2, ShieldCheck } from 'lucide-vue-next';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('otp.verify.submit'));
};

const resend = () => {
    form.post(route('otp.verify.resend'), {
        preserveScroll: true,
        onSuccess: () => form.reset('code'),
    });
};
</script>

<template>
    <Head title="OTP Verification" />

    <div class="min-h-screen flex items-center justify-center bg-brand-900 relative overflow-hidden p-4">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.08),transparent_35%),radial-gradient(circle_at_80%_70%,rgba(59,130,246,0.16),transparent_35%)]"></div>

        <div class="relative z-10 w-full max-w-md bg-white/10 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            <div class="w-14 h-14 rounded-2xl bg-brand-500/20 flex items-center justify-center mb-5">
                <ShieldCheck class="w-8 h-8 text-brand-300" />
            </div>

            <h1 class="text-2xl font-bold text-white">First Login Verification</h1>
            <p class="text-brand-300 mt-2 text-sm">
                Enter the 6-digit OTP sent to your assigned email address.
            </p>

            <div v-if="flash.success" class="mt-4 text-xs text-emerald-300 bg-emerald-400/10 border border-emerald-300/20 rounded-lg px-3 py-2">
                {{ flash.success }}
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-brand-200 mb-2">OTP Code</label>
                    <input
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-center tracking-[0.4em] text-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-400 outline-none"
                    />
                    <p v-if="form.errors.code" class="text-rose-300 text-xs mt-2">{{ form.errors.code }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 rounded-xl bg-brand-500 hover:bg-brand-400 text-brand-900 font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <span>Verify & Continue</span>
                </button>

                <button
                    type="button"
                    @click="resend"
                    :disabled="form.processing"
                    class="w-full py-3 rounded-xl border border-white/20 text-brand-200 hover:text-white hover:border-white/40 transition-colors disabled:opacity-50"
                >
                    Resend OTP
                </button>
            </form>
        </div>
    </div>
</template>
