<script setup lang="ts">
import { reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tests: Array<any>;
    filters: {
        year?: string;
        month?: string;
        status?: string;
        factory_id?: string;
    };
    factories: Array<{ id: number; name: string }>;
    summary: {
        total: number;
        ok: number;
        ng: number;
        spare: number;
        na: number;
        service: number;
    };
}>();

const filter = reactive({
    year: props.filters.year ?? String(new Date().getFullYear()),
    month: props.filters.month ?? '',
    status: props.filters.status ?? '',
    factory_id: props.filters.factory_id ?? '',
});

const apply = () => {
    const params: Record<string, string> = {};
    if (filter.year) params.year = filter.year;
    if (filter.month) params.month = filter.month;
    if (filter.status) params.status = filter.status;
    if (filter.factory_id) params.factory_id = filter.factory_id;
    router.get(route('reports.index'), params, { preserveState: true, preserveScroll: true });
};

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const statusType = (status: string) => {
    if (status === 'OK') return 'success';
    if (status === 'NG') return 'danger';
    if (status === 'SPARE') return 'info';
    if (status === 'SERVICE') return 'warning';
    return 'default';
};
</script>

<template>
    <div class="white-card">
        <h3 class="card-title">Filter Laporan</h3>
        <var-space direction="column" size="small">
            <var-select v-model="filter.year" placeholder="Tahun" :options="[2025, 2026, 2027].map((y) => ({ label: String(y), value: String(y) }))" />
            <var-select v-model="filter.month" placeholder="Bulan" :options="monthNames.map((m, i) => ({ label: m, value: String(i + 1) }))" />
            <var-select v-model="filter.status" placeholder="Status" :options="['OK','NG','SPARE','NA','SERVICE'].map((s) => ({ label: s, value: s }))" />
            <var-select v-model="filter.factory_id" placeholder="Factory" :options="factories.map((f) => ({ label: f.name, value: String(f.id) }))" />
            <var-button type="primary" block class="filter-btn" @click="apply">Tampilkan</var-button>
        </var-space>
    </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#dbeafe"><var-icon name="file-document-outline" :size="20" color="#3b82f6" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.total }}</span><span class="stat-title">Total</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#d1fae5"><var-icon name="check" :size="20" color="#10b981" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.ok }}</span><span class="stat-title">OK</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#fee2e2"><var-icon name="close-circle-outline" :size="20" color="#ef4444" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.ng }}</span><span class="stat-title">NG</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#e3f2fd"><var-icon name="information" :size="20" color="#2196f3" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.spare }}</span><span class="stat-title">SPARE</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#f5f5f5"><var-icon name="minus-circle" :size="20" color="#9e9e9e" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.na }}</span><span class="stat-title">NA</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#fff3e0"><var-icon name="alert" :size="20" color="#ff9800" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.service }}</span><span class="stat-title">SERVICE</span></div>
                </div>
            </div>

            <div v-if="tests.length === 0" class="empty-card">
                <var-icon name="file-document-outline" :size="48" color="#cbd5e1" />
                <p>Tidak ada data laporan.</p>
            </div>

            <div v-else class="request-list">
                <Link
                    v-for="t in tests"
                    :key="t.id"
                    :href="route('tests.show', { test: t.id })"
                    class="request-card"
                >
                    <div class="request-header">
                        <span class="request-code">{{ t.instrument?.code }}</span>
                        <var-chip :type="statusType(t.status)" size="small" round>{{ t.status }}</var-chip>
                    </div>
                    <div class="request-meta">
                        <span>{{ t.instrument?.type?.name }}</span>
                        <span>{{ t.instrument?.factory?.name }}</span>
                        <span>{{ t.test_date }}</span>
                        <span>{{ t.tester?.name }}</span>
                        <span>{{ t.items?.length }} titik</span>
                    </div>
                </Link>
            </div>
</template>

<style scoped>
.card-title {
    margin: 0 0 12px;
    font-size: 14px;
    color: #0f172a;
}

.filter-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-radius: 100px;
    font-weight: 700;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #f1f5f9;
}

.stat-icon-wrapper {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-count {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.stat-title {
    font-size: 10px;
    color: #64748b;
    font-weight: 500;
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
    text-decoration: none;
    color: inherit;
    display: block;
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
</style>
