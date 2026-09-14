<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    test: any;
}>();

type SnapshotItem = {
    id: number;
    standard_value: number | string;
    reading_value: number | string;
    correction: number | string;
    is_within_limit: boolean | null;
    group_order: number | null;
    group_name: string | null;
    reference_media: string | null;
    unit: string | null;
    point_order: number | null;
};
const snapshotGroups = computed(() => {
    const groups = new Map<number, { order: number; name: string | null; media: string | null; items: SnapshotItem[] }>();
    for (const item of props.test.items as SnapshotItem[]) {
        if (item.group_order == null) continue;
        if (!groups.has(item.group_order)) groups.set(item.group_order, { order: item.group_order, name: item.group_name, media: item.reference_media, items: [] });
        groups.get(item.group_order)!.items.push(item);
    }
    return [...groups.values()].sort((a, b) => a.order - b.order).map((group) => ({
        ...group,
        items: [...group.items].sort((a, b) => (a.point_order ?? Infinity) - (b.point_order ?? Infinity)),
    }));
});
const groupAverage = (items: SnapshotItem[]) => {
    if (!items.length) return null;
    const sum = items.reduce((total, item) => total + BigInt(Math.round(Number(item.correction) * 10000)), 0n);
    const count = BigInt(items.length);
    const absolute = sum < 0n ? -sum : sum;
    const rounded = absolute / count + ((absolute % count) * 2n >= count ? 1n : 0n);
    return Number(sum < 0n ? -rounded : rounded) / 10000;
};
if (import.meta.env.DEV) {
    console.assert(groupAverage([{ correction: '-0.0001' }, { correction: '0' }] as SnapshotItem[]) === -0.0001, 'Snapshot group average rounding');
}
const legacyItems = computed(() => (props.test.items as SnapshotItem[]).filter((item) => item.group_order == null));
const snapshotUnit = computed(() => {
    const units = [...new Set((props.test.items as SnapshotItem[]).map((item) => item.unit))];
    return units.length === 1 ? units[0] : null;
});
const hasToleranceSnapshot = computed(() => props.test.min_correction_snapshot != null && props.test.max_correction_snapshot != null);
const bannerClass = (status: string) => {
    if (status === 'OK') return 'ok';
    if (status === 'NG') return 'ng';
    if (status === 'SPARE') return 'spare';
    if (status === 'SERVICE') return 'service';
    return 'na';
};
</script>

