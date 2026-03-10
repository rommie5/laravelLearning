<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    User, 
    Mail, 
    Lock, 
    ShieldCheck, 
    Camera, 
    Loader2, 
    CheckCircle2 
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Profile Information Form
const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
    initials: user.value.initials || '',
    avatar_type: user.value.avatar ? 'image' : 'initials',
    avatar: null,
});

const fileInput = ref(null);
const avatarPreviewBase64 = ref(null);

const avatarPreview = computed(() => {
    if (avatarPreviewBase64.value) return avatarPreviewBase64.value;
    if (user.value.avatar_url) return user.value.avatar_url;
    return null;
});

const initialsPreview = computed(() => {
    // Realtime initials generator for the UI based on Name OR custom initials override
    if (profileForm.initials && profileForm.initials.trim().length > 0) {
        return profileForm.initials.trim().toUpperCase().substring(0, 5);
    }
    
    const words = profileForm.name.trim().split(' ');
    let init = words[0] ? words[0].charAt(0) : '';
    if (words[1]) init += words[1].charAt(0);
    return init.toUpperCase();
});

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        profileForm.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreviewBase64.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Auto-fallback if avatar upload fails
watch(() => profileForm.errors.avatar, (newError) => {
    if (newError) {
        profileForm.avatar_type = 'initials';
        profileForm.avatar = null;
        avatarPreviewBase64.value = null;
    }
});

const updateProfile = () => {
    profileForm.transform((data) => {
        const payload = { ...data };
        if (!payload.avatar || payload.avatar_type === 'initials') {
            delete payload.avatar;
        }
        return payload;
    }).post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
             profileForm.clearErrors();
             // Auto sync internal UI state
             if (profileForm.avatar_type === 'initials') {
                 avatarPreviewBase64.value = null;
             }
        }
    });
};

// Auto-sync form if user prop changes centrally
watch(() => user.value, (newUser) => {
    profileForm.name = newUser.name;
    profileForm.email = newUser.email;
    profileForm.initials = newUser.initials || '';
}, { deep: true });

// Password Update Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

</script>

