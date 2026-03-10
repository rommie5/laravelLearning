<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    FileText, Calendar, Shield, CreditCard, Send, Save,
    ChevronDown, ChevronUp, Plus, Trash2, Clock, Lock,
    AlertTriangle, CheckCircle2, Info
} from 'lucide-vue-next';
import dayjs from 'dayjs';

// ──────────────────────────────────────────────────────────
// FORM STATE
// ──────────────────────────────────────────────────────────
const form = useForm({
    // Section 1: Identification
    contract_number:   '',
    contract_name:     '',
    awarded_to:        '',
    contract_site:     '',
    priority_level:    'medium',

    // Section 2: Duration
    contract_signing_date: dayjs().format('YYYY-MM-DD'),
    start_date:            dayjs().format('YYYY-MM-DD'),
    duration_value:        12,
    duration_unit:         'months',

    // Section 3: Clauses (optional)
    clauses: [],   // built from toggles below

    // Section 4: Installments
    installments: [],

    // Section 5: Action
    notes:  '',
    action: 'draft',
});

// Clause toggle state (separate from form.clauses so UI controls them)
const psEnabled = ref(false);
const psdays    = ref(30);

const hoEnabled = ref(false);
const hodays    = ref(60);

// ──────────────────────────────────────────────────────────
// COMPUTED — auto calculations
// ──────────────────────────────────────────────────────────

const computedExpiry = computed(() => {
    if (!form.start_date || !form.duration_value || !form.duration_unit) return null;
    const d = dayjs(form.start_date);
    return form.duration_unit === 'weeks'
        ? d.add(Number(form.duration_value), 'week')
        : d.add(Number(form.duration_value), 'month');
});

const computedExpiryStr = computed(() => computedExpiry.value?.format('YYYY-MM-DD') ?? null);
const computedExpiryDisplay = computed(() => computedExpiry.value?.format('D MMMM YYYY') ?? '—');

// Performance Security: signing_date + psdays
const psDueDate = computed(() => {
    if (!psEnabled.value || !form.contract_signing_date || !psdays.value) return null;
    return dayjs(form.contract_signing_date).add(Number(psdays.value), 'day');
});
const psDueDateDisplay = computed(() => psDueDate.value?.format('D MMMM YYYY') ?? '—');
const psDueDateStr     = computed(() => psDueDate.value?.format('YYYY-MM-DD') ?? null);

// Handover: expiry_date + hodays
const hoDueDate = computed(() => {
    if (!hoEnabled.value || !computedExpiryStr.value || !hodays.value) return null;
    return dayjs(computedExpiryStr.value).add(Number(hodays.value), 'day');
});
const hoDueDateDisplay = computed(() => hoDueDate.value?.format('D MMMM YYYY') ?? '—');
const hoDueDateStr     = computed(() => hoDueDate.value?.format('YYYY-MM-DD') ?? null);

// ── Collision warnings (live, pre-submit) ───────────────────
const collisionWarnings = computed(() => {
    const warnings = [];
    if (!computedExpiry.value) return warnings;

    if (psEnabled.value && psDueDate.value) {
        if (!psDueDate.value.isBefore(computedExpiry.value)) {
            warnings.push({
                type: 'ps',
                msg: `Performance Security due date (${psDueDateDisplay.value}) must be BEFORE expiry (${computedExpiryDisplay.value}).`,
            });
        }
    }
    if (hoEnabled.value && hoDueDate.value) {
        if (!hoDueDate.value.isAfter(computedExpiry.value)) {
            warnings.push({
                type: 'ho',
                msg: `Handover due date (${hoDueDateDisplay.value}) must be AFTER expiry (${computedExpiryDisplay.value}).`,
            });
        }
        if (psEnabled.value && psDueDate.value && !hoDueDate.value.isAfter(psDueDate.value)) {
            warnings.push({
                type: 'ho2',
                msg: `Handover due date (${hoDueDateDisplay.value}) must be AFTER performance security due date (${psDueDateDisplay.value}).`,
            });
        }
    }
    return warnings;
});

