<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Decimal = number | string;
type Instrument = {
    id: number;
    code: string;
    factory: { name: string } | null;
    department: { name: string } | null;
    type: { name: string } | null;
    brand: { name: string } | null;
    specification: { name: string } | null;
    capacity: { name: string; groups: Array<{ id: number; name: string; reference_media: string; standards: Array<{ id: number; standard_value: Decimal }> }> } | null;
    acceptable_limit: { name: string; min_correction: Decimal; max_correction: Decimal; unit: string } | null;
};
type Point = { standard_template_id: number; standard_value: Decimal; reading_value: string; index: number };
type Group = { id: number; name: string; reference_media: string; open: boolean; points: Point[] };
const props = defineProps<{ instruments: Instrument[] }>();
const selected = ref<Instrument | null>(null);
const groups = ref<Group[]>([]);
const today = new Date();
const localToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
const form = reactive({ instrument_id: '' as number | string, test_date: localToday, status: '', notes: '' });
const saving = ref(false);
const errors = ref<Record<string, string>>({});
const instrumentOptions = props.instruments.map((instrument) => ({ label: instrument.code, value: instrument.id }));
const items = computed(() => groups.value.flatMap((group) => group.points));
const isManual = computed(() => ['SPARE', 'NA', 'SERVICE'].includes(form.status));
const unit = computed(() => selected.value?.acceptable_limit?.unit ?? '');
const scaled = (value: Decimal): number | null => {
    const text = String(value).trim();
    if (!/^[+-]?(?:\d+(?:\.\d{1,4})?|\.\d{1,4})$/.test(text) || Math.abs(Number(text)) > 99999999.9999) return null;
    const [whole, fraction = ''] = text.replace(/^[+-]/, '').split('.');
    return (text.startsWith('-') ? -1 : 1) * (Number(whole) * 10000 + Number(fraction.padEnd(4, '0')));
};
const correction = (point: Point): number | null => {
    const reading = scaled(point.reading_value);
    const standard = scaled(point.standard_value);
    if (reading === null || standard === null) return null;
    const difference = reading - standard;
    return Math.abs(difference) <= 999999999999 ? difference : null;
};
const mean = (points: Point[]): number | null => {
    const values = points.map(correction).filter((value): value is number => value !== null);
    if (!values.length) return null;
    const sum = values.reduce((total, value) => total + BigInt(value), 0n);
    const count = BigInt(values.length);
    const absolute = sum < 0n ? -sum : sum;
    const rounded = absolute / count + ((absolute % count) * 2n >= count ? 1n : 0n);
    return Number(sum < 0n ? -rounded : rounded) / 10000;
};
const progress = (group: Group) => group.points.filter((point) => correction(point) !== null).length;
const allFilled = computed(() => groups.value.length > 0 && groups.value.every((group) => group.points.length > 0 && progress(group) === group.points.length));
const avgCorrection = computed(() => allFilled.value ? mean(items.value) : null);
const limitValid = computed(() => {
    const limit = selected.value?.acceptable_limit;
    if (!limit?.unit) return false;
    const min = scaled(limit.min_correction);
    const max = scaled(limit.max_correction);
    return min !== null && max !== null && min <= max;
});
const computedStatus = computed(() => {
    const limit = selected.value?.acceptable_limit;
    if (isManual.value || avgCorrection.value === null || !limit || !limitValid.value) return '';
    return avgCorrection.value >= Number(limit.min_correction) && avgCorrection.value <= Number(limit.max_correction) ? 'OK' : 'NG';
});
const finalStatus = computed(() => form.status || computedStatus.value);
const canSubmit = computed(() => !!selected.value && !!form.test_date && form.test_date <= localToday && (isManual.value || (allFilled.value && limitValid.value)));
const pointErrors = (point: Point) => Object.entries(errors.value).filter(([key]) => key === `items.${point.index}` || key.startsWith(`items.${point.index}.`));
const selectInstrument = (id: unknown) => {
    selected.value = props.instruments.find((instrument) => instrument.id === Number(id)) ?? null;
    form.instrument_id = selected.value?.id ?? '';
    form.status = '';
    errors.value = {};
    let index = 0;
    groups.value = (selected.value?.capacity?.groups ?? []).map((group, groupIndex) => ({
        id: group.id,
        name: group.name,
        reference_media: group.reference_media,
        open: groupIndex === 0,
        points: group.standards.map((point) => ({ standard_template_id: point.id, standard_value: point.standard_value, reading_value: '', index: index++ })),
    }));
};
const submit = () => {
    if (!canSubmit.value || saving.value) return;
    saving.value = true;
    errors.value = {};
    const payload = {
        instrument_id: form.instrument_id,
        test_date: form.test_date,
        notes: form.notes,
        ...(form.status ? { status: form.status } : {}),
        ...(!isManual.value ? { items: items.value.map((point) => ({ standard_template_id: point.standard_template_id, reading_value: point.reading_value.trim() })) } : {}),
    };
    router.post(route('tests.store'), payload, {
        onError: (messages) => {
            errors.value = messages;
            groups.value.forEach((group) => {
                if (messages.items || group.points.some((point) => pointErrors(point).length)) group.open = true;
            });
            Snackbar.error('Gagal menyimpan pengujian. Periksa input.');
        },
        onFinish: () => { saving.value = false; },
    });
};
if (import.meta.env.DEV) {
    const point = (reading_value: string): Point => ({ standard_template_id: 1, standard_value: '0', reading_value, index: 0 });
    console.assert(scaled('0') === 0 && scaled('') === null && scaled('1.00001') === null && scaled('100000000') === null, 'Decimal validation');
    console.assert(mean([point('-0.0001'), point('0')]) === -0.0001 && mean([point('0.0001'), point('0')]) === 0.0001, 'Half-away-from-zero rounding');
    console.assert(mean([point('0'), point('0'), point('3')]) === 1, 'Point-weighted mean');
}
</script>

