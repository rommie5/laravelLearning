<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    Calendar, FileText, Users, DollarSign, Scale, History,
    Clock, AlertTriangle, CheckCircle2, Shield, Lock,
    X, Check, ChevronRight, Eye, CreditCard, Activity, ChevronDown, Trash,
    ChevronUp,
    ChevronDownCircle
} from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps({ contract: Object });

const _page = usePage();
const user  = computed(() => _page.props.auth.user);
const role  = computed(() => user.value?.roles?.[0] ?? '');
const pendingUpdate = computed(() => props.contract.updates?.find(u => u.status === 'pending') || null);

const activeTab              = ref('overview');
const isApprovalModalOpen      = ref(false);
const isRejectionModalOpen     = ref(false);
const isRejectUpdateModalOpen  = ref(false);
const isTerminateModalOpen     = ref(false);
const isCloseModalOpen         = ref(false);
const isNotificationsOpen      = ref(false)
const rejectionReason          = ref('');
const rejectUpdateReason       = ref('');
const terminateReason          = ref('');
const closeReason              = ref('');

const selectedUpdateId         = ref(null);

const tabs = [
    { id: 'overview',   name: 'Overview',     icon: Eye        },
    { id: 'clauses',    name: 'Clauses',       icon: Scale      },
    { id: 'financials', name: 'Installments',  icon: CreditCard },
    { id: 'history',    name: 'History',       icon: History    },
];

const statusBadge = {
    draft:            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    pending_approval: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    active:           'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    pending_update:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    terminated:       'bg-slate-200 text-slate-700',
    closed:           'bg-slate-200 text-slate-700',
};

const priorityBadge = {
    low:       'bg-blue-100 text-blue-700',
    medium:    'bg-amber-100 text-amber-700',
    high:      'bg-orange-100 text-orange-700',
    sensitive: 'bg-red-100 text-red-700',
};

const clauseStatusBadge = {
    pending:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    active:     'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    expired:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    completed:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    terminated: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    waived:     'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
};

// ── Clause action state ──────────────────────────────────────────────────────
const clauseDropdownOpen         = ref(null); // clause.id of currently open dropdown
const isClauseTerminateModalOpen = ref(false);
const isClauseOverrideModalOpen  = ref(false);
const isClauseRequestModalOpen   = ref(false);
const selectedClause             = ref(null);
const clauseTerminateReason      = ref('');
const clauseOverrideStatus       = ref('');
const clauseOverrideReason       = ref('');

const overridableStatuses = [
    { value: 'pending',    label: 'Pending' },
    { value: 'active',     label: 'Active' },
    { value: 'completed',  label: 'Completed' },
    { value: 'terminated', label: 'Terminated' },
    { value: 'waived',     label: 'Waived' },
];

function toggleClauseDropdown(clauseId) {
    clauseDropdownOpen.value = clauseDropdownOpen.value === clauseId ? null : clauseId;
}

function isClauseLocked(clause) {
    return ['expired', 'terminated', 'completed'].includes(clause.status);
}

function markClauseCompleted(clause) {
    clauseDropdownOpen.value = null;
    router.post(route('clauses.complete', clause.id));
}

function openRequestTermination(clause) {
    clauseDropdownOpen.value = null;
    selectedClause.value = clause;
    clauseTerminateReason.value = '';
    isClauseRequestModalOpen.value = true;
}

function submitRequestTermination() {
    router.post(route('clauses.request-termination', selectedClause.value.id), {
        reason: clauseTerminateReason.value,
    }, {
        onSuccess: () => { isClauseRequestModalOpen.value = false; clauseTerminateReason.value = ''; },
    });
}

function openClauseTerminate(clause) {
    clauseDropdownOpen.value = null;
    selectedClause.value = clause;
    clauseTerminateReason.value = '';
    isClauseTerminateModalOpen.value = true;
}

function submitClauseTerminate() {
    router.post(route('clauses.terminate', selectedClause.value.id), {
        reason: clauseTerminateReason.value,
    }, {
        onSuccess: () => { isClauseTerminateModalOpen.value = false; clauseTerminateReason.value = ''; },
    });
}

