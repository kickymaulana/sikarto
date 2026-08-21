<script setup lang="ts">
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Dialog, Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: {
        data: Array<any>;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    roles: string[];
}>();

const roleSel = reactive<Record<number, string>>({});
props.users.data.forEach((u) => {
    roleSel[u.id] = (u.requested_role ?? u.roles?.[0]?.name) ?? 'inspector';
});

const approve = (u: any) => {
    router.post(route('users.approve', { user: u.id }), { role: roleSel[u.id] }, {
        preserveScroll: true,
        onError: () => Snackbar.error('Gagal menyetujui user.'),
    });
};

const changeRole = (u: any) => {
    router.post(route('users.role', { user: u.id }), { role: roleSel[u.id] }, {
        preserveScroll: true,
        onError: () => Snackbar.error('Gagal mengubah role.'),
    });
};

const deactivate = (u: any) => {
    Dialog({
        title: 'Nonaktifkan User',
        message: `Nonaktifkan "${u.name}"?`,
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(route('users.deactivate', { user: u.id }), {}, {
                preserveScroll: true,
                onError: () => Snackbar.error('Gagal menonaktifkan.'),
            });
        },
    });
};

const pendingUsers = props.users.data.filter((u) => !u.is_approved);
const activeUsers = props.users.data.filter((u) => u.is_approved);
</script>

<template>
    <h2 class="page-title">👥 Kelola Pengguna</h2>

    <div class="section-head">
        <h3>Menunggu Persetujuan ({{ pendingUsers.length }})</h3>
    </div>

    <div v-if="pendingUsers.length === 0" class="empty">Tidak ada user menunggu persetujuan.</div>

    <div v-for="u in pendingUsers" :key="u.id" class="user-card pending">
        <div class="user-info">
            <span class="user-name">{{ u.name }}</span>
            <span class="user-meta">{{ u.email }} • NIK: {{ u.nik }}</span>
            <var-chip type="warning" size="mini" round>MENUNGGU</var-chip>
        </div>
        <div class="user-actions">
            <var-select
                v-model="roleSel[u.id]"
                placeholder="Role"
                size="small"
                :options="roles.map((r) => ({ label: r, value: r }))"
            />
            <var-button type="primary" size="small" @click="approve(u)">Setujui</var-button>
        </div>
    </div>

    <div class="section-head">
        <h3>Aktif ({{ activeUsers.length }})</h3>
    </div>

    <div v-for="u in activeUsers" :key="u.id" class="user-card">
        <div class="user-info">
            <span class="user-name">{{ u.name }}</span>
            <span class="user-meta">{{ u.email }} • NIK: {{ u.nik }}</span>
            <var-chip type="success" size="mini" round>{{ u.roles?.[0]?.name ?? '—' }}</var-chip>
        </div>
        <div class="user-actions">
            <var-select
                v-model="roleSel[u.id]"
                placeholder="Role"
                size="small"
                :options="roles.map((r) => ({ label: r, value: r }))"
            />
            <var-button size="small" text @click="changeRole(u)">Ubah Role</var-button>
            <var-button size="small" text type="danger" @click="deactivate(u)">Nonaktifkan</var-button>
        </div>
    </div>

    <div class="pagination">
        <span
            v-if="users.current_page > 1"
            class="page-btn"
            @click="router.get(route('users.index', { page: users.current_page - 1 }), {}, { preserveScroll: true, preserveState: true })"
        >
            Sebelumnya
        </span>
        <span class="page-info">{{ users.from }}–{{ users.to }} dari {{ users.total }}</span>
        <span
            v-if="users.current_page < users.last_page"
            class="page-btn"
            @click="router.get(route('users.index', { page: users.current_page + 1 }), {}, { preserveScroll: true, preserveState: true })"
        >
            Selanjutnya
        </span>
    </div>
</template>

<style scoped>
.section-head {
    margin-top: 8px;
}

.section-head h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}

.empty {
    text-align: center;
    padding: 20px;
    color: #94a3b8;
    font-size: 13px;
}

.user-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #f1f5f9;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.user-card.pending {
    border-color: #fde68a;
    background: #fffbeb;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.user-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.user-meta {
    font-size: 12px;
    color: #64748b;
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    font-size: 13px;
    padding: 8px 0;
}

.page-btn {
    padding: 6px 16px;
    border-radius: 8px;
    background: #fff3e0;
    color: #f57c00;
    cursor: pointer;
    font-weight: 600;
}

.page-info {
    color: #64748b;
}
</style>
