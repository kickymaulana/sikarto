<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    instruments: Array<{
        id: number;
        code: string;
        factory: { name: string };
        department: { name: string };
        type: {
            name: string;
            standards: Array<{ standard_value: number }>;
        };
        brand: { name: string };
        capacity: { name: string };
        acceptable_limit: { name: string; min_correction: number; max_correction: number; unit: string };
    }>;
}>();

const selected = ref<any>(null);
const items = ref<Array<{ standard_value: number; reading_value: string; correction: number; within: boolean }>>([]);
const form = reactive({
    instrument_id: '',
    test_date: new Date().toISOString().slice(0, 10),
    notes: '',
});
const saving = ref(false);

const instrumentOptions = props.instruments.map((i) => ({ label: i.code, value: i.id }));

const selectInstrument = (id: number) => {
    const instrument = props.instruments.find((i) => i.id === id);
    selected.value = instrument;
    form.instrument_id = String(id);
    items.value = (instrument?.type.standards ?? []).map((s) => ({
        standard_value: s.standard_value,
        reading_value: '',
        correction: 0,
        within: true,
    }));
};

const computeRow = (idx: number) => {
    const row = items.value[idx];
    const reading = parseFloat(row.reading_value);
    if (isNaN(reading)) {
        row.correction = 0;
        row.within = true;
        return;
    }
    const limit = selected.value?.acceptable_limit;
    row.correction = Math.round((reading - row.standard_value) * 10000) / 10000;
    row.within = !!limit && row.correction >= limit.min_correction && row.correction <= limit.max_correction;
};

const overallStatus = computed(() => {
    if (items.value.length === 0) return 'PASS';
    return items.value.every((i) => {
        const reading = parseFloat(i.reading_value);
        return !isNaN(reading) && i.within;
    }) ? 'PASS' : 'FAIL';
});

const allFilled = computed(() =>
    items.value.every((i) => i.reading_value !== '' && !isNaN(parseFloat(i.reading_value)))
);

const submit = () => {
    if (!selected.value || !allFilled.value) return;
    saving.value = true;
    router.post(route('tests.store'), {
        instrument_id: form.instrument_id,
        test_date: form.test_date,
        notes: form.notes,
        items: items.value.map((i) => ({
            standard_value: i.standard_value,
            reading_value: parseFloat(i.reading_value),
        })),
    }, {
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal menyimpan pengujian.'); },
    });
};
</script>

<template>
    <div class="white-card">
        <h3 class="card-title">1. Pilih Alat Ukur</h3>
        <var-select
            v-model="form.instrument_id"
            placeholder="Kode Alat"
            :options="instrumentOptions"
            @change="selectInstrument"
        />
    </div>

            <div v-if="selected" class="info-card">
                <div class="info-row"><span class="info-label">Factory</span><span>{{ selected.factory.name }}</span></div>
                <div class="info-row"><span class="info-label">Departemen</span><span>{{ selected.department.name }}</span></div>
                <div class="info-row"><span class="info-label">Jenis</span><span>{{ selected.type.name }}</span></div>
                <div class="info-row"><span class="info-label">Merk</span><span>{{ selected.brand.name }}</span></div>
                <div class="info-row"><span class="info-label">Kapasitas</span><span>{{ selected.capacity.name }}</span></div>
                <div class="info-row"><span class="info-label">Toleransi</span><span>{{ selected.acceptable_limit.name }}</span></div>
            </div>

            <div v-if="selected" class="white-card">
                <h3 class="card-title">2. Input Penunjukan</h3>
                <div class="date-row">
                    <label class="date-label">Tanggal Uji</label>
                    <input v-model="form.test_date" type="date" class="date-input" />
                </div>

                <div class="items-list">
                    <div v-for="(row, idx) in items" :key="idx" class="item-row">
                        <div class="item-left">
                            <span class="item-standar">{{ row.standard_value }}</span>
                            <span class="item-limit">
                                {{ selected.acceptable_limit.min_correction }} s/d {{ selected.acceptable_limit.max_correction }}
                            </span>
                        </div>
                        <var-input
                            v-model="row.reading_value"
                            type="number"
                            size="small"
                            placeholder="Penunjukan"
                            class="item-input"
                            @change="computeRow(idx)"
                        />
                        <span class="item-corr">{{ row.correction }}</span>
                        <var-chip
                            :type="row.within ? 'success' : 'danger'"
                            size="mini"
                        >
                            {{ row.within ? 'OK' : 'NOK' }}
                        </var-chip>
                    </div>
                </div>

                <var-input v-model="form.notes" placeholder="Catatan (opsional)" :textarea="true" />

                <var-alert v-if="!allFilled" type="warning">Semua penunjukan harus diisi.</var-alert>
                <var-alert :type="overallStatus === 'PASS' ? 'success' : 'danger'">
                    Status Alat: {{ overallStatus }}
                    <template v-if="overallStatus === 'FAIL'"> — ada titik melewati toleransi. Pengujian tetap tersimpan.</template>
                </var-alert>

                <var-button type="primary" block class="submit-btn" :loading="saving" :disabled="!allFilled" @click="submit">
                    Simpan Pengujian
                </var-button>
            </div>
</template>

<style scoped>
.card-title {
    margin: 0 0 12px;
    font-size: 14px;
    color: #0f172a;
}

.info-card {
    background: #fff3e0;
    border-radius: 16px;
    padding: 14px 16px;
    border: 1px solid #ffe0b2;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
    color: #0f172a;
}

.info-label {
    color: #92400e;
    font-weight: 600;
}

.date-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.date-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.date-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    background: #fdf0ea;
    color: #0f172a;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 14px;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 10px 12px;
    border: 1px solid #f1f5f9;
}

.item-left {
    display: flex;
    flex-direction: column;
    width: 90px;
    flex-shrink: 0;
}

.item-standar {
    font-family: monospace;
    font-weight: 700;
    font-size: 14px;
    color: #0f172a;
}

.item-limit {
    font-size: 10px;
    color: #64748b;
}

.item-input {
    flex: 1;
}

.item-corr {
    width: 56px;
    text-align: right;
    font-family: monospace;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.submit-btn {
    margin-top: 12px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-radius: 100px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(251, 140, 0, 0.3);
}
</style>