function openClauseOverride(clause) {
    clauseDropdownOpen.value = null;
    selectedClause.value = clause;
    clauseOverrideStatus.value = '';
    clauseOverrideReason.value = '';
    isClauseOverrideModalOpen.value = true;
}

function submitClauseOverride() {
    router.post(route('clauses.override', selectedClause.value.id), {
        status: clauseOverrideStatus.value,
        reason: clauseOverrideReason.value,
    }, {
        onSuccess: () => { isClauseOverrideModalOpen.value = false; },
    });
}

function markInstallmentPaid(installment) {
    router.post(route('installments.pay', installment.id));
}

const paidStatusBadge = {
    pending: 'bg-amber-100 text-amber-700',
    paid:    'bg-emerald-100 text-emerald-700',
    overdue: 'bg-red-100 text-red-700',
};

const isDropdownOpen = ref(false)

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value
}

const handleApprove = () => {
    isApprovalModalOpen.value = true
    isDropdownOpen.value = false
}

const handleReject = () => {
    isRejectionModalOpen.value = true
    isDropdownOpen.value = false
}

function approve() {
    router.post(route('contracts.approve', props.contract.id), {}, {
        onSuccess: () => isApprovalModalOpen.value = false,
    });
}
function reject() {
    router.post(route('contracts.reject', props.contract.id), { reason: rejectionReason.value }, {
        onSuccess: () => { isRejectionModalOpen.value = false; rejectionReason.value = ''; }
    });
}
function terminate() {
    router.post(route('contracts.terminate', props.contract.id), { reason: terminateReason.value }, {
        onSuccess: () => { isTerminateModalOpen.value = false; terminateReason.value = ''; }
    });
}
function closeContract() {
    router.post(route('contracts.close', props.contract.id), { reason: closeReason.value }, {
        onSuccess: () => { isCloseModalOpen.value = false; closeReason.value = ''; }
    });
}
function submitDraft() {
    router.post(route('contracts.submit', props.contract.id));
}

function handleRejectUpdate(updateId) {
    selectedUpdateId.value = updateId;
    isRejectUpdateModalOpen.value = true;
    isDropdownOpen.value = false;
}

function approveUpdate(updateId) {
    router.post(route('contracts.updates.approve', [props.contract.id, updateId]));
}

function rejectUpdate() {
    router.post(route('contracts.updates.reject', [props.contract.id, selectedUpdateId.value]), {
        reason: rejectUpdateReason.value
    }, {
        onSuccess: () => { isRejectUpdateModalOpen.value = false; rejectUpdateReason.value = ''; }
    });
}

function gotoEdit() {
    router.visit(route('contracts.edit', props.contract.id));
}

function destroy() {
    if (confirm('Are you sure you want to delete this contract? This action cannot be undone.')) {
        router.delete(route('contracts.destroy', props.contract.id));
    }
}





</script>

