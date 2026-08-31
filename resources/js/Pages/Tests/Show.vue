<script setup lang="ts">
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    test: any;
}>();
</script>

<template>
    <div class="status-banner" :class="test.status === 'PASS' ? 'pass' : 'fail'">
        <span class="status-label">Status</span>
        <span class="status-value">{{ test.status }}</span>
    </div>

            <div class="white-card">
                <h3 class="card-title">Info Alat</h3>
                <div class="info-row"><span class="info-label">Kode Alat</span><span>{{ test.instrument.code }}</span></div>
                <div class="info-row"><span class="info-label">Jenis</span><span>{{ test.instrument.type?.name }}</span></div>
                <div class="info-row"><span class="info-label">Lokasi</span><span>{{ test.instrument.factory?.name }} / {{ test.instrument.department?.name }}</span></div>
                <div class="info-row"><span class="info-label">Merk / Kapasitas</span><span>{{ test.instrument.brand?.name }} / {{ test.instrument.capacity?.name }}</span></div>
                <div class="info-row"><span class="info-label">Spesifikasi</span><span>{{ test.instrument.specification?.name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Toleransi</span><span>{{ test.instrument.acceptable_limit?.name }}</span></div>
            </div>

            <div class="white-card">
                <h3 class="card-title">Info Uji</h3>
                <div class="info-row"><span class="info-label">Tanggal Uji</span><span>{{ test.test_date }}</span></div>
                <div class="info-row"><span class="info-label">Next Test</span><span>{{ test.next_test_date }}</span></div>
                <div class="info-row"><span class="info-label">Tester</span><span>{{ test.tester?.name }}</span></div>
                <div class="info-row"><span class="info-label">Catatan</span><span>{{ test.notes ?? '—' }}</span></div>
            </div>

            <div class="white-card">
                <h3 class="card-title">Hasil Uji</h3>
                <div class="items-list">
                    <div v-for="(item, idx) in test.items" :key="idx" class="item-row">
                        <div class="item-left">
                            <span class="item-standar">{{ item.standard_value }}</span>
                            <span class="item-correction">Koreksi {{ item.correction }}</span>
                        </div>
                        <span class="item-reading">{{ item.reading_value }}</span>
                        <var-chip :type="item.is_within_limit ? 'success' : 'danger'" size="mini">
                            {{ item.is_within_limit ? 'OK' : 'NOK' }}
                        </var-chip>
                    </div>
                </div>
            </div>
</template>

<style scoped>
.status-banner {
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    color: #fff;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.status-banner.pass {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    box-shadow: 0 8px 20px -5px rgba(76, 175, 80, 0.4);
}

.status-banner.fail {
    background: linear-gradient(135deg, #ef5350, #c62828);
    box-shadow: 0 8px 20px -5px rgba(239, 83, 80, 0.4);
}

.status-label {
    font-size: 12px;
    opacity: 0.85;
    font-weight: 500;
}

.status-value {
    font-size: 26px;
    font-weight: 800;
}

.card-title {
    margin: 0 0 10px;
    font-size: 14px;
    color: #0f172a;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    font-size: 13px;
    color: #0f172a;
    border-bottom: 1px solid #f8fafc;
}

.info-label {
    color: #64748b;
    font-weight: 500;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 10px 12px;
    border: 1px solid #f1f5f9;
}

.item-left {
    display: flex;
    flex-direction: column;
}

.item-standar {
    font-family: monospace;
    font-weight: 700;
    font-size: 14px;
    color: #0f172a;
}

.item-correction {
    font-size: 11px;
    color: #64748b;
}

.item-reading {
    flex: 1;
    text-align: right;
    font-family: monospace;
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
}
</style>