const hasCollisions = computed(() => collisionWarnings.value.length > 0);

// ── Installment validation warnings ────────────────────────
const installmentWarnings = computed(() => {
    const warnings = [];
    if (form.installments.length === 0) return warnings;
    const start  = form.start_date ? dayjs(form.start_date) : null;
    const expiry = computedExpiry.value;
    let prevDate = null;
    const seenDates = new Set();

    form.installments.forEach((inst, i) => {
        const n    = i + 1;
        const date = inst.due_date ? dayjs(inst.due_date) : null;
        if (!date || !date.isValid()) return;

        if (start && date.isBefore(start)) {
            warnings.push(`Installment #${n}: due date is before the contract start date.`);
        }
        if (expiry && date.isAfter(expiry)) {
            warnings.push(`Installment #${n}: due date is after the contract expiry date.`);
        }
        if (seenDates.has(inst.due_date)) {
            warnings.push(`Installment #${n}: duplicate due date.`);
        }
        if (prevDate && !date.isAfter(prevDate)) {
            warnings.push(`Installment #${n}: must be after installment #${n - 1}.`);
        }
        seenDates.add(inst.due_date);
        prevDate = date;
    });
    return warnings;
});

// ── Sidebar summary ─────────────────────────────────────────
const totalInstallments = computed(() =>
    form.installments.reduce((sum, i) => sum + (parseFloat(i.amount) || 0), 0)
);
const totalInstallmentsFormatted = computed(() =>
    new Intl.NumberFormat('en-US', { style: 'decimal', minimumFractionDigits: 2 }).format(totalInstallments.value)
);

// ──────────────────────────────────────────────────────────
// SECTION ACCORDION
// ──────────────────────────────────────────────────────────
const openSections = ref([1, 2, 3, 4, 5]);
function toggleSection(n) {
    openSections.value = openSections.value.includes(n)
        ? openSections.value.filter(s => s !== n)
        : [...openSections.value, n];
}

// ──────────────────────────────────────────────────────────
// INSTALLMENTS
// ──────────────────────────────────────────────────────────
function addInstallment() {
    form.installments.push({ amount: '', due_date: '' });
}
function removeInstallment(i) {
    form.installments.splice(i, 1);
}

// ──────────────────────────────────────────────────────────
// SUBMIT — build final form.clauses from toggles then post
// ──────────────────────────────────────────────────────────
function buildClauses() {
    const clauses = [];
    if (psEnabled.value && psdays.value) {
        clauses.push({ clause_type: 'performance_security', period_days: Number(psdays.value) });
    }
    if (hoEnabled.value && hodays.value) {
        clauses.push({ clause_type: 'handover', period_days: Number(hodays.value) });
    }
    return clauses;
}

function saveDraft() {
    form.clauses = buildClauses();
    form.action  = 'draft';
    form.post(route('contracts.store'));
}

function submitForApproval() {
    if (hasCollisions.value) return;
    form.clauses = buildClauses();
    form.action  = 'submit';
    form.post(route('contracts.store'));
}

// ──────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────
const priorityColor = {
    low:       'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    medium:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    high:      'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    sensitive: 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-300',
};
</script>