<template>
    <div class="status-banner" :class="bannerClass(test.status)">
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
                <div class="info-row"><span class="info-label">Toleransi saat pengujian</span><span v-if="hasToleranceSnapshot">{{ test.min_correction_snapshot }} s/d {{ test.max_correction_snapshot }} {{ snapshotUnit ?? '' }}</span><span v-else>Tidak tersedia (data lama)</span></div>
            </div>

            <div class="white-card">
                <h3 class="card-title">Info Uji</h3>
                <div class="info-row"><span class="info-label">Tanggal Uji</span><span>{{ test.test_date }}</span></div>
                <div class="info-row"><span class="info-label">Next Test</span><span>{{ test.next_test_date }}</span></div>
                <div class="info-row"><span class="info-label">Tester</span><span>{{ test.tester?.name }}</span></div>
                <div class="info-row"><span class="info-label">Rata-rata Koreksi</span>
                    <span>
                        {{ test.avg_correction ?? '—' }} {{ snapshotUnit ?? '' }}
                    </span>
                </div>
                <div class="info-row"><span class="info-label">Status otomatis tersimpan</span><var-chip v-if="test.computed_status" :type="test.computed_status === 'OK' ? 'success' : 'danger'" size="mini">{{ test.computed_status }}</var-chip><span v-else>Tidak tersedia / tidak dihitung</span></div>
                <div class="info-row"><span class="info-label">Status pilihan tersimpan</span><span>{{ test.selected_status ?? 'Tidak tersedia / tidak dipilih' }}</span></div>
                <div class="info-row"><span class="info-label">Status efektif</span><span>{{ test.status }}</span></div>
                <div class="info-row"><span class="info-label">Catatan</span><span>{{ test.notes ?? '—' }}</span></div>
                <p v-if="['SPARE', 'NA', 'SERVICE'].includes(test.selected_status)">Status manual; pengukuran tidak disimpan dan status otomatis tidak dihitung.</p>
            </div>

            <div v-if="test.items.length > 0" class="white-card">
                <h3 class="card-title">Hasil Uji</h3>
                <details v-for="(group, index) in snapshotGroups" :key="group.order" :open="index === 0" class="group-card">
                    <summary>{{ group.name ?? 'Nama grup tidak tersedia' }} · {{ group.items.length }} titik</summary>
                    <div class="group-content">
                        <p>Media referensi: {{ group.media ?? 'Tidak tersedia' }}</p>
                        <p>Rata-rata Koreksi Grup: {{ groupAverage(group.items) ?? '—' }} {{ group.items[0]?.unit ?? '' }}</p>
                        <div class="items-list">
                            <div v-for="item in group.items" :key="item.id" class="item-row">
                                <div class="item-left">
                                    <span class="item-standar">Standar {{ item.standard_value }} {{ item.unit ?? '' }}</span>
                                    <span class="item-correction">Koreksi {{ item.correction }} {{ item.unit ?? '' }}</span>
                                </div>
                                <span class="item-reading">Penunjukan {{ item.reading_value }} {{ item.unit ?? '' }}</span>
                                <var-chip v-if="item.is_within_limit != null" :type="item.is_within_limit ? 'success' : 'danger'" size="mini">{{ item.is_within_limit ? 'OK' : 'NG' }}</var-chip>
                            </div>
                        </div>
                    </div>
                </details>
                <p v-if="legacyItems.length">Data lama: snapshot grup, media, urutan, atau satuan tidak tersedia. Nilai berikut berasal dari hasil tersimpan, bukan master saat ini.</p>
                <div v-if="legacyItems.length" class="items-list">
                    <div v-for="item in legacyItems" :key="item.id" class="item-row">
                        <div class="item-left">
                            <span class="item-standar">Standar {{ item.standard_value }} {{ item.unit ?? '' }}</span>
                            <span class="item-correction">Koreksi {{ item.correction }} {{ item.unit ?? '' }}</span>
                        </div>
                        <span class="item-reading">Penunjukan {{ item.reading_value }} {{ item.unit ?? '' }}</span>
                        <var-chip v-if="item.is_within_limit != null" :type="item.is_within_limit ? 'success' : 'danger'" size="mini">
                            {{ item.is_within_limit ? 'OK' : 'NG' }}
                        </var-chip>
                    </div>
                </div>
            </div>
</template>

<style scoped>
.group-card { margin: 12px 0; border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden; }
summary { padding: 14px; background: #fdf0ea; color: #9a4d00; cursor: pointer; font-weight: 600; overflow-wrap: anywhere; }
summary:focus-visible { outline: 2px solid #fb8c00; outline-offset: -2px; }
.group-content { padding: 12px; }
.item-row { flex-wrap: wrap; }
.info-row { gap: 12px; overflow-wrap: anywhere; }
.status-banner {
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    color: #fff;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.status-banner.ok {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    box-shadow: 0 8px 20px -5px rgba(76, 175, 80, 0.4);
}

.status-banner.ng {
    background: linear-gradient(135deg, #ef5350, #c62828);
    box-shadow: 0 8px 20px -5px rgba(239, 83, 80, 0.4);
}

.status-banner.spare {
    background: linear-gradient(135deg, #42a5f5, #1565c0);
    box-shadow: 0 8px 20px -5px rgba(66, 165, 245, 0.4);
}

.status-banner.na {
    background: linear-gradient(135deg, #90a4ae, #546e7a);
    box-shadow: 0 8px 20px -5px rgba(144, 164, 174, 0.4);
}

.status-banner.service {
    background: linear-gradient(135deg, #ffa726, #e65100);
    box-shadow: 0 8px 20px -5px rgba(255, 167, 38, 0.4);
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