<template>
    <Head title="Profile Settings" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold tracking-tight text-brand-900 dark:text-white">Profile <span class="text-primary font-display">Settings</span></h2>
                <p class="text-brand-500 dark:text-brand-400 mt-1">Manage your account credentials, avatar, and security preferences.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Profile Info & Avatar -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="card overflow-hidden">
                        <div class="p-6 border-b border-brand-100 dark:border-brand-800 bg-brand-50/50 dark:bg-brand-800/30">
                            <h3 class="text-lg tracking-tight text-brand-800 dark:text-brand-500 flex items-center">
                                <User class="w-5 h-5 mr-2 text-primary" /> Personal Information
                            </h3>
                            <p class="text-xs text-brand-500 mt-1">Update your profile identity and contact email.</p>
                        </div>
                        
                        <div class="p-8">
                            <form @submit.prevent="updateProfile" class="space-y-6">
                                
                                <!-- Success Message -->
                                <transition enter-active-class="transition ease-out duration-300 transform" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="$page.props.status === 'profile-updated'" class="p-4 bg-success/10 border border-success/20 rounded-2xl flex items-center text-success text-sm font-bold">
                                        <CheckCircle2 class="w-5 h-5 mr-2" /> Profile information successfully updated.
                                    </div>
                                </transition>

                                <!-- Hybrid Avatar Display -->
                                <div class="flex items-start md:items-center flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-8 pb-4">
                                    <div class="relative group cursor-pointer shrink-0" @click="profileForm.avatar_type === 'image' ? fileInput.click() : null">
                                        <div 
                                            class="w-24 h-24 rounded-full overflow-hidden ring-4 ring-brand-50 dark:ring-brand-800 bg-white shadow-xl relative flex items-center justify-center text-3xl font-black text-white transition-all overflow-hidden"
                                            :style="(profileForm.avatar_type === 'initials' || !avatarPreview) ? `background-color: ${user.avatar_color || '#3B82F6'}` : ''"
                                        >
                                            <img v-if="profileForm.avatar_type === 'image' && avatarPreview" :src="avatarPreview" alt="Avatar Preview" class="w-full h-full object-cover" />
                                            <span v-else>{{ initialsPreview }}</span>
                                            
                                            <div v-if="profileForm.avatar_type === 'image'" class="absolute inset-0 bg-brand-900/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Camera class="w-6 h-6 text-white" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-1 space-y-4 w-full">
                                        <!-- explicit toggle -->
                                        <div class="flex items-center space-x-6 mb-4">
                                            <label class="flex items-center space-x-2 cursor-pointer group">
                                                <input type="radio" v-model="profileForm.avatar_type" value="initials" class="text-primary focus:ring-primary h-4 w-4 border-brand-300 dark:border-brand-600 dark:bg-brand-900" />
                                                <span class="text-sm font-bold text-brand-900 dark:text-brand-100 group-hover:text-primary transition-colors">Custom Initials</span>
                                            </label>
                                        </div>
                

                                        <!-- Custom Initials Panel -->
                                        <div v-if="profileForm.avatar_type === 'initials'">
                                            <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest flex items-center mb-2">
                                                Set Custom Initials
                                            </label>
                                            <input 
                                                v-model="profileForm.initials" 
                                                type="text" 
                                                maxlength="5"
                                                placeholder="e.g. CEO"
                                                class="w-full max-w-[120px] px-4 py-2.5 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white uppercase"
                                            />
                                            <p class="text-[10px] uppercase font-bold text-brand-400 mt-2 tracking-widest">Optional. Max 5 chars. Auto-generated if empty.</p>
                                            <p v-if="profileForm.errors.initials" class="text-xs mt-1 text-danger font-bold">{{ profileForm.errors.initials }}</p>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-brand-100 dark:border-brand-800/50" />

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest flex items-center">
                                            <User class="w-3 h-3 mr-1 text-primary" /> Full Name
                                        </label>
                                        <input 
                                            v-model="profileForm.name" 
                                            type="text" 
                                            class="w-full px-4 py-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white"
                                            required
                                        />
                                        <p v-if="profileForm.errors.name" class="text-xs mt-1 text-danger font-bold">{{ profileForm.errors.name }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest flex items-center">
                                            <Mail class="w-3 h-3 mr-1 text-primary" /> Email Address
                                        </label>
                                        <input 
                                            v-model="profileForm.email" 
                                            type="email" 
                                            class="w-full px-4 py-3 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white"
                                            required
                                        />
                                        <p v-if="profileForm.errors.email" class="text-xs mt-1 text-danger font-bold">{{ profileForm.errors.email }}</p>
                                    </div>
                                </div>

                                <div class="pt-4 flex justify-end">
                                    <button type="submit" :disabled="profileForm.processing" class="btn-primary" :class="{'opacity-75 cursor-not-allowed': profileForm.processing}">
                                        <Loader2 v-if="profileForm.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Security -->
                <div class="space-y-8">
                    <div class="card overflow-hidden">
                         <div class="p-6 border-b border-brand-100 dark:border-brand-800 bg-brand-50/50 dark:bg-brand-800/30">
                            <h3 class="text-lg text-brand-900 dark:text-brand-500 flex items-center">
                                <Lock class="w-5 h-5 mr-2 text-primary" /> Security
                            </h3>
                            <p class="text-xs text-brand-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                        </div>

                        <div class="p-6">
                            <form @submit.prevent="updatePassword" class="space-y-5">
                                
                                <transition enter-active-class="transition ease-out duration-300 transform" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="$page.props.status === 'password-updated'" class="p-3 bg-success/10 border border-success/20 rounded-xl flex items-center text-success text-xs font-bold mb-4">
                                        <CheckCircle2 class="w-4 h-4 mr-2 shrink-0" /> Password updated.
                                    </div>
                                </transition>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest">
                                        Current Password
                                    </label>
                                    <input 
                                        v-model="passwordForm.current_password" 
                                        type="password" 
                                        class="w-full px-4 py-2.5 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white"
                                    />
                                    <p v-if="passwordForm.errors.current_password" class="text-xs text-danger font-bold">{{ passwordForm.errors.current_password }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest">
                                        New Password
                                    </label>
                                    <input 
                                        v-model="passwordForm.password" 
                                        type="password" 
                                        class="w-full px-4 py-2.5 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white"
                                    />
                                    <p v-if="passwordForm.errors.password" class="text-xs text-danger font-bold">{{ passwordForm.errors.password }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-brand-900 dark:text-brand-100 uppercase tracking-widest">
                                        Confirm Password
                                    </label>
                                    <input 
                                        v-model="passwordForm.password_confirmation" 
                                        type="password" 
                                        class="w-full px-4 py-2.5 bg-brand-50 dark:bg-brand-800/50 border border-brand-200 dark:border-brand-700/50 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:text-white"
                                    />
                                    <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-danger font-bold">{{ passwordForm.errors.password_confirmation }}</p>
                                </div>

                                <div class="pt-2">
                                    <button type="submit" :disabled="passwordForm.processing" class="w-full btn-primary bg-brand-900 dark:bg-brand-800 dark:text-brand-200 dark:hover:bg-brand-600 " :class="{'opacity-75 cursor-not-allowed': passwordForm.processing}">
                                        <Loader2 v-if="passwordForm.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
