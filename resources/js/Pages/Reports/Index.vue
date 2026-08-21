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
        pass: number;
        fail: number;
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
const chipType = (status: string) => (status === 'PASS' ? 'success' : 'danger');
</script>

<template>
    <div class="white-card">
        <h3 class="card-title">Filter Laporan</h3>
        <var-space direction="column" size="small">
            <var-select v-model="filter.year" placeholder="Tahun" :options="[2025, 2026, 2027].map((y) => ({ label: String(y), value: String(y) }))" />
            <var-select v-model="filter.month" placeholder="Bulan" :options="monthNames.map((m, i) => ({ label: m, value: String(i + 1) }))" />
            <var-select v-model="filter.status" placeholder="Status" :options="[{ label: 'PASS', value: 'PASS' }, { label: 'FAIL', value: 'FAIL' }]" />
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
                    <div class="stat-info"><span class="stat-count">{{ summary.pass }}</span><span class="stat-title">PASS</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:#fee2e2"><var-icon name="close-circle-outline" :size="20" color="#ef4444" /></div>
                    <div class="stat-info"><span class="stat-count">{{ summary.fail }}</span><span class="stat-title">FAIL</span></div>
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
                        <var-chip :type="chipType(t.status)" size="small" round>{{ t.status }}</var-chip>
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
