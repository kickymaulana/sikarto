<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar, Dialog } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';
import MastersSidebar from './Sidebar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    entity: string;
    config: {
        label: string;
        fields: Array<{ key: string; label: string; type: string; step?: string; options?: string }>;
    };
    item: Record<string, any> | null;
    options: Record<string, Array<{ id: number; name: string; code?: string }>>;
    counts: Record<string, number>;
}>();

const form = reactive<Record<string, any>>({});
props.config.fields.forEach((f) => {
    form[f.key] = props.item ? props.item[f.key] : f.type === 'select' ? '' : '';
});

const isCapacity = props.entity === 'capacities';
type Point = { id?: number; standard_value: string; key: number };
type Group = { id?: number; name: string; reference_media: string; standards: Point[]; key: number; open: boolean };
let nextKey = 0;
const groups = ref<Group[]>((props.item?.groups ?? []).map((group: Group, index: number) => ({
    id: group.id,
    name: group.name,
    reference_media: group.reference_media,
    key: nextKey++,
    open: index === 0,
    standards: group.standards.map((point) => ({ id: point.id, standard_value: String(point.standard_value), key: nextKey++ })),
})));
const errors = ref<Record<string, string>>({});
const groupErrors = (index: number) => Object.entries(errors.value).filter(([key]) => key === `groups.${index}` || key.startsWith(`groups.${index}.`));

const isEditing = !!props.item;
const saving = ref(false);

const fieldOptions = (key: string) =>
    (props.options[key] ?? []).map((o) => ({ label: o.name ?? o.code, value: o.id }));

const addGroup = () => {
    errors.value = {};
    groups.value.push({ name: '', reference_media: '', key: nextKey++, open: true, standards: [{ standard_value: '', key: nextKey++ }] });
};
const removeGroup = (group: Group) => {
    Dialog({
        title: 'Hapus Grup',
        message: `Hapus grup ${group.name || 'ini'} beserta semua titik uji?`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            groups.value = groups.value.filter((entry) => entry.key !== group.key);
            errors.value = {};
        },
    });
};
const removePoint = (group: Group, index: number) => {
    group.standards.splice(index, 1);
    errors.value = {};
};

const submit = () => {
    if (saving.value) return;
    errors.value = {};
    if (isCapacity) {
        groups.value.forEach((group, index) => {
            if (!group.name.trim()) errors.value[`groups.${index}.name`] = 'Nama grup wajib diisi.';
            if (!group.reference_media.trim()) errors.value[`groups.${index}.reference_media`] = 'Media referensi wajib diisi.';
            if (!group.standards.length) errors.value[`groups.${index}.standards`] = 'Minimal satu titik uji.';
            group.standards.forEach((point, pointIndex) => {
                const value = point.standard_value.trim();
                if (!/^[+-]?(?:\d+(?:\.\d{1,4})?|\.\d{1,4})$/.test(value) || Math.abs(Number(value)) > 99999999.9999) {
                    errors.value[`groups.${index}.standards.${pointIndex}.standard_value`] = 'Isi angka maksimal 4 desimal, rentang ±99999999.9999.';
                }
            });
            if (groupErrors(index).length) group.open = true;
        });
        if (Object.keys(errors.value).length) return;
    }
    saving.value = true;
    const payload: Record<string, any> = { ...form };
    if (isCapacity) {
        payload.groups = groups.value.map((group) => ({
            ...(group.id !== undefined ? { id: group.id } : {}),
            name: group.name,
            reference_media: group.reference_media,
            standards: group.standards.map((point) => ({
                ...(point.id !== undefined ? { id: point.id } : {}),
                standard_value: point.standard_value.trim(),
            })),
        }));
    }
    const opts = {
        onSuccess: () => { saving.value = false; },
        onError: (messages: Record<string, string>) => {
            errors.value = messages;
            groups.value.forEach((group, index) => { if (groupErrors(index).length) group.open = true; });
            Snackbar.error('Gagal menyimpan. Periksa kembali input.');
        },
        onFinish: () => { saving.value = false; },
    };
    if (isEditing) {
        router.put(route('masters.update', { entity: props.entity, id: props.item!.id }), payload, opts);
    } else {
        router.post(route('masters.store', { entity: props.entity }), payload, opts);
    }
};

const remove = () => {
    Dialog({
        title: 'Hapus Data',
        message: `Yakin ingin menghapus ${props.config.label.toLowerCase()} ini?`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('masters.destroy', { entity: props.entity, id: props.item!.id }), {
                onError: () => Snackbar.error('Gagal menghapus. Data mungkin masih dipakai.'),
            });
        },
    });
};
</script>