<template>
    <Head :title="'Contract: ' + contract.contract_number" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

            <!-- ── Breadcrumb + Header ── -->
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="space-y-3">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-brand-400">
                        <Link :href="route('contracts.index')" class="hover:text-primary transition-colors flex items-center gap-1">
                            <ChevronRight class="w-3 h-3 rotate-180" /> Registry
                        </Link>
                        <span>/</span>
                        <span class="text-primary">{{ contract.contract_number }}</span>
                    </div>

                    <!-- Title + Status -->
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold text-brand-800 dark:text-white tracking-tight">
                            {{ contract.contract_name }}
                        </h1>
                        <span v-if="pendingUpdate" :class="['text-[9px] font-black uppercase px-3 py-1 rounded-full tracking-widest', statusBadge['pending_update']]">
                            PENDING UPDATE
                        </span>
                        <span v-else :class="['text-[9px] font-black uppercase px-3 py-1 rounded-full tracking-widest', statusBadge[contract.status] ?? 'bg-gray-100 text-gray-600']">
                            {{ contract.status?.replace('_', ' ') }}
                        </span>
                        <span :class="['text-[9px] font-black uppercase px-3 py-1 rounded-full tracking-widest', priorityBadge[contract.priority_level] ?? 'bg-gray-100 text-gray-600']">
                            {{ contract.priority_level }}
                        </span>
                    </div>

                    <p class="text-sm text-brand-400 flex items-center gap-2">
                        <Lock class="w-3 h-3" />
                        Awarded to {{ contract.awarded_to }}
                        <span v-if="contract.contract_site"> · {{ contract.contract_site }}</span>
                    </p>
                </div>

                <!-- Role-Based Actions -->
                <div class="flex flex-wrap items-center gap-3">

                    <!-- ── HEAD ACTIONS ── -->
                    <div v-if="role === 'Head'" class="relative inline-block text-left">

                        <!-- Dropdown Button -->
                        <button
                            @click="toggleDropdown"
                            class="flex items-center gap-2 px-5 py-2.5 bg-white border border-brand-300 text-brand-700 hover:shadow hover:border-brand-600 dark:border-brand-200 dark:bg-brand-800 dark:text-brand-300 dark:border-brand-600 dark:hover:bg-brand-700 rounded-xl text-sm font-semibold transition">
                            Actions
                            <ChevronUp v-if="isDropdownOpen" class="w-5 h-5 text-brand-500" />
                            <ChevronDown v-else class="w-5 h-5 text-brand-500" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-52 bg-white dark:bg-brand-900 border border-brand-200 dark:border-brand-800 rounded-2xl shadow-2xl z-50 overflow-hidden">
                            <div class="flex flex-col p-2 gap-1">

                                <!-- Approve / Reject for pending_approval -->
                                <template v-if="contract.status === 'pending_approval'">
                                    <button @click="handleApprove"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all">
                                        ✓ Approve
                                    </button>
                                    <button @click="handleReject"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-red-700 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                                        ✕ Reject
                                    </button>
                                </template>

                                <!-- Approve / Reject pending update -->
                                <template v-if="pendingUpdate">
                                    <button @click="approveUpdate(pendingUpdate.id)"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all">
                                        ✓ Approve Update
                                    </button>
                                    <button @click="handleRejectUpdate(pendingUpdate.id)"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-red-700 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                                        ✕ Reject Update
                                    </button>
                                </template>

                                <!-- Terminate / Close — active contracts only -->
                                <template v-if="contract.status === 'active'">
                                    <button @click="isTerminateModalOpen = true; isDropdownOpen = false"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-red-700 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                                        ⊘ Terminate
                                    </button>
                                    <button @click="isCloseModalOpen = true; isDropdownOpen = false"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-600 hover:bg-slate-100 dark:hover:bg-slate-400 transition-all">
                                        ✕ Close Contract
                                    </button>
                                </template>

                                <template v-if="contract.status=='draft'">
                                    <button @click="submitDraft(); isDropdownOpen = false"
                                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-primary bg-primary/20 hover:bg-primary/30 transition-all">
                                         Submit Draft
                                    </button>
                                </template>

                                <!-- Edit/Update — shown unless terminated or closed -->
                                <button v-if="!['terminated', 'closed'].includes(contract.status)"
                                        @click="gotoEdit(); isDropdownOpen = false"
                                        class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-brand-700 bg-brand-50 dark:bg-brand-800 dark:text-brand-200 hover:bg-brand-100 dark:hover:bg-brand-700 transition-all">
                                    ✎ Edit / Update
                                </button>

                                <!-- Delete — draft only -->
                                <button v-if="contract.status === 'draft'"
                                        @click="destroy(); isDropdownOpen = false"
                                        class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-red-700 bg-red-50 dark:bg-red-900 dark:text-red-100 hover:bg-red-100 dark:hover:bg-red-800 transition-all">
                                    🗑 Delete Draft
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- ── OFFICER ACTIONS ── -->
                    <template v-if="role === 'Officer'">
                        <!-- Draft: Submit for Approval + Delete + Edit -->
                        <template v-if="contract.status === 'draft'">
                            <button @click="submitDraft"
                                    class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-2xl text-sm font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
                                Submit for Approval
                            </button>
                            <button @click="gotoEdit"
                                    class="flex items-center gap-2 px-6 py-3 bg-white border border-brand-300 dark:bg-brand-800 dark:border-brand-600 dark:text-brand-300 text-brand-600 font-black uppercase text-sm tracking-widest rounded-2xl hover:shadow hover:border-brand-600 dark:hover:bg-brand-700 transition-all">
                                Edit Draft
                            </button>
                            <button @click="destroy"
                                    class="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest shadow-lg shadow-red-600/20 hover:scale-105 active:scale-95 transition-all">
                                <Trash class="w-4 h-4" /> Delete
                            </button>
                        </template>

                        <!-- Active or Pending Approval: Edit only -->
                        <template v-if="['active', 'pending_approval'].includes(contract.status)">
                            <button @click="gotoEdit"
                                    class="flex items-center gap-2 px-6 py-3 bg-white border border-brand-300 dark:bg-brand-800 dark:border-brand-600 dark:text-brand-300 text-brand-600 font-black uppercase text-sm tracking-widest rounded-2xl hover:shadow hover:border-brand-600 dark:hover:bg-brand-700 transition-all">
                                Edit Contract
                            </button>
                        </template>
                    </template>

                </div>
            </div>

            <!-- ── Tabs ── -->
            <div class="flex overflow-x-auto no-scrollbar
         sm:overflow-visible
         gap-1 mb-8
         bg-brand-50/50 dark:bg-brand-950/50
         p-1.5 rounded-2xl
         border border-brand-100 dark:border-brand-800">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        :class="['flex items-center gap-2 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                            activeTab === tab.id
                                ? 'bg-white dark:bg-brand-800 text-primary shadow-sm'
                                : 'text-brand-400 hover:text-brand-600']">
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.name }}
                </button>
            </div>

            <!-- ── Tab Content ── -->
            <div>

                <!-- OVERVIEW -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Left: contract details -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Core fields -->
                        <div class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow p-8">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-brand-700 mb-6 flex items-center gap-2">
                                <Shield class="w-4 h-4 text-primary" /> Contract Details
                            </h3>
                            <div class="grid grid-cols-2 gap-y-6 gap-x-10">
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Contract Number</p>
                                    <p class="value">{{ contract.contract_number }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Awarded To</p>
                                    <p class="value">{{ contract.awarded_to }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Contract Site</p>
                                    <p class="value">{{ contract.contract_site || '—' }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Priority</p>
                                    <p class="value">{{ contract.priority_level }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Signing Date</p>
                                    <p class="value">{{ contract.contract_signing_date ? dayjs(contract.contract_signing_date).format('D MMM YYYY') : '—' }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Start Date</p>
                                    <p class="value">{{ contract.start_date ? dayjs(contract.start_date).format('D MMM YYYY') : '—' }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Duration</p>
                                    <p class="value">{{ contract.duration_value }} {{ contract.duration_unit }}</p>
                                </div>
                                <div class="text-brand-500 dark:text-brand-400">
                                    <p class="label">Expiry Date</p>
                                    <p class="value font-black text-primary">{{ contract.expiry_date ? dayjs(contract.expiry_date).format('D MMM YYYY') : '—' }}</p>
                                </div>
                                <div v-if="contract.notes" class="col-span-2">
                                    <p class="label">Notes</p>
                                    <p class="text-sm text-brand-600 dark:text-brand-300 leading-relaxed">{{ contract.notes }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Termination / Close info -->
                        <div v-if="contract.termination_reason || contract.close_reason"
                             class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-6">
                            <p class="text-[10px] font-black uppercase tracking-widest text-red-500 mb-2">
                                {{ contract.termination_reason ? 'Termination Reason' : 'Close Reason' }}
                            </p>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                {{ contract.termination_reason ?? contract.close_reason }}
                            </p>
                        </div>
                    </div>

                    <!-- Right: timeline -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow p-8">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-primary mb-6">Execution Timeline</h3>
                            <div class="space-y-6 relative">
                                <div class="absolute left-1.5 top-0 bottom-0 w-0.5 bg-brand-800"></div>

                                <div class="flex items-start gap-4 relative">
                                    <div class="w-4 h-4 rounded-full bg-primary border-4 border-brand-900 z-10 shrink-0"></div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-brand-400">Created</p>
                                        <p class="text-xs font-bold">{{ dayjs(contract.created_at).format('D MMM YYYY') }}</p>
                                        <p class="text-[9px] text-brand-500">by {{ contract.creator?.name }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 relative">
                                    <div :class="['w-4 h-4 rounded-full border-4 border-brand-900 z-10 shrink-0', contract.approved_at ? 'bg-primary' : 'bg-brand-700']"></div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-brand-400">Approved</p>
                                        <p class="text-xs font-bold">{{ contract.approved_at ? dayjs(contract.approved_at).format('D MMM YYYY') : 'Pending' }}</p>
                                        <p v-if="contract.approver" class="text-[9px] text-brand-500">by {{ contract.approver?.name }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 relative">
                                    <div :class="['w-4 h-4 rounded-full border-4 border-brand-900 z-10 shrink-0', contract.status === 'active' ? 'bg-emerald-500' : 'bg-brand-700']"></div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-brand-400">Status</p>
                                        <p class="text-xs font-bold capitalize">{{ contract.status?.replace('_', ' ') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 relative">
                                    <div :class="['w-4 h-4 rounded-full border-4 border-brand-900 z-10 shrink-0', contract.expiry_date ? 'bg-amber-500' : 'bg-brand-700']"></div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-brand-400">Expiry</p>
                                        <p class="text-xs font-black text-primary">{{ contract.expiry_date ? dayjs(contract.expiry_date).format('D MMM YYYY') : '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CLAUSES TAB -->
                <div v-if="activeTab === 'clauses'" class="space-y-5">
                    <div v-if="contract.compliance_clauses?.length === 0 || !contract.compliance_clauses" class="text-center py-16 text-brand-400">
                        <Scale class="w-10 h-10 mx-auto mb-3 opacity-30" />
                        <p class="text-sm font-semibold">No compliance clauses were added to this contract.</p>
                    </div>

                    <div v-for="clause in contract.compliance_clauses" :key="clause.id"
                         class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow p-8">

                        <!-- Clause header: title + status badge + action dropdown -->
                        <div class="flex items-start justify-between mb-6 gap-4">
                            <div class="flex-1">
                                <p class="text-[9px] font-black uppercase tracking-widest text-brand-400 mb-1">{{ clause.reference_date_type?.replace('_', ' ') }} + {{ clause.period_days }} days</p>
                                <h4 class="text-lg font-black text-brand-900 dark:text-white capitalize">
                                    {{ clause.clause_type?.replace('_', ' ') }}
                                </h4>
                                <!-- Termination reason note -->
                                <p v-if="clause.status_reason && isClauseLocked(clause)" class="text-[10px] text-brand-400 mt-1 italic">
                                    {{ clause.status_reason }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <!-- Status badge -->
                                <span :class="['text-[9px] font-black uppercase px-3 py-1 rounded-full tracking-widest', clauseStatusBadge[clause.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ clause.status }}
                                </span>

                                <!-- Action dropdown (only when NOT locked) -->
                                <div v-if="!isClauseLocked(clause)" class="relative">
                                    <button @click="toggleClauseDropdown(clause.id)"
                                            class="flex items-center gap-1.5 px-4 py-2 bg-brand-50 dark:bg-brand-800 text-brand-600 dark:text-brand-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-brand-100 dark:hover:bg-brand-700 transition-all">
                                        Actions
                                        <ChevronDown class="w-3 h-3" />
                                    </button>

                                    <div v-if="clauseDropdownOpen === clause.id"
                                         class="absolute right-0 mt-2 w-52 bg-white dark:bg-brand-900 border border-brand-100 dark:border-brand-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                                        <div class="flex flex-col p-2 gap-1">

                                            <!-- Mark Completed — Officer & Head -->
                                            <button @click="markClauseCompleted(clause)"
                                                    class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all">
                                                ✓ Mark Completed
                                            </button>

                                            <!-- Request Termination — Officer only -->
                                            <button v-if="role === 'Officer'"
                                                    @click="openRequestTermination(clause)"
                                                    class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-amber-700 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-all">
                                                ⚠ Request Termination
                                            </button>

                                            <!-- Terminate + Override — Head only -->
                                            <template v-if="role === 'Head'">
                                                <button @click="openClauseTerminate(clause)"
                                                        class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-red-700 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                                                    ✕ Terminate
                                                </button>
                                                <button @click="openClauseOverride(clause)"
                                                        class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-black text-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                                    ↺ Override Status
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Locked indicator -->
                                <span v-else class="text-[9px] text-brand-300 dark:text-brand-600 font-black uppercase tracking-widest">
                                    <Lock class="w-3 h-3 inline" /> Locked
                                </span>
                            </div>
                        </div>

                        <!-- Clause detail fields -->
                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <p class="label">Due Date</p>
                                <p class="value font-black text-primary">{{ clause.due_date ? dayjs(clause.due_date).format('D MMM YYYY') : '—' }}</p>
                            </div>
                            <div>
                                <p class="label">Period</p>
                                <p class="value">{{ clause.period_days }} days</p>
                            </div>
                            <div v-if="clause.completed_at">
                                <p class="label">Completed</p>
                                <p class="value text-emerald-600">{{ dayjs(clause.completed_at).format('D MMM YYYY') }}</p>
                            </div>
                            <div v-if="clause.status_changed_at">
                                <p class="label">Last Changed</p>
                                <p class="value text-brand-400">{{ dayjs(clause.status_changed_at).format('D MMM YYYY') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- INSTALLMENTS TAB -->
                <div v-if="activeTab === 'financials'" class="space-y-6">
                    <div v-if="contract.installments?.length === 0 || !contract.installments" class="text-center py-16 text-brand-400">
                        <CreditCard class="w-10 h-10 mx-auto mb-3 opacity-30" />
                        <p class="text-sm font-semibold">No payment installments were added to this contract.</p>
                    </div>

                    <div v-else>
                        <!-- Summary -->
                        <div class="grid grid-cols-3 gap-6 mb-6">
                            <div class="bg-white dark:bg-brand-900 rounded-2xl border border-brand-100 dark:border-brand-800 p-6 shadow">
                                <p class="label">Total Installments</p>
                                <p class="text-2xl font-black text-brand-900 dark:text-white">{{ contract.installments.length }}</p>
                            </div>
                            <div class="bg-white dark:bg-brand-900 rounded-2xl border border-brand-100 dark:border-brand-800 p-6 shadow">
                                <p class="label">Total Amount</p>
                                <p class="text-2xl font-black text-brand-900 dark:text-white">
                                    {{ new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(contract.installments.reduce((s, i) => s + parseFloat(i.amount), 0)) }}
                                </p>
                            </div>
                            <div class="bg-white dark:bg-brand-900 rounded-2xl border border-brand-100 dark:border-brand-800 p-6 shadow">
                                <p class="label">Paid</p>
                                <p class="text-2xl font-black text-emerald-600">
                                    {{ contract.installments.filter(i => i.paid_status === 'paid').length }} / {{ contract.installments.length }}
                                </p>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 shadow overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-brand-50 dark:bg-brand-950/50">
                                    <tr>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">#</th>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Due Date</th>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Amount</th>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Status</th>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Paid At</th>
                                        <th class="px-8 py-4 text-[9px] font-black uppercase tracking-widest text-brand-400">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-50 dark:divide-brand-800">
                                    <tr v-for="inst in contract.installments" :key="inst.id" class="hover:bg-brand-50/30 transition-colors">
                                        <td class="px-8 py-5 text-sm font-black text-brand-500">{{ inst.installment_no }}</td>
                                        <td class="px-8 py-5 text-sm font-bold text-brand-900 dark:text-brand-100">{{ inst.due_date ? dayjs(inst.due_date).format('D MMM YYYY') : '—' }}</td>
                                        <td class="px-8 py-5 text-sm font-black text-brand-900 dark:text-brand-100">{{ Number(inst.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
                                        <td class="px-8 py-5">
                                            <span :class="['text-[9px] font-black uppercase px-2 py-1 rounded-full tracking-widest', paidStatusBadge[inst.paid_status] ?? 'bg-gray-100 text-gray-600']">
                                                {{ inst.paid_status }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-sm text-brand-400">{{ inst.paid_at ? dayjs(inst.paid_at).format('D MMM YYYY') : '—' }}</td>
                                        <td class="px-8 py-5">
                                            <button v-if="inst.paid_status !== 'paid'"
                                                    @click="markInstallmentPaid(inst)"
                                                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 hover:scale-105 active:scale-95 transition-all shadow-sm">
                                                Mark Paid
                                            </button>
                                            <span v-else class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">✓ Paid</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- HISTORY TAB -->
                <div v-if="activeTab === 'history'" class="bg-white dark:bg-brand-900 rounded-3xl border border-brand-100 dark:border-brand-800 p-8 shadow">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-brand-400 mb-8 flex items-center gap-2">
                        <History class="w-4 h-4" /> Audit History
                    </h3>
                    <div class="space-y-6 relative">
                        <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-brand-100 dark:bg-brand-800"></div>

                        <div class="flex items-start gap-4 rotate-0 relative">
                            <div class="w-5 h-5 rounded-full bg-primary shrink-0 z-10"></div>
                            <div>
                                <p class="text-xs font-black text-brand-900 dark:text-white">Contract Created</p>
                                <p class="text-[10px] text-brand-400">{{ dayjs(contract.created_at).format('D MMM YYYY HH:mm') }} · by {{ contract.creator?.name }}</p>
                            </div>
                        </div>

                        <div v-if="contract.approved_at" class="flex items-start gap-4 relative">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 shrink-0 z-10"></div>
                            <div>
                                <p class="text-xs font-black text-brand-900 dark:text-white">Contract Approved</p>
                                <p class="text-[10px] text-brand-400">{{ dayjs(contract.approved_at).format('D MMM YYYY HH:mm') }} · by {{ contract.approver?.name }}</p>
                            </div>
                        </div>

                        <div v-if="contract.status === 'terminated'" class="flex items-start gap-4 relative">
                            <div class="w-5 h-5 rounded-full bg-red-500 shrink-0 z-10"></div>
                            <div>
                                <p class="text-xs font-black text-red-600">Contract Terminated</p>
                                <p class="text-[10px] text-brand-400">{{ contract.termination_reason }}</p>
                            </div>
                        </div>

                        <div v-if="contract.status === 'closed'" class="flex items-start gap-4 relative">
                            <div class="w-5 h-5 rounded-full bg-slate-500 shrink-0 z-10"></div>
                            <div>
                                <p class="text-xs font-black text-slate-600">Contract Closed</p>
                                <p class="text-[10px] text-brand-400">{{ contract.close_reason }}</p>
                            </div>
                        </div>

                        <!-- Pending updates -->
                        <template v-if="contract.updates?.length > 0">
                            <div v-for="upd in contract.updates" :key="upd.id" class="flex items-start gap-4 relative">
                                <div :class="['w-5 h-5 rounded-full shrink-0 z-10', upd.status === 'approved' ? 'bg-emerald-400' : upd.status === 'rejected' ? 'bg-red-400' : 'bg-amber-400']"></div>
                                <div>
                                    <p class="text-xs font-black text-brand-900 dark:text-white">Update {{ upd.status }} by {{ upd.requester?.name }}</p>
                                    <p v-if="upd.rejection_reason" class="text-[10px] text-red-400">{{ upd.rejection_reason }}</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>

    <Teleport to="body">
    <!-- Approve Modal -->
    <div v-if="isApprovalModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <h3 class="text-xl font-black text-brand-900 dark:text-white">Approve Contract?</h3>
            <p class="text-sm text-brand-400">This will set the contract to <strong>Active</strong> and notify the Contract Officer.</p>
            <div class="flex gap-4">
                <button @click="approve" class="flex-1 py-4 bg-emerald-600 text-white font-black uppercase text-xs tracking-widest rounded-2xl hover:opacity-90 transition-all">Confirm Approval</button>
                <button @click="isApprovalModalOpen = false" class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="isRejectionModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <h3 class="text-xl font-black text-red-600">Reject Contract</h3>
            <textarea v-model="rejectionReason" rows="4" class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-primary/30 focus:outline-none" placeholder="Reason for rejection..."></textarea>
            <div class="flex gap-4">
                <button @click="reject" :disabled="!rejectionReason" class="flex-1 py-4 bg-red-600 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:opacity-90 transition-all">Confirm Rejection</button>
                <button @click="isRejectionModalOpen = false" class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Terminate Contract Modal -->
    <div v-if="isTerminateModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <h3 class="text-xl font-black text-red-600">Terminate Contract</h3>
            <textarea v-model="terminateReason" rows="4" class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-red-300 focus:outline-none" placeholder="Reason for termination..."></textarea>
            <div class="flex gap-4">
                <button @click="terminate" :disabled="!terminateReason" class="flex-1 py-4 bg-red-600 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:opacity-90 transition-all">Confirm Termination</button>
                <button @click="isTerminateModalOpen = false" class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Close Modal -->
    <div v-if="isCloseModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <h3 class="text-xl font-black text-brand-900 dark:text-white">Close Contract</h3>
            <textarea v-model="closeReason" rows="4" class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-primary/30 focus:outline-none" placeholder="Reason for closing..."></textarea>
            <div class="flex gap-4">
                <button @click="closeContract" :disabled="!closeReason" class="flex-1 py-4 bg-slate-700 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:opacity-90 transition-all">Confirm Close</button>
                <button @click="isCloseModalOpen = false" class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ── Clause Modals ─────────────────────────────────────────────────── -->

    <!-- Request Termination Modal (Officer) -->
    <div v-if="isClauseRequestModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-amber-500 mb-1">Clause Action</p>
                <h3 class="text-xl font-black text-brand-900 dark:text-white">Request Termination</h3>
                <p class="text-sm text-brand-400 mt-2">This will notify all Heads to review and terminate this clause. The clause status will not change until a Head confirms.</p>
            </div>
            <textarea v-model="clauseTerminateReason" rows="4"
                      class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-amber-300 focus:outline-none"
                      placeholder="Reason for requesting termination..."></textarea>
            <div class="flex gap-4">
                <button @click="submitRequestTermination" :disabled="!clauseTerminateReason"
                        class="flex-1 py-4 bg-amber-500 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:bg-amber-600 transition-all">
                    Send Request
                </button>
                <button @click="isClauseRequestModalOpen = false"
                        class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Terminate Clause Modal (Head) -->
    <div v-if="isClauseTerminateModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-red-500 mb-1">Clause Action</p>
                <h3 class="text-xl font-black text-red-600">Terminate Clause</h3>
                <p v-if="selectedClause" class="text-sm text-brand-400 mt-2 capitalize">
                    {{ selectedClause.clause_type?.replace('_', ' ') }} — this action is permanent and cannot be undone.
                </p>
            </div>
            <textarea v-model="clauseTerminateReason" rows="4"
                      class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-red-300 focus:outline-none"
                      placeholder="Required: reason for termination..."></textarea>
            <div class="flex gap-4">
                <button @click="submitClauseTerminate" :disabled="!clauseTerminateReason"
                        class="flex-1 py-4 bg-red-600 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:opacity-90 transition-all">
                    Confirm Termination
                </button>
                <button @click="isClauseTerminateModalOpen = false"
                        class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Override Status Modal (Head) -->
    <div v-if="isClauseOverrideModalOpen" class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen z-[2147483647] flex items-center justify-center p-4 bg-brand-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-brand-900 w-full max-w-md rounded-3xl p-10 shadow-2xl border border-brand-100 dark:border-brand-800 space-y-6">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Head Override</p>
                <h3 class="text-xl font-black text-brand-900 dark:text-white">Override Clause Status</h3>
                <p class="text-sm text-brand-400 mt-2">Select the new status and provide a reason. This will be logged in the audit trail.</p>
            </div>
            <select v-model="clauseOverrideStatus"
                    class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-primary/30 focus:outline-none">
                <option value="" disabled>Select new status...</option>
                <option v-for="s in overridableStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <textarea v-model="clauseOverrideReason" rows="3"
                      class="w-full px-4 py-3 border border-brand-200 dark:border-brand-700 rounded-xl text-sm dark:bg-brand-950 dark:text-white focus:ring-1 focus:ring-primary/30 focus:outline-none"
                      placeholder="Required: reason for override..."></textarea>
            <div class="flex gap-4">
                <button @click="submitClauseOverride" :disabled="!clauseOverrideStatus || !clauseOverrideReason"
                        class="flex-1 py-4 bg-slate-700 text-white font-black uppercase text-xs tracking-widest rounded-2xl disabled:opacity-40 hover:opacity-90 transition-all">
                    Apply Override
                </button>
                <button @click="isClauseOverrideModalOpen = false"
                        class="px-6 py-4 bg-brand-50 dark:bg-brand-800 text-brand-500 font-black uppercase text-xs tracking-widest rounded-2xl">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    </Teleport>

</template>
