<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    dueInstruments: Array<{
        id: number;
        code: string;
        type?: string;
        factory?: string;
        department?: string;
        latest_test_date?: string;
        next_test_date?: string;
    }>;
}>();

const page = usePage();
const can = (p: string) => !!page.props.auth?.user?.permissions.includes(p);

const goEntry = () => router.get(route('tests.create'));
const goTests = () => router.get(route('tests.index'));
const goMasters = () => router.get(route('masters.index', { entity: 'factories' }));
const goUsers = () => router.get(route('users.index'));
</script>

<template>
    <div class="dashboard">
        <div v-if="can('test.create')" class="feature-card entry-card" @click="goEntry">
            <div class="feature-icon">🧪</div>
            <div class="feature-text">
                <span class="feature-title">Entry Pengujian</span>
                <span class="feature-count">Input hasil kalibrasi alat ukur</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('test.read')" class="feature-card tests-card" @click="goTests">
            <div class="feature-icon">📋</div>
            <div class="feature-text">
                <span class="feature-title">Riwayat Pengujian</span>
                <span class="feature-count">Lihat semua hasil pengujian</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('master.read')" class="feature-card master-card" @click="goMasters">
            <div class="feature-icon">🗂️</div>
            <div class="feature-text">
                <span class="feature-title">Master Data</span>
                <span class="feature-count">Kelola factory, alat ukur, toleransi</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('user.manage')" class="feature-card users-card" @click="goUsers">
            <div class="feature-icon">👥</div>
            <div class="feature-text">
                <span class="feature-title">Kelola Pengguna</span>
                <span class="feature-count">Setujui &amp; atur role user</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div class="section-header">
            <h3 class="section-title">Alat Perlu Uji</h3>
            <Link :href="route('tests.index')" class="see-all-link">Lihat Semua</Link>
        </div>

        <div v-if="dueInstruments.length === 0" class="empty-card">
            <var-icon name="checkbox-marked-circle" :size="48" color="#cbd5e1" />
            <p>Tidak ada alat yang jatuh tempo bulan ini.</p>
        </div>

        <div v-else class="request-list">
            <div v-for="i in dueInstruments" :key="i.id" class="request-card" @click="goEntry">
                <div class="request-header">
                    <span class="request-code">{{ i.code }}</span>
                    <var-chip v-if="i.next_test_date" type="danger" size="small" round>OVERDUE</var-chip>
                    <var-chip v-else type="warning" size="small" round>BELUM UJI</var-chip>
                </div>
                <div class="request-meta">
                    <span>{{ i.type }}</span>
                    <span>{{ i.factory }} / {{ i.department }}</span>
                    <span>Uji terakhir: {{ i.latest_test_date ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.feature-card {
    display: flex;
    align-items: center;
    gap: 14px;
    border-radius: 16px;
    padding: 16px;
    border: 2px solid;
    cursor: pointer;
}

.entry-card {
    background: #eef2ff;
    border-color: #c7d2fe;
}

.tests-card {
    background: #ecfeff;
    border-color: #a5f3fc;
}

.master-card {
    background: #fef3c7;
    border-color: #fde68a;
}

.users-card {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.feature-icon {
    font-size: 32px;
}

.feature-text {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.feature-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.feature-count {
    font-size: 12px;
    color: #64748b;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}

.section-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.see-all-link {
    font-size: 12px;
    color: #fb8c00;
    text-decoration: none;
    font-weight: 600;
}

.request-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    cursor: pointer;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.request-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 13px;
}

.request-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}

.empty-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    border: 1px dashed #cbd5e1;
    color: #94a3b8;
    font-size: 13px;
}

.empty-card p {
    margin: 8px 0 0;
}
</style>
