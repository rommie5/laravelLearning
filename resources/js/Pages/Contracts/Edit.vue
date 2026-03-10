<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import {
    FileText, Calendar, Shield, CreditCard,
    ChevronDown, ChevronUp, Plus, Trash2,
    Clock, Save, Send, Lock, AlertTriangle
} from 'lucide-vue-next'

/* ─────────────────────────────────────────────
   PROPS
───────────────────────────────────────────── */
const props = defineProps({
    contract: Object,
    role:     { type: String, default: 'Officer' },
})

const isHead    = computed(() => props.role === 'Head')
const isActive  = computed(() => props.contract.status === 'active')
const isDraft   = computed(() => props.contract.status === 'draft')

/* ─────────────────────────────────────────────
   FORM — all fields pre-filled from contract
───────────────────────────────────────────── */
const form = useForm({
    action:   'submit',
    contract_number:       props.contract.contract_number ?? '',
    contract_name:         props.contract.contract_name ?? '',
    awarded_to:            props.contract.awarded_to ?? '',
    contract_site:         props.contract.contract_site ?? '',
    priority_level:        props.contract.priority_level ?? 'low',
    contract_signing_date: props.contract.contract_signing_date ?? '',
    start_date:            props.contract.start_date ?? '',
    duration_value:        props.contract.duration_value ?? '',
    duration_unit:         props.contract.duration_unit ?? 'months',
    notes:                 props.contract.notes ?? '',
    clauses:               [],       // built on submit
    installments:          (props.contract.installments ?? []).map(i => ({
        amount:   i.amount,
        due_date: i.due_date,
    })),
})

/* ─────────────────────────────────────────────
   ACCORDION
───────────────────────────────────────────── */
const openSections = ref([1, 2, 3, 4, 5])
function toggleSection(n) {
    openSections.value = openSections.value.includes(n)
        ? openSections.value.filter(s => s !== n)
        : [...openSections.value, n]
}

/* ─────────────────────────────────────────────
   CLAUSE TOGGLES — pre-fill from existing clauses
───────────────────────────────────────────── */
const psEnabled = ref(false)
const psdays    = ref(30)
const hoEnabled = ref(false)
const hodays    = ref(60)

if (props.contract.compliance_clauses) {
    const ps = props.contract.compliance_clauses.find(c => c.clause_type === 'performance_security')
    const ho = props.contract.compliance_clauses.find(c => c.clause_type === 'handover')
    if (ps) { psEnabled.value = true; psdays.value = ps.period_days }
    if (ho) { hoEnabled.value = true; hodays.value = ho.period_days }
}

/* ─────────────────────────────────────────────
   COMPUTED EXPIRY
───────────────────────────────────────────── */
const computedExpiry = computed(() => {
    if (!form.start_date || !form.duration_value) return null
    const d = dayjs(form.start_date)
    return form.duration_unit === 'weeks'
        ? d.add(Number(form.duration_value), 'week')
        : d.add(Number(form.duration_value), 'month')
})
const computedExpiryDisplay = computed(() => computedExpiry.value?.format('D MMMM YYYY') ?? '—')

/* ─────────────────────────────────────────────
   INSTALLMENTS
───────────────────────────────────────────── */
function addInstallment()    { form.installments.push({ amount: '', due_date: '' }) }
function removeInstallment(i) { form.installments.splice(i, 1) }

/* ─────────────────────────────────────────────
   BUILD CLAUSES PAYLOAD
───────────────────────────────────────────── */
function buildClauses() {
    const clauses = []
    if (psEnabled.value) clauses.push({ clause_type: 'performance_security', period_days: Number(psdays.value) })
    if (hoEnabled.value) clauses.push({ clause_type: 'handover',             period_days: Number(hodays.value) })
    return clauses
}