<template>
    <div class="white-card">
        <h3 class="card-title">1. Pilih Alat Ukur</h3>
        <var-select v-model="form.instrument_id" placeholder="Kode Alat" :options="instrumentOptions" filterable clearable :disabled="saving" @change="selectInstrument" />
    </div>
    <div v-if="Object.keys(errors).length" role="alert" class="white-card error-list">
        <p v-for="(message, key) in errors" :key="key">{{ message }}</p>
    </div>
    <div v-if="selected" class="info-card">
        <h3 class="selected-code">{{ selected.code }}</h3>
        <div class="info-row"><span>Factory / Departemen</span><span>{{ selected.factory?.name ?? '—' }} / {{ selected.department?.name ?? '—' }}</span></div>
        <div class="info-row"><span>Jenis / Merk</span><span>{{ selected.type?.name ?? '—' }} / {{ selected.brand?.name ?? '—' }}</span></div>
        <div class="info-row"><span>Kapasitas</span><span>{{ selected.capacity?.name ?? '—' }}</span></div>
        <div class="info-row"><span>Spesifikasi</span><span>{{ selected.specification?.name ?? '—' }}</span></div>
        <div class="info-row"><span>Toleransi</span><span v-if="selected.acceptable_limit">{{ selected.acceptable_limit.min_correction }} s/d {{ selected.acceptable_limit.max_correction }} {{ unit }}</span><span v-else>—</span></div>
    </div>
    <div v-if="selected" class="white-card">
        <h3 class="card-title">2. Input Penunjukan</h3>
        <label for="test-date">Tanggal Uji</label>
        <input id="test-date" v-model="form.test_date" type="date" :max="localToday" :disabled="saving" />
        <div class="field-block">
            <label class="field-label">Status pilihan (menggantikan otomatis)</label>
            <var-select v-model="form.status" placeholder="Otomatis" :disabled="saving" :options="[{ label: 'Otomatis', value: '' }, ...['OK', 'NG', 'SPARE', 'NA', 'SERVICE'].map((status) => ({ label: status, value: status }))]" />
        </div>
        <var-alert v-if="isManual" type="info">Status {{ form.status }} mengabaikan pengukuran. Input tetap tersimpan sementara di halaman ini, tetapi tidak dikirim atau disimpan sebagai hasil uji.</var-alert>
        <div v-show="!isManual" class="items-list">
            <p v-if="!groups.length">Kapasitas belum memiliki grup titik uji. Lengkapi master kapasitas atau pilih SPARE/NA/SERVICE.</p>
            <details v-for="group in groups" :key="group.id" :open="group.open" class="group-card" @toggle="group.open = ($event.target as HTMLDetailsElement).open">
                <summary>{{ group.name }} · {{ progress(group) }}/{{ group.points.length }} titik valid</summary>
                <div class="group-content">
                    <p>Media referensi: {{ group.reference_media }}</p>
                    <p>Rata-rata Koreksi Sementara: {{ mean(group.points) ?? '—' }} {{ unit }}</p>
                    <p v-if="!group.points.length" class="error-list">Grup belum memiliki titik uji.</p>
                    <div v-for="point in group.points" :key="point.standard_template_id" class="item-row">
                        <label :for="`reading-${point.standard_template_id}`">Penunjukan untuk standar {{ point.standard_value }} {{ unit }}</label>
                        <input :id="`reading-${point.standard_template_id}`" v-model="point.reading_value" type="text" inputmode="decimal" :disabled="saving" :aria-invalid="!!pointErrors(point).length || (point.reading_value !== '' && correction(point) === null)" :aria-describedby="`reading-error-${point.standard_template_id}`" />
                        <span>Koreksi: {{ correction(point) === null ? '—' : correction(point)! / 10000 }} {{ unit }}</span>
                        <div :id="`reading-error-${point.standard_template_id}`" class="error-list" aria-live="polite">
                            <p v-if="point.reading_value !== '' && scaled(point.reading_value) === null">Isi angka maksimal 4 desimal, rentang ±99999999.9999.</p>
                            <p v-else-if="point.reading_value !== '' && correction(point) === null">Standar tidak valid atau koreksi di luar rentang ±99999999.9999.</p>
                            <p v-for="([key, message]) in pointErrors(point)" :key="key">{{ message }}</p>
                        </div>
                    </div>
                </div>
            </details>
            <var-alert v-if="!allFilled" type="warning">Semua titik uji wajib diisi angka valid, termasuk nol. Status pilihan OK/NG tetap memerlukan semua titik.</var-alert>
            <var-alert v-if="!limitValid" type="warning">Batas toleransi atau satuan tidak valid. Perbaiki master alat.</var-alert>
        </div>
        <var-input v-model="form.notes" placeholder="Catatan (opsional)" :textarea="true" :disabled="saving" />
        <div class="result" aria-live="polite">
            <p>Rata-rata Koreksi Global: {{ isManual ? '—' : avgCorrection ?? '—' }} {{ isManual ? '' : unit }}</p>
            <p>Otomatis: {{ computedStatus || (isManual ? 'Tidak dihitung' : 'Menunggu semua titik valid') }}</p>
            <p>Pilihan: {{ form.status || 'Tidak dipilih' }}</p>
            <strong>Status efektif: {{ finalStatus || '—' }}</strong>
        </div>
        <var-button type="primary" block class="submit-btn" :loading="saving" :disabled="!canSubmit || saving" @click="submit">Simpan Pengujian</var-button>
    </div>
