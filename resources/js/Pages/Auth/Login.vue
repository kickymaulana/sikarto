<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const page = usePage();
const errorMessage = computed(() => {
    const errors = (page.props as any).errors ?? {};
    return errors.message || '';
});
const successMessage = computed(() => (page.props as any)?.flash?.success ?? '');
</script>

<template>
    <Head title="Masuk" />

    <div class="android-layout">
        <div class="android-content login-content">
            <div class="brand-section">
                <div class="logo-avatar">
                    <var-icon name="cog" :size="64" color="#fb8c00" />
                </div>
                <h1 class="app-title">SI KARTO</h1>
                <p class="app-subtitle">Sistem Kalibrasi Toleransi Operasional Alat Ukur Rutin Bulanan</p>
            </div>

            <div class="benefits-box">
                <div class="benefit">✅ Kalibrasi rutin bulanan tanpa kertas</div>
                <div class="benefit">✅ Status PASS/FAIL otomatis per titik uji</div>
                <div class="benefit">✅ Jadwal uji otomatis &amp; riwayat untuk audit</div>
            </div>

            <var-alert v-if="errorMessage" type="danger">{{ errorMessage }}</var-alert>
            <var-alert v-if="successMessage" type="success">{{ successMessage }}</var-alert>

            <a :href="route('sso.redirect')" class="sso-btn">
                <var-icon name="account-circle" :size="22" />
                Masuk dengan SSO Perusahaan
            </a>

            <p class="sso-info">
                Gunakan akun perusahaan (NIK &amp; Password SSO) yang terdaftar.<br>
                Akun baru akan disetujui terlebih dahulu oleh Admin.
            </p>
        </div>

        <div class="android-footer">
            <p>© 2026 SI KARTO • Departemen QA</p>
        </div>
    </div>
</template>

<style scoped>
.login-content {
    justify-content: center;
    align-items: center;
    padding-bottom: 40px;
}

.brand-section {
    text-align: center;
    margin-bottom: 20px;
}

.logo-avatar {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 96px;
    height: 96px;
    background: #fff3e0;
    border-radius: 28px;
    margin-bottom: 12px;
}

.app-title {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 1px;
    margin: 0 0 6px 0;
    color: #f57c00;
}

.app-subtitle {
    font-size: 13px;
    color: #64748b;
    margin: 0 auto;
    max-width: 300px;
    line-height: 1.4;
}

.benefits-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 20px;
    width: 100%;
    max-width: 340px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.benefit {
    font-size: 13px;
    color: #475569;
}

.sso-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    max-width: 340px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border: none;
    border-radius: 100px;
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(251, 140, 0, 0.3);
    margin-top: 8px;
}

.sso-info {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 16px;
    text-align: center;
    line-height: 1.5;
}

.android-footer {
    padding: 16px;
    text-align: center;
}

.android-footer p {
    margin: 0;
    font-size: 11px;
    color: #94a3b8;
    letter-spacing: 0.5px;
}
</style>