<template>
    <div>
        <div class="content-head">
            <MastersSidebar :entity="entity" :counts="counts" />
            <h2 class="page-title">{{ isEditing ? `Edit ${config.label}` : `Tambah ${config.label}` }}</h2>
        </div>

        <div class="white-card">
        <var-space direction="column" size="small">
            <template v-for="f in config.fields" :key="f.key">
                <div v-if="f.type === 'select'" class="field-block">
                    <label class="field-label">{{ f.label }}</label>
                    <var-select
                        v-model="form[f.key]"
                        :placeholder="f.label"
                        :options="fieldOptions(f.options ?? '')"
                    />
                </div>
                <div v-else class="field-block">
                    <label class="field-label">{{ f.label }}</label>
                    <var-input
                        v-model="form[f.key]"
                        :placeholder="f.label"
                        :type="f.type === 'number' ? 'number' : 'text'"
                        :step="f.step"
                    />
                </div>
            </template>
        </var-space>

        <div v-if="Object.keys(errors).length" role="alert" class="error-list">
            <p v-for="(message, key) in errors" :key="key">{{ message }}</p>
        </div>
        <div v-if="isCapacity" class="field-block standards-block">
            <h3 class="field-label">Grup Titik Uji</h3>
            <p v-if="!groups.length">Belum ada grup. Kapasitas boleh disimpan tanpa grup.</p>
            <details v-for="(group, index) in groups" :key="group.key" :open="group.open" class="group-card" @toggle="group.open = ($event.target as HTMLDetailsElement).open">
                <summary>{{ group.name || `Grup ${index + 1}` }} · {{ group.standards.length }} titik</summary>
                <div class="group-content">
                    <label :for="`group-name-${group.key}`">Nama grup</label>
                    <input :id="`group-name-${group.key}`" v-model="group.name" maxlength="255" :disabled="saving" />
                    <label :for="`group-media-${group.key}`">Media referensi</label>
                    <input :id="`group-media-${group.key}`" v-model="group.reference_media" maxlength="255" :disabled="saving" />
                    <div v-for="(point, pointIndex) in group.standards" :key="point.key" class="standard-row">
                        <label :for="`point-${point.key}`">Titik {{ pointIndex + 1 }}</label>
                        <input :id="`point-${point.key}`" v-model="point.standard_value" type="text" inputmode="decimal" :disabled="saving" :aria-invalid="!!errors[`groups.${index}.standards.${pointIndex}.standard_value`]" />
                        <var-button size="small" text type="danger" :disabled="saving || group.standards.length <= 1" @click="removePoint(group, pointIndex)">Hapus Titik {{ pointIndex + 1 }}</var-button>
                    </div>
                    <div v-if="groupErrors(index).length" role="alert" class="error-list">
                        <p v-for="([key, message]) in groupErrors(index)" :key="key">{{ message }}</p>
                    </div>
                    <var-button size="small" text :disabled="saving" @click="group.standards.push({ standard_value: '', key: nextKey++ })">+ Tambah Titik</var-button>
                    <var-button size="small" text type="danger" :disabled="saving" @click="removeGroup(group)">Hapus Grup</var-button>
                </div>
            </details>
            <var-button size="small" text :disabled="saving" @click="addGroup">+ Tambah Grup</var-button>
        </div>

        <div class="form-actions">
            <var-button v-if="isEditing" type="danger" block @click="remove" class="delete-btn">
                Hapus {{ config.label }}
            </var-button>
            <var-button type="primary" block :loading="saving" @click="submit">
                {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
            </var-button>
        </div>
        </div>
    </div>
</template>

<style scoped>
.content-head {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
}

.page-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.form-actions {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.delete-btn {
    border-radius: 100px;
}

.standards-block {
    margin-top: 16px;
    border-top: 1px solid #f1f5f9;
    padding-top: 14px;
}

.standard-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.standard-row { flex-wrap: wrap; }
.standard-row input { flex: 1; }
.group-card { margin: 12px 0; border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden; }
summary { padding: 14px; background: #fdf0ea; color: #9a4d00; cursor: pointer; font-weight: 600; overflow-wrap: anywhere; }
.group-content { display: grid; gap: 10px; padding: 12px; }
input { box-sizing: border-box; min-width: 0; width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font: inherit; color: #0f172a; background: #f8fafc; }
summary:focus-visible, input:focus-visible { outline: 2px solid #fb8c00; outline-offset: -2px; }
.error-list { color: #b91c1c; font-size: 13px; }
.error-list p { margin: 4px 0; }
</style>