/* ─────────────────────────────────────────────
   SUBMIT ACTIONS
───────────────────────────────────────────── */
function deleteContract() {
    if (confirm('Are you sure you want to delete this contract?')) {
        form.delete(route('contracts.destroy', props.contract.id))
    }
}

function submitUpdate() {
    form.clauses = buildClauses()
    form.action  = 'submit'
    form.put(route('contracts.update', props.contract.id))
}

/* ─────────────────────────────────────────────
   LABEL HELPERS
───────────────────────────────────────────── */
</script>

<template>
<Head title="Edit Contract" />

<AuthenticatedLayout>
<div class="py-8">
<div class="max-w-[1440px] mx-auto sm:px-6 lg:px-8">

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-2">
        <Lock class="w-3 h-3" /> Secure Update
    </div>
    <h1 class="text-3xl text-brand-800 dark:text-white">
        Edit: {{ contract.contract_name }}
    </h1>
    <p class="text-sm text-brand-400 mt-1">
        All fields are pre-filled. Expiry is auto-calculated from start date + duration.
    </p>

    <!-- Banner: Officer editing active contract -->
    <div v-if="!isHead && isActive"
         class="mt-4 flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl">
        <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
        <div>
            <p class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">Approval Required</p>
            <p class="text-xs text-amber-600 dark:text-amber-300 mt-0.5">
                This contract is active. Your changes will be saved as a pending update for Head review before going live.
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

<!-- LEFT SIDE -->
<div class="lg:col-span-2 space-y-5">

<!-- SECTION 1 — Contract Identification -->
<section class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
    <button @click="toggleSection(1)" type="button" class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
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
            <input v-model="form.contract_number" class="form-input" />
            <p v-if="form.errors.contract_number" class="text-xs text-red-500 mt-1">{{ form.errors.contract_number }}</p>
        </div>
        <div>
            <label class="form-label">Priority Level</label>
            <select v-model="form.priority_level" class="form-input">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="sensitive">Sensitive / Restricted</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Contract Name <span class="text-red-500">*</span></label>
            <input v-model="form.contract_name" class="form-input" />
            <p v-if="form.errors.contract_name" class="text-xs text-red-500 mt-1">{{ form.errors.contract_name }}</p>
        </div>
        <div>
            <label class="form-label">Awarded To <span class="text-red-500">*</span></label>
            <input v-model="form.awarded_to" class="form-input" />
            <p v-if="form.errors.awarded_to" class="text-xs text-red-500 mt-1">{{ form.errors.awarded_to }}</p>
        </div>
        <div>
            <label class="form-label">Contract Site</label>
            <input v-model="form.contract_site" class="form-input" />
        </div>
    </div>
</section>

<!-- SECTION 2 — Duration & Expiry -->
<section class="bg-white dark:bg-brand-900 rounded-3xl border shadow overflow-hidden">
    <button @click="toggleSection(2)" type="button" class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
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

    <div v-show="openSections.includes(2)" class="px-7 pb-7 grid grid-cols-2 gap-6">
        <div>
            <label class="form-label">Signing Date <span class="text-red-500">*</span></label>
            <input type="date" v-model="form.contract_signing_date" class="form-input" />
        </div>
        <div>
            <label class="form-label">Start Date <span class="text-red-500">*</span></label>
            <input type="date" v-model="form.start_date" class="form-input" />
        </div>
        <div>
            <label class="form-label">Duration <span class="text-red-500">*</span></label>
            <input type="number" v-model="form.duration_value" class="form-input" min="1" />
        </div>
        <div>
            <label class="form-label">Unit <span class="text-red-500">*</span></label>
            <select v-model="form.duration_unit" class="form-input">
                <option value="weeks">Weeks</option>
                <option value="months">Months</option>
            </select>
        </div>
        <div class="flex items-center gap-4 p-5 bg-primary/5 border border-primary/15 rounded-2xl col-span-2">
            <Clock class="w-4 h-4 text-primary" />
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-primary mb-0.5">Computed Expiry Date (read-only)</p>
                <p class="text-xl font-black text-brand-900 dark:text-white">{{ computedExpiryDisplay }}</p>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 3 — Compliance Clauses -->