<template>
    <Head title="Create Contract" />

    <AuthenticatedLayout>
        <template #header>
            <nav class="flex items-center space-x-2 text-xs text-brand-500" aria-label="Breadcrumb">
                <Link :href="route('dashboard')" class="hover:text-primary transition-colors">Dashboard</Link>
                <span>/</span>
                <Link :href="route('contracts.index')" class="hover:text-primary transition-colors">Contracts</Link>
                <span>/</span>
                <span class="text-brand-900 dark:text-white font-semibold">New Contract</span>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-[1440px] mx-auto sm:px-6 lg:px-8">

                <!-- Page Title -->
                <div class="mb-8">
                    <div class="flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-2">
                        <Lock class="w-3 h-3" /> Secure Registry
                    </div>
                    <h1 class="text-3xl font-black text-brand-900 dark:text-white">New <span class="text-primary italic">Contract</span></h1>
                    <p class="text-sm text-brand-400 mt-1">Complete all required sections. Expiry is auto-calculated and cannot be edited manually.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                    <!-- ═══════════════════════════════════════ -->
                    <!-- LEFT — FORM (2/3 width)                 -->
                    <!-- ═══════════════════════════════════════ -->
                    <div class="lg:col-span-2 space-y-5">

                        <!-- Error/Collision Summary -->
                        <div v-if="hasCollisions" class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 rounded-2xl p-5 space-y-1">
                            <p class="text-xs font-black uppercase text-red-600 mb-2 flex items-center gap-2">
                                <AlertTriangle class="w-4 h-4" /> Clause Collision Detected
                            </p>
                            <p v-for="w in collisionWarnings" :key="w.type" class="text-sm text-red-600">⚠ {{ w.msg }}</p>
                        </div>

                        <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-5">
                            <p class="text-xs font-black uppercase text-red-600 mb-2">Fix these errors:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(e, f) in form.errors" :key="f" class="text-sm text-red-600">{{ e }}</li>
                            </ul>
                        </div>

                        <!-- ── SECTION 1: Identification ── -->
                        <section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <button @click="toggleSection(1)" type="button"
                                    class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center">
                                        <FileText class="w-4 h-4 text-primary" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 1</p>
                                        <p class="text-base font-bold text-brand-900 dark:text-white">Contract Identification</p>
                                    </div>
                                </div>
                                <ChevronUp v-if="openSections.includes(1)" class="w-5 h-5 text-brand-400" />
                                <ChevronDown v-else class="w-5 h-5 text-brand-400" />
                            </button>

                            <div v-show="openSections.includes(1)" class="px-7 pb-7 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label">Contract Number <span class="text-red-500">*</span></label>
                                    <input v-model="form.contract_number" type="text" class="form-input" :class="{'!border-red-400': form.errors.contract_number}" placeholder="e.g. CMS-2026-001" />
                                    <p v-if="form.errors.contract_number" class="form-error">{{ form.errors.contract_number }}</p>
                                </div>
                                <div>
                                    <label class="form-label">Priority Level <span class="text-red-500">*</span></label>
                                    <select v-model="form.priority_level" class="form-input">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="sensitive">Sensitive / Restricted</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="form-label">Contract Name <span class="text-red-500">*</span></label>
                                    <input v-model="form.contract_name" type="text" class="form-input" :class="{'!border-red-400': form.errors.contract_name}" placeholder="e.g. Strategic Roadway Enhancement Phase II" />
                                    <p v-if="form.errors.contract_name" class="form-error">{{ form.errors.contract_name }}</p>
                                </div>
                                <div>
                                    <label class="form-label">Awarded To <span class="text-red-500">*</span></label>
                                    <input v-model="form.awarded_to" type="text" class="form-input" :class="{'!border-red-400': form.errors.awarded_to}" placeholder="Contractor / Vendor" />
                                    <p v-if="form.errors.awarded_to" class="form-error">{{ form.errors.awarded_to }}</p>
                                </div>
                                <div>
                                    <label class="form-label">Contract Site</label>
                                    <input v-model="form.contract_site" type="text" class="form-input" placeholder="Location or zone" />
                                </div>
                            </div>
                        </section>

                        <!-- ── SECTION 2: Duration ── -->
                        <section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <button @click="toggleSection(2)" type="button"
                                    class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                                        <Calendar class="w-4 h-4 text-amber-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 2</p>
                                        <p class="text-base font-bold text-brand-900 dark:text-white">Duration & Expiry</p>
                                    </div>
                                </div>
                                <ChevronUp v-if="openSections.includes(2)" class="w-5 h-5 text-brand-400" />
                                <ChevronDown v-else class="w-5 h-5 text-brand-400" />
                            </button>

                            <div v-show="openSections.includes(2)" class="px-7 pb-7 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="form-label">Signing Date <span class="text-red-500">*</span></label>
                                        <input v-model="form.contract_signing_date" type="date" class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Start Date <span class="text-red-500">*</span></label>
                                        <input v-model="form.start_date" type="date" class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Duration <span class="text-red-500">*</span></label>
                                        <input v-model.number="form.duration_value" type="number" min="1" class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Unit <span class="text-red-500">*</span></label>
                                        <select v-model="form.duration_unit" class="form-input">
                                            <option value="weeks">Weeks</option>
                                            <option value="months">Months</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Expiry Preview -->
                                <div class="flex items-center gap-4 p-5 bg-primary/5 border border-primary/15 rounded-2xl">
                                    <Clock class="w-5 h-5 text-primary shrink-0" />
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-primary mb-0.5">Computed Expiry Date (read-only)</p>
                                        <p class="text-xl font-black text-brand-900 dark:text-white">{{ computedExpiryDisplay }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- ── SECTION 3: Optional Clauses ── -->
                        <section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <button @click="toggleSection(3)" type="button"
                                    class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                        <Shield class="w-4 h-4 text-emerald-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 3 — Optional</p>
                                        <p class="text-base font-bold text-brand-900 dark:text-white">Compliance Clauses</p>
                                    </div>
                                </div>
                                <ChevronUp v-if="openSections.includes(3)" class="w-5 h-5 text-brand-400" />
                                <ChevronDown v-else class="w-5 h-5 text-brand-400" />
                            </button>

                            <div v-show="openSections.includes(3)" class="px-7 pb-7 space-y-5">
                                <div class="flex items-start gap-2 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/40">
                                    <Info class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" />
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        Toggle each clause that applies. System enforces:
                                        <strong>Performance Security due date &lt; Expiry</strong> and
                                        <strong>Handover due date &gt; Expiry &gt; Performance Security</strong>.
                                    </p>
                                </div>

                                <!-- Performance Security -->
                                <div class="border border-brand-100 dark:border-brand-800 rounded-2xl overflow-hidden">
                                    <div class="flex items-center justify-between p-5">
                                        <div>
                                            <p class="text-sm font-black text-brand-900 dark:text-white">Performance Security</p>
                                            <p class="text-xs text-brand-400">Due = Signing Date + period days (must be before expiry)</p>
                                        </div>
                                        <button @click="psEnabled = !psEnabled" type="button"
                                                :class="[
                                                    'w-12 h-6 rounded-full relative transition-colors duration-200',
                                                    psEnabled ? 'bg-primary' : 'bg-brand-200 dark:bg-brand-700'
                                                ]">
                                            <span :class="['absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200', psEnabled ? 'left-[26px]' : 'left-0.5']"></span>
                                        </button>
                                    </div>
                                    <div v-if="psEnabled" class="px-5 pb-5 border-t border-brand-100 dark:border-brand-800 pt-5">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label">Period (days) <span class="text-red-500">*</span></label>
                                                <input v-model.number="psdays" type="number" min="1" class="form-input" placeholder="e.g. 30" />
                                            </div>
                                            <div>
                                                <label class="form-label">Computed Due Date</label>
                                                <div :class="['flex items-center h-11 px-4 rounded-xl text-sm font-bold border',
                                                    !hasCollisions || !collisionWarnings.find(w=>w.type==='ps')
                                                        ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                                                        : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-600'
                                                ]">
                                                    {{ psDueDateDisplay }}
                                                </div>
                                            </div>
                                        </div>
                                        <p v-if="collisionWarnings.find(w=>w.type==='ps')" class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" /> {{ collisionWarnings.find(w=>w.type==='ps').msg }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Handover -->
                                <div class="border border-brand-100 dark:border-brand-800 rounded-2xl overflow-hidden">
                                    <div class="flex items-center justify-between p-5">
                                        <div>
                                            <p class="text-sm font-black text-brand-900 dark:text-white">Handover</p>
                                            <p class="text-xs text-brand-400">Due = Expiry Date + period days (must be after expiry)</p>
                                        </div>
                                        <button @click="hoEnabled = !hoEnabled" type="button"
                                                :class="[
                                                    'w-12 h-6 rounded-full relative transition-colors duration-200',
                                                    hoEnabled ? 'bg-primary' : 'bg-brand-200 dark:bg-brand-700'
                                                ]">
                                            <span :class="['absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200', hoEnabled ? 'left-[26px]' : 'left-0.5']"></span>
                                        </button>
                                    </div>
                                    <div v-if="hoEnabled" class="px-5 pb-5 border-t border-brand-100 dark:border-brand-800 pt-5">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label">Period (days) <span class="text-red-500">*</span></label>
                                                <input v-model.number="hodays" type="number" min="1" class="form-input" placeholder="e.g. 60" />
                                            </div>
                                            <div>
                                                <label class="form-label">Computed Due Date</label>
                                                <div :class="['flex items-center h-11 px-4 rounded-xl text-sm font-bold border',
                                                    !collisionWarnings.find(w=>w.type==='ho'||w.type==='ho2')
                                                        ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                                                        : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-600'
                                                ]">
                                                    {{ hoDueDateDisplay }}
                                                </div>
                                            </div>
                                        </div>
                                        <p v-if="collisionWarnings.find(w=>w.type==='ho'||w.type==='ho2')" class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" />
                                            {{ collisionWarnings.find(w=>w.type==='ho'||w.type==='ho2').msg }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- ── SECTION 4: Installments ── -->
                        <section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <button @click="toggleSection(4)" type="button"
                                    class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                        <CreditCard class="w-4 h-4 text-purple-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 4 — Optional</p>
                                        <p class="text-base font-bold text-brand-900 dark:text-white">Payment Installments
                                            <span v-if="form.installments.length > 0" class="ml-2 text-xs font-black bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full">{{ form.installments.length }}</span>
                                        </p>
                                    </div>
                                </div>
                                <ChevronUp v-if="openSections.includes(4)" class="w-5 h-5 text-brand-400" />
                                <ChevronDown v-else class="w-5 h-5 text-brand-400" />
                            </button>

                            <div v-show="openSections.includes(4)" class="px-7 pb-7 space-y-4">
                                <!-- Installment warnings -->
                                <div v-if="installmentWarnings.length > 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                    <p v-for="w in installmentWarnings" :key="w" class="text-xs text-amber-700 dark:text-amber-300 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3 shrink-0" /> {{ w }}
                                    </p>
                                </div>

                                <!-- Installment rows -->
                                <div v-if="form.installments.length > 0" class="space-y-3">
                                    <!-- Header -->
                                    <div class="grid grid-cols-12 gap-3 px-1">
                                        <p class="col-span-1 text-[9px] font-black uppercase tracking-widest text-brand-400">#</p>
                                        <p class="col-span-5 text-[9px] font-black uppercase tracking-widest text-brand-400">Amount</p>
                                        <p class="col-span-5 text-[9px] font-black uppercase tracking-widest text-brand-400">Due Date</p>
                                        <p class="col-span-1"></p>
                                    </div>

                                    <div v-for="(inst, i) in form.installments" :key="i"
                                         class="grid grid-cols-12 gap-3 items-center bg-brand-50 dark:bg-brand-950/40 p-3 rounded-2xl border border-brand-100 dark:border-brand-800">
                                        <div class="col-span-1 flex items-center">
                                            <span class="text-xs font-black text-brand-500">{{ i + 1 }}</span>
                                        </div>
                                        <div class="col-span-5">
                                            <input v-model="inst.amount" type="number" min="0.01" step="0.01"
                                                   class="form-input text-sm py-2" placeholder="0.00" />
                                        </div>
                                        <div class="col-span-5">
                                            <input v-model="inst.due_date" type="date" class="form-input text-sm py-2" />
                                        </div>
                                        <div class="col-span-1 flex justify-end">
                                            <button @click="removeInstallment(i)" type="button"
                                                    class="w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 transition-colors">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button @click="addInstallment" type="button"
                                        class="w-full py-3 px-4 border-2 border-dashed border-brand-200 dark:border-brand-700 rounded-2xl text-xs font-black uppercase tracking-widest text-brand-400 hover:border-primary hover:text-primary transition-all flex items-center justify-center gap-2">
                                    <Plus class="w-4 h-4" /> Add Installment
                                </button>

                                <p class="text-[10px] text-brand-400 flex items-center gap-1">
                                    <Info class="w-3 h-3" />
                                    Installment dates must be within the contract period ({{ form.start_date || '—' }} → {{ computedExpiryStr || '—' }}) and strictly increasing.
                                </p>
                            </div>
                        </section>

                        <!-- ── SECTION 5: Notes & Action ── -->
                        <section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <button @click="toggleSection(5)" type="button"
                                    class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center">
                                        <Send class="w-4 h-4 text-rose-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 5</p>
                                        <p class="text-base font-bold text-brand-900 dark:text-white">Notes & Action</p>
                                    </div>
                                </div>
                                <ChevronUp v-if="openSections.includes(5)" class="w-5 h-5 text-brand-400" />
                                <ChevronDown v-else class="w-5 h-5 text-brand-400" />
                            </button>

                            <div v-show="openSections.includes(5)" class="px-7 pb-7 space-y-6">
                                <div>
                                    <label class="form-label">Notes</label>
                                    <textarea v-model="form.notes" rows="3" class="form-input" placeholder="Additional conditions, requirements, or context..."></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <button @click="saveDraft" type="button" :disabled="form.processing"
                                            class="flex items-center justify-center gap-3 py-4 px-6 bg-brand-100 dark:bg-brand-800 text-brand-700 dark:text-brand-200 font-black uppercase tracking-widest text-sm rounded-2xl hover:bg-brand-200 dark:hover:bg-brand-700 transition-all disabled:opacity-50">
                                        <Save class="w-5 h-5" /> Save as Draft
                                    </button>
                                    <button @click="submitForApproval" type="button"
                                            :disabled="form.processing || hasCollisions"
                                            :title="hasCollisions ? 'Resolve clause collisions before submitting' : ''"
                                            class="flex items-center justify-center gap-3 py-4 px-6 bg-primary text-white font-black uppercase tracking-widest text-sm rounded-2xl hover:opacity-90 hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-primary/25 disabled:opacity-40 disabled:cursor-not-allowed disabled:scale-100">
                                        <Send class="w-5 h-5" />
                                        {{ hasCollisions ? 'Resolve Collisions First' : 'Submit for Approval' }}
                                    </button>
                                </div>

                                <p class="text-[10px] text-brand-400 text-center">
                                    <strong>Draft</strong> — visible only to you &nbsp;|&nbsp;
                                    <strong>Submit</strong> — locks for editing until reviewed by Head
                                </p>
                            </div>
                        </section>

                    </div>

                    <!-- ═══════════════════════════════════════ -->
                    <!-- RIGHT — LIVE SUMMARY SIDEBAR (1/3)      -->
                    <!-- ═══════════════════════════════════════ -->
                    <div class="lg:sticky lg:top-8 space-y-5">

                        <!-- Summary card -->
                        <div class="bg-white-600 dark:bg-white-600 border border-gray-200 shadow relative overflow-hidden rounded-2xl p-5 dark:border-brand-800" >
                            <p class="text-[10px] font-black uppercase text-primary text-center tracking-[0.25em] text-brand-800 mb-6">Live Summary</p>
                            <div class="space-y-5 relative z-10">

                                <div>
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-1">Contract Name</p>
                                    <p class="text-sm font-bold text-brand-600 truncate">{{ form.contract_name || '—' }}</p>
                                </div>

                                <div>
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-1">Awarded To</p>
                                    <p class="text-sm font-bold text-brand-600 truncate">{{ form.awarded_to || '—' }}</p>
                                </div>

                                <div class="border-t border-brand-700 pt-5">
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-1">Duration</p>
                                    <p class="text-sm font-bold text-brand-600">{{ form.duration_value }} {{ form.duration_unit }}</p>
                                </div>

                                <div>
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-1">Expiry Date</p>
                                    <p class="text-xl font-black text-primary">{{ computedExpiryDisplay }}</p>
                                </div>

                                <!-- Clause Due Dates -->
                                <div v-if="psEnabled || hoEnabled" class="border-t border-brand-700 pt-5 space-y-3">
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800">Clause Due Dates</p>

                                    <div v-if="psEnabled" class="flex items-center justify-between">
                                        <span class="text-xs text-brand-800">Performance Security</span>
                                        <span :class="['text-xs font-black', collisionWarnings.find(w=>w.type==='ps') ? 'text-red-400' : 'text-emerald-400']">
                                            {{ psDueDateDisplay }}
                                        </span>
                                    </div>

                                    <div v-if="hoEnabled" class="flex items-center justify-between">
                                        <span class="text-xs text-brand-800">Handover</span>
                                        <span :class="['text-xs font-black', collisionWarnings.find(w=>w.type==='ho'||w.type==='ho2') ? 'text-red-400' : 'text-emerald-400']">
                                            {{ hoDueDateDisplay }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Installments Summary -->
                                <div v-if="form.installments.length > 0" class="border-t border-brand-700 pt-5">
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-2">Installments</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-brand-400">{{ form.installments.length }} installment(s)</span>
                                        <span class="text-sm font-black text-white">{{ totalInstallmentsFormatted }}</span>
                                    </div>
                                </div>

                                <!-- Priority -->
                                <div class="border-t border-brand-700 pt-5">
                                    <p class="text-[9px] font-black uppercase text-primary tracking-widest text-brand-800 mb-1">Priority</p>
                                    <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full', priorityColor[form.priority_level]]">
                                        {{ form.priority_level }}
                                    </span>
                                </div>

                                <!-- Collision status -->
                                <div class="border-t border-brand-700 pt-5 flex items-center gap-2">
                                    <CheckCircle2 v-if="!hasCollisions" class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <AlertTriangle v-else class="w-4 h-4 text-red-400 shrink-0" />
                                    <p :class="['text-xs font-bold', hasCollisions ? 'text-red-400' : 'text-emerald-600']">
                                        {{ hasCollisions ? 'Clause collision detected' : 'No clause collisions' }}
                                    </p>
                                </div>

                            </div>
                        </div>

                        <!-- Reminder card -->
                        <div class="bg-white-600 dark:bg-white-600 border border-gray-200 shadow relative overflow-hidden rounded-2xl p-5 dark:border-brand-800" >
                            <p class="text-xs font-black text-brand-800 dark:text-blue-800 text-primary mb-2 ">Key Rules</p>
                            <ul class="text-xs text-brand-900 dark:text-brand-400 text-primary space-y-1.5 list-none">
                                <li>-> Expiry is auto-calculated — not editable</li>
                                <li>-> Performance Security must settle before expiry</li>
                                <li>-> Handover must be after expiry</li>
                                <li>-> Installments must be strictly increasing</li>
                                <li>-> Drafts are only visible to you</li>
                            </ul>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.form-label {
    @apply block text-[10px] font-extrabold uppercase tracking-widest text-brand-500 mb-2;
}
.form-input {
    @apply w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl font-semibold text-sm
           bg-white dark:bg-brand-950 text-brand-900 dark:text-white
           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-all;
}
.form-error {
    @apply text-xs text-red-500 mt-1 font-medium;
}
</style>