</template>

<style scoped>
.card-title { margin: 0 0 12px; font-size: 14px; color: #0f172a; }
.info-card { background: #fdf0ea; border-radius: 16px; padding: 14px 16px; border: 1px solid #ffe0b2; }
.info-row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 13px; }
.selected-code { margin: 0 0 8px; font-size: 16px; color: #f57c00; }
.group-card { border: 1px solid #f1f5f9; border-radius: 16px; background: white; margin: 12px 0; overflow: hidden; }
summary { padding: 14px; background: #fdf0ea; color: #9a4d00; cursor: pointer; font-weight: 600; overflow-wrap: anywhere; }
summary:focus-visible, input:focus-visible { outline: 2px solid #fb8c00; outline-offset: -2px; }
.group-content { padding: 12px; }
.item-row { display: grid; gap: 8px; background: #f8fafc; border-radius: 12px; padding: 12px; margin-top: 10px; }
input { box-sizing: border-box; width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font: inherit; background: #fdf0ea; color: #0f172a; }
.error-list { color: #b91c1c; font-size: 13px; }
.error-list p { margin: 4px 0; }
.result { margin: 12px 0; }
.submit-btn { margin-top: 12px; background: linear-gradient(135deg, #fb8c00, #f57c00); border-radius: 100px; font-weight: 700; }
</style>
