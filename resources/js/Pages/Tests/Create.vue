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
        };
        brand: { name: string };
        capacity: {
            name: string;
            standards: Array<{ standard_value: number }>;
        };
        specification: { name: string } | null;
        acceptable_limit: { name: string; min_correction: number; max_correction: number; unit: string };
    }>;
}>();

const manualStatuses = ['SPARE', 'NA', 'SERVICE'];

const selected = ref<any>(null);
const items = ref<Array<{ standard_value: number; reading_value: string; correction: number; within: boolean }>>([]);
const form = reactive({
    instrument_id: '',
    test_date: new Date().toISOString().slice(0, 10),
    status: '',
    notes: '',
});
const saving = ref(false);

const instrumentOptions = props.instruments.map((i) => ({ label: i.code, value: i.id }));

const computedStatus = computed(() => {
    if (items.value.length === 0) return '';
    const avg = avgCorrection.value;
    if (avg === null) return '';
    const limit = selected.value?.acceptable_limit;
    if (!limit) return '';
    return avg >= limit.min_correction && avg <= limit.max_correction ? 'OK' : 'NG';
});

const isManual = computed(() => manualStatuses.includes(form.status));

const finalStatus = computed(() => {
    if (isManual.value) return form.status;
    return computedStatus.value || form.status || '';
});

const statusType = (s: string) => {
    if (s === 'OK') return 'success';
    if (s === 'NG') return 'danger';
    if (s === 'SPARE') return 'info';
    if (s === 'SERVICE') return 'warning';
    return 'default';
};

const alertType = (s: string) => {
    if (s === 'OK') return 'success';
    if (s === 'NG') return 'danger';
    if (s === 'SPARE') return 'info';
    if (s === 'SERVICE') return 'warning';
    return 'info';
};

const selectInstrument = (id: number) => {
    const instrument = props.instruments.find((i) => i.id === id);
    selected.value = instrument;
    form.instrument_id = String(id);
    form.status = '';
    items.value = (instrument?.capacity.standards ?? []).map((s) => ({
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

const avgCorrection = computed<number | null>(() => {
    const filled = items.value.filter((i) => !isNaN(parseFloat(i.reading_value)));
    if (filled.length === 0) return null;
    const sum = filled.reduce((acc, i) => acc + i.correction, 0);
    return Math.round((sum / filled.length) * 10000) / 10000;
});

const allFilled = computed(() =>
    items.value.every((i) => i.reading_value !== '' && !isNaN(parseFloat(i.reading_value)))
);

const canSubmit = computed(() => {
    if (!selected.value) return false;
    if (isManual.value) return true;
    return allFilled.value;
});

const submit = () => {
    if (!selected.value || !canSubmit.value) return;
    saving.value = true;
    const payload: Record<string, any> = {
        instrument_id: form.instrument_id,
        test_date: form.test_date,
        notes: form.notes,
    };
    if (form.status) payload.status = form.status;
    if (!isManual.value && allFilled.value) {
        payload.items = items.value.map((i) => ({
            standard_value: i.standard_value,
            reading_value: parseFloat(i.reading_value),
        }));
    }
    router.post(route('tests.store'), payload, {
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal menyimpan pengujian.'); },
    });
};
</script>

<template>
    <var-steps
        :active="selected ? 1 : 0"
        active-color="#fb8c00"
        class="steps-bar"
    >
        <var-step>Pilih Alat Ukur</var-step>
        <var-step>Input Penunjukan</var-step>
    </var-steps>

    <div class="white-card">
        <h3 class="card-title">1. Pilih Alat Ukur</h3>
        <var-select
            v-model="form.instrument_id"
            placeholder="Kode Alat"
            :options="instrumentOptions"
            filterable
            clearable
            @change="selectInstrument"
        />
    </div>

            <div v-if="selected" class="info-card">
                <h3 class="selected-code">🔧 {{ selected.code }}</h3>
                <div class="info-row"><span class="info-label">Factory</span><span>{{ selected.factory.name }}</span></div>
                <div class="info-row"><span class="info-label">Departemen</span><span>{{ selected.department.name }}</span></div>
                <div class="info-row"><span class="info-label">Jenis</span><span>{{ selected.type.name }}</span></div>
                <div class="info-row"><span class="info-label">Merk</span><span>{{ selected.brand.name }}</span></div>
                <div class="info-row"><span class="info-label">Kapasitas</span><span>{{ selected.capacity.name }}</span></div>
                <div class="info-row"><span class="info-label">Toleransi</span><span>{{ selected.acceptable_limit.name }}</span></div>
                <div class="info-row"><span class="info-label">Spesifikasi</span><span>{{ selected.specification?.name ?? '—' }}</span></div>
            </div>

            <div v-if="selected" class="white-card">
                <h3 class="card-title">2. Input Penunjukan</h3>
                <div class="date-row">
                    <label class="date-label">Tanggal Uji</label>
                    <input v-model="form.test_date" type="date" class="date-input" />
                </div>

                <div class="field-block status-block">
                    <label class="field-label">Status (opsional — otomatis OK/NG dari perhitungan, bisa diganti)</label>
                    <var-select
                        v-model="form.status"
                        placeholder="Otomatis"
                        :options="['OK', 'NG', 'SPARE', 'NA', 'SERVICE'].map((s) => ({ label: s, value: s }))"
                    />
                </div>

                <div v-if="!isManual" class="items-list">
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

                <var-alert v-if="!isManual && !allFilled" type="warning">Semua penunjukan harus diisi (atau pilih status SPARE/NA/SERVICE).</var-alert>
                <var-alert v-if="!isManual && allFilled && avgCorrection !== null" type="info">
                    Rata-rata Koreksi: {{ avgCorrection }}
                    (limit {{ selected.acceptable_limit.min_correction }} s/d {{ selected.acceptable_limit.max_correction }})
                </var-alert>
                <var-alert v-if="finalStatus" :type="alertType(finalStatus)">
                    Status Alat: {{ finalStatus }}
                    <template v-if="!isManual && finalStatus === 'NG'"> — rata-rata koreksi melewati toleransi. Pengujian tetap tersimpan.</template>
                    <template v-if="isManual"> — status dipilih manual.</template>
                </var-alert>

                <var-button type="primary" block class="submit-btn" :loading="saving" :disabled="!canSubmit" @click="submit">
                    Simpan Pengujian
                </var-button>
            </div>
</template>

<style scoped>
.steps-bar {
    margin-bottom: 12px;
}

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

.selected-code {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 800;
    color: #f57c00;
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
