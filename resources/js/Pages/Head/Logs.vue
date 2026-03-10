<script setup>
// Head/Logs.vue — reuses the same audit log UI as Admin/Logs.vue
// AuditLogController::index renders this view for Head users.
</script>

<template>
    <!-- Delegate to Admin/Logs — all logic there is role-aware already -->
    <AdminLogs v-bind="$attrs" />
</template>

<script>
import AdminLogs from '@/Pages/Admin/Logs.vue';
export default { components: { AdminLogs } };
</script>
