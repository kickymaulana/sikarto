<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar, Dialog } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    entity: string;
    config: {
        label: string;
        fields: Array<{ key: string; label: string; type: string; step?: string; options?: string }>;
    };
    item: Record<string, any> | null;
    options: Record<string, Array<{ id: number; name: string; code?: string }>>;
}>();

const form = reactive<Record<string, any>>({});
props.config.fields.forEach((f) => {
    form[f.key] = props.item ? props.item[f.key] : f.type === 'select' ? '' : '';
});

const isCapacity = props.entity === 'capacities';
const standards = ref<string[]>(
    props.item?.standards?.length
        ? props.item.standards.map((s: any) => String(s.standard_value ?? s))
        : ['']
);

const isEditing = !!props.item;
const saving = ref(false);

const fieldOptions = (key: string) =>
    (props.options[key] ?? []).map((o) => ({ label: o.name ?? o.code, value: o.id }));

const addStandard = () => { standards.value.push(''); };
const removeStandard = (idx: number) => { standards.value.splice(idx, 1); };

const submit = () => {
    saving.value = true;
    const payload: Record<string, any> = { ...form };
    if (isCapacity) {
        payload.standards = standards.value
            .map((s) => s.trim())
            .filter((s) => s !== '');
    }
    const opts = {
        onSuccess: () => { saving.value = false; },
        onError: () => {
            saving.value = false;
            Snackbar.error('Gagal menyimpan. Periksa kembali input.');
        },
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
    <div class="white-card">
        <var-space direction="column" size="small">
            <template v-for="f in config.fields" :key="f.key">
                <var-select
                    v-if="f.type === 'select'"
                    v-model="form[f.key]"
                    :label="f.label"
                    :options="fieldOptions(f.options ?? '')"
                />
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

        <div v-if="isCapacity" class="field-block standards-block">
            <label class="field-label">Titik Uji (Standar)</label>
            <div v-for="(s, idx) in standards" :key="idx" class="standard-row">
                <var-input
                    v-model="standards[idx]"
                    placeholder="contoh: 500"
                    type="number"
                    step="0.0001"
                />
                <var-button size="small" text round type="danger" @click="removeStandard(idx)">
                    <var-icon name="close-circle-outline" :size="20" />
                </var-button>
            </div>
            <var-button size="small" text @click="addStandard">+ Tambah Titik</var-button>
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
</template>

<style scoped>
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

.standard-row .var-input {
    flex: 1;
}
</style>
