<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';

const props = defineProps<{
    user: {
        id: number;
        name: string;
    };
    factories: Array<{ id: number; name: string }>;
}>();

const form = reactive({
    role: '',
    factory_id: '',
});
const saving = ref(false);

const submit = () => {
    if (!form.role) {
        Snackbar.warning('Pilih peran terlebih dahulu.');
        return;
    }
    saving.value = true;
    router.post(route('pending-role.submit'), form, {
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal mengirim permintaan.'); },
    });
};
</script>

<template>
    <div class="android-layout">
        <div class="android-content pending-content">
            <div class="white-card">
                <h2 class="pending-title">Halo, {{ user.name }} 👋</h2>
                <p class="pending-sub">
                    Akun Anda sudah terhubung dengan SSO, tetapi belum diaktifkan Admin.
                    Pilih peran untuk mengirim permintaan persetujuan.
                </p>

                <var-space direction="column" size="small">
                    <var-select
                        v-model="form.role"
                        placeholder="Pilih Peran"
                        :options="[
                            { label: 'Super Admin', value: 'super_admin' },
                            { label: 'Admin', value: 'admin' },
                            { label: 'Inspector / QA', value: 'inspector' },
                            { label: 'User', value: 'user' },
                        ]"
                    />
                    <var-select
                        v-model="form.factory_id"
                        placeholder="Factory (opsional)"
                        :options="factories.map((f) => ({ label: f.name, value: f.id }))"
                    />
                </var-space>

                <var-button type="primary" block class="submit-btn" :loading="saving" @click="submit">
                    Kirim Permintaan
                </var-button>

                <p class="pending-note">Setelah dikirim, tunggu persetujuan Admin untuk mulai menggunakan SI KARTO.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pending-content {
    justify-content: center;
    max-width: 420px;
    margin: 0 auto;
    width: 100%;
}

.pending-title {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

.pending-sub {
    margin: 0 0 16px;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

.submit-btn {
    margin-top: 16px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-radius: 100px;
    font-weight: 700;
}

.pending-note {
    margin: 16px 0 0;
    font-size: 12px;
    color: #94a3b8;
    text-align: center;
}
</style>