<section class="bg-white dark:bg-brand-900 rounded-3xl border shadow overflow-hidden">
    <button @click="toggleSection(3)" type="button" class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
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
        <!-- Performance Security -->
        <div class="border border-brand-100 dark:border-brand-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-black text-brand-900 dark:text-white">Performance Security</p>
                    <p class="text-[11px] text-brand-400">Days from signing date</p>
                </div>
                <button @click="psEnabled = !psEnabled" type="button"
                        :class="['relative w-11 h-6 rounded-full transition-colors duration-200', psEnabled ? 'bg-primary' : 'bg-brand-200 dark:bg-brand-700']">
                    <span :class="['absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200', psEnabled ? 'translate-x-5' : '']"></span>
                </button>
            </div>
            <div v-if="psEnabled">
                <label class="form-label">Period (days)</label>
                <input v-model="psdays" type="number" min="1" class="form-input" />
            </div>
        </div>

        <!-- Handover -->
        <div class="border border-brand-100 dark:border-brand-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-black text-brand-900 dark:text-white">Handover</p>
                    <p class="text-[11px] text-brand-400">Days from expiry date</p>
                </div>
                <button @click="hoEnabled = !hoEnabled" type="button"
                        :class="['relative w-11 h-6 rounded-full transition-colors duration-200', hoEnabled ? 'bg-primary' : 'bg-brand-200 dark:bg-brand-700']">
                    <span :class="['absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200', hoEnabled ? 'translate-x-5' : '']"></span>
                </button>
            </div>
            <div v-if="hoEnabled">
                <label class="form-label">Period (days)</label>
                <input v-model="hodays" type="number" min="1" class="form-input" />
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4 — Installments -->
<section class="bg-white dark:bg-brand-900 rounded-3xl border shadow overflow-hidden">
    <button @click="toggleSection(4)" type="button" class="w-full flex items-center justify-between p-7 text-left hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <CreditCard class="w-4 h-4 text-purple-600" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Section 4 — Optional</p>
                <p class="text-base font-bold text-brand-900 dark:text-white">
                    Payment Installments
                    <span v-if="form.installments.length > 0" class="ml-2 text-xs font-black bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full">
                        {{ form.installments.length }}
                    </span>
                </p>
            </div>
        </div>
        <ChevronUp v-if="openSections.includes(4)" class="w-5 h-5 text-brand-400" />
        <ChevronDown v-else class="w-5 h-5 text-brand-400" />
    </button>

    <div v-show="openSections.includes(4)" class="px-7 pb-7 space-y-4">
        <div class="grid grid-cols-12 gap-3 px-1">
            <p class="col-span-1 text-[9px] font-black uppercase tracking-widest text-brand-400">#</p>
            <p class="col-span-5 text-[9px] font-black uppercase tracking-widest text-brand-400">Amount</p>
            <p class="col-span-5 text-[9px] font-black uppercase tracking-widest text-brand-400">Due Date</p>
            <p class="col-span-1"></p>
        </div>

        <div v-for="(inst, i) in form.installments" :key="i" class="grid grid-cols-12 gap-3">
            <div class="col-span-1 flex items-center">
                <span class="text-xs font-black text-brand-500">{{ i + 1 }}</span>
            </div>
            <div class="col-span-5">
                <input v-model="inst.amount" type="number" step="0.01" class="form-input text-sm py-2" placeholder="0.00" />
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

        <button @click="addInstallment" type="button"
                class="w-full py-3 px-4 border-2 border-dashed border-brand-200 dark:border-brand-700 rounded-2xl text-xs font-black uppercase tracking-widest text-brand-400 hover:border-primary hover:text-primary transition-all flex items-center justify-center gap-2">
            <Plus class="w-4 h-4" /> Add Installment
        </button>
    </div>
