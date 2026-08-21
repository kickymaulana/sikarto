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

const isEditing = !!props.item;
const saving = ref(false);

const fieldOptions = (key: string) =>
    (props.options[key] ?? []).map((o) => ({ label: o.name ?? o.code, value: o.id }));

const submit = () => {
    saving.value = true;
    const opts = {
        onSuccess: () => { saving.value = false; },
        onError: () => {
            saving.value = false;
            Snackbar.error('Gagal menyimpan. Periksa kembali input.');
        },
    };
    if (isEditing) {
        router.put(route('masters.update', { entity: props.entity, id: props.item!.id }), form, opts);
    } else {
        router.post(route('masters.store', { entity: props.entity }), form, opts);
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
                <var-input
                    v-else
                    v-model="form[f.key]"
                    :label="f.label"
                    :type="f.type === 'number' ? 'number' : 'text'"
                    :step="f.step"
                />
            </template>
        </var-space>
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
</style>
