<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar, Dialog } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    instrument: Record<string, any> | null;
    options: {
        factories: Array<{ id: number; code: string; name: string }>;
        departments: Array<{ id: number; name: string; factory_id: number }>;
        types: Array<{ id: number; name: string }>;
        brands: Array<{ id: number; name: string }>;
        capacities: Array<{ id: number; name: string }>;
        limits: Array<{ id: number; name: string }>;
    };
}>();

const isEditing = !!props.instrument;

const form = reactive<any>({
    code: props.instrument?.code ?? '',
    factory_id: props.instrument?.factory_id ?? '',
    department_id: props.instrument?.department_id ?? '',
    instrument_type_id: props.instrument?.instrument_type_id ?? '',
    brand_id: props.instrument?.brand_id ?? '',
    capacity_id: props.instrument?.capacity_id ?? '',
    acceptable_limit_id: props.instrument?.acceptable_limit_id ?? '',
    notes: props.instrument?.notes ?? '',
});

const saving = ref(false);

const filteredDepartments = computed(() =>
    form.factory_id
        ? props.options.departments.filter((d) => d.factory_id === form.factory_id)
        : props.options.departments
);

const toOpts = (list: Array<Record<string, any>>, labelKey = 'name') =>
    list.map((o) => ({ label: o[labelKey], value: o.id }));

const submit = () => {
    saving.value = true;
    const opts = {
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal menyimpan.'); },
    };
    if (isEditing) {
        router.put(route('instruments.update', { instrument: props.instrument!.id }), form, opts);
    } else {
        router.post(route('instruments.store'), form, opts);
    }
};

const remove = () => {
    Dialog({
        title: 'Nonaktifkan Alat',
        message: `Yakin ingin menonaktifkan alat "${form.code}"?`,
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('instruments.destroy', { instrument: props.instrument!.id }), {
                onError: () => Snackbar.error('Gagal menonaktifkan.'),
            });
        },
    });
};
</script>

<template>
    <div class="white-card">
        <var-space direction="column" size="small">
            <div class="field-block">
                <label class="field-label">Kode Alat *</label>
                <var-input v-model="form.code" placeholder="cth: W.FL.5" />
            </div>
            <var-select v-model="form.factory_id" label="Factory *" :options="toOpts(options.factories)" @change="form.department_id = ''" />
            <var-select v-model="form.department_id" label="Departemen *" :options="toOpts(filteredDepartments)" />
            <var-select v-model="form.instrument_type_id" label="Jenis Alat *" :options="toOpts(options.types)" />
            <var-select v-model="form.brand_id" label="Merk *" :options="toOpts(options.brands)" />
            <var-select v-model="form.capacity_id" label="Kapasitas *" :options="toOpts(options.capacities)" />
            <var-select v-model="form.acceptable_limit_id" label="Toleransi *" :options="toOpts(options.limits)" />
            <div class="field-block">
                <label class="field-label">Catatan (opsional)</label>
                <var-input v-model="form.notes" :textarea="true" />
            </div>
        </var-space>
        <div class="form-actions">
            <var-button v-if="isEditing" type="danger" block @click="remove" class="delete-btn">
                Nonaktifkan Alat
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