</section>

<!-- SECTION 5 — Notes & Actions -->
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

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Delete -->
            <button @click="deleteContract" type="button"
                    :disabled="form.processing"
                    class="flex items-center justify-center gap-3 py-4 px-6 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-black uppercase tracking-widest text-sm rounded-2xl hover:bg-red-200 dark:hover:bg-red-900/50 transition-all disabled:opacity-50">
                <Trash2 class="w-5 h-5" />
                Delete
            </button>

            <!-- Update -->
            <button @click="submitUpdate" type="button"
                    :disabled="form.processing"
                    class="flex items-center justify-center gap-3 py-4 px-6 bg-primary text-white font-black uppercase tracking-widest text-sm rounded-2xl hover:opacity-90 hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-primary/25 disabled:opacity-40">
                <Save class="w-5 h-5" />
                Update
            </button>
        </div>

        <!-- Validation errors summary -->
        <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl">
            <p class="text-xs font-black text-red-700 dark:text-red-400 uppercase tracking-widest mb-2">Please fix the following:</p>
            <ul class="space-y-1">
                <li v-for="(err, field) in form.errors" :key="field" class="text-xs text-red-600 dark:text-red-300">
                    • {{ err }}
                </li>
            </ul>
        </div>
    </div>
</section>

</div><!-- end left col -->

<!-- RIGHT SIDEBAR -->
<div class="space-y-5">

    <!-- Contract Summary Card -->
    <div class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow p-7 space-y-5 sticky top-8">
        <p class="text-[10px] font-black uppercase tracking-widest text-brand-400">Contract Summary</p>

        <div class="space-y-4">
            <div>
                <p class="label">Contract #</p>
                <p class="value font-black">{{ form.contract_number || '—' }}</p>
            </div>
            <div>
                <p class="label">Awarded To</p>
                <p class="value">{{ form.awarded_to || '—' }}</p>
            </div>
            <div>
                <p class="label">Status</p>
                <span :class="[
                    'text-[9px] font-black uppercase px-3 py-1 rounded-full tracking-widest',
                    {
                        'bg-amber-100 text-amber-700':   contract.status === 'draft',
                        'bg-blue-100 text-blue-700':     contract.status === 'pending_approval',
                        'bg-emerald-100 text-emerald-700': contract.status === 'active',
                        'bg-slate-100 text-slate-600':   !['draft','pending_approval','active'].includes(contract.status),
                    }
                ]">{{ contract.status?.replace('_', ' ') }}</span>
            </div>
            <div>
                <p class="label">Computed Expiry</p>
                <p class="value font-black text-primary">{{ computedExpiryDisplay }}</p>
            </div>
            <div>
                <p class="label">Installments</p>
                <p class="value">{{ form.installments.length }} added</p>
            </div>
            <div>
                <p class="label">Clauses</p>
                <p class="value">
                    {{ [psEnabled && 'Performance Security', hoEnabled && 'Handover'].filter(Boolean).join(', ') || 'None' }}
                </p>
            </div>
        </div>

        <!-- Cancel link -->
        <Link :href="route('contracts.show', contract.id)"
              class="block text-center text-xs font-black uppercase tracking-widest text-brand-400 hover:text-primary transition-colors mt-4">
            ← Cancel & go back
        </Link>
    </div>

</div><!-- end right col -->

</div><!-- end grid -->
</div>
</div>
</AuthenticatedLayout>
</template>

<style>
@reference "../../../css/app.css";

.form-input {
    @apply w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl font-semibold text-sm
           bg-white dark:bg-brand-950 text-brand-900 dark:text-white
           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-all;
}

.label {
    @apply text-[10px] font-black uppercase tracking-widest text-brand-400 mb-1;
}

.value {
    @apply text-sm font-semibold text-brand-900 dark:text-white;
}
</style>