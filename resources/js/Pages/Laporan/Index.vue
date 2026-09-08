<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import LaporanSidebar from './Sidebar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    sections: Array<{
        title: string;
        desc: string;
    }>;
}>();

const sidebarOpen = ref(false);
const activeMenu = ref('index');

const goBack = () => router.get(route('dashboard'));
</script>

<template>
    <div class="reports-shell">
        <aside class="desktop-sidebar">
            <div class="sidebar-head">
                <div class="sidebar-brand">
                    <span class="brand-name">SI KARTO</span>
                    <span class="brand-sub">Laporan</span>
                </div>
            </div>
            <div class="sidebar-body">
                <div class="menu-item active" @click="router.get(route('laporan.matrix'))">
                    <var-icon name="file-document-outline" :size="20" />
                    <span>Ringkasan</span>
                </div>
                <div class="menu-item" @click="router.get(route('laporan.matrix'))">
                    <var-icon name="calendar-month" :size="20" />
                    <span>Matriks Uji</span>
                </div>
                <div class="menu-item">
                    <var-icon name="calendar-text" :size="20" />
                    <span>Rekap Tahunan</span>
                </div>
                <div class="menu-item">
                    <var-icon name="chart-box-outline" :size="20" />
                    <span>Status Pengujian</span>
                </div>
            </div>
        </aside>

        <div class="mobile-menu-bar">
            <var-button round text @click="sidebarOpen = true">
                <var-icon name="menu" :size="24" />
            </var-button>
            <span class="mobile-title">Laporan</span>
            <var-button round text @click="goBack">
                <var-icon name="arrow-left" :size="24" />
            </var-button>
        </div>

        <var-popup v-model:show="sidebarOpen" position="left">
            <div class="mobile-sidebar">
                <div class="sidebar-head">
                    <div class="sidebar-brand">
                        <span class="brand-name">SI KARTO</span>
                        <span class="brand-sub">Laporan</span>
                    </div>
                </div>
                <div class="sidebar-body">
                    <div class="menu-item active" @click="router.get(route('laporan.matrix'))">
                        <var-icon name="file-document-outline" :size="20" />
                        <span>Ringkasan</span>
                    </div>
                    <div class="menu-item" @click="router.get(route('laporan.matrix'))">
                        <var-icon name="calendar-month" :size="20" />
                        <span>Matriks Uji</span>
                    </div>
                    <div class="menu-item">
                        <var-icon name="calendar-text" :size="20" />
                        <span>Rekap Tahunan</span>
                    </div>
                    <div class="menu-item">
                        <var-icon name="chart-box-outline" :size="20" />
                        <span>Status Pengujian</span>
                    </div>
                </div>
            </div>
        </var-popup>

        <main class="reports-content">
            <section class="hero-card white-card">
                <h2 class="page-title">Laporan</h2>
                <p class="page-desc">Dummy menu laporan. Nanti isi bisa diganti rekap, grafik, export, dan filter.</p>
            </section>

            <section class="stats-grid">
                <div class="stat-card" v-for="section in sections" :key="section.title">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <div class="stat-title">{{ section.title }}</div>
                        <div class="stat-desc">{{ section.desc }}</div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.reports-shell {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 16px;
}

.desktop-sidebar,
.mobile-sidebar {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    overflow: hidden;
    min-height: calc(100vh - 120px);
}

.sidebar-head {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    padding: 18px;
    color: #ffffff;
}

.brand-mark {
    font-size: 18px;
    font-weight: 800;
}

.brand-sub {
    font-size: 12px;
    opacity: 0.9;
    margin-top: 4px;
}

.sidebar-body {
    padding: 8px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 12px;
    border-radius: 12px;
    color: #334155;
    cursor: pointer;
}

.menu-item.active {
    background: #fff7ed;
    color: #f57c00;
    font-weight: 700;
}

.reports-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.hero-card {
    padding: 16px;
}

.page-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

.page-desc {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 13px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.stat-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    background: #ffffff;
    align-items: center;
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #fdf0ea;
    font-size: 20px;
}

.stat-info {
    min-width: 0;
}

.stat-title {
    font-weight: 700;
    color: #0f172a;
    font-size: 14px;
}

.stat-desc {
    color: #64748b;
    font-size: 12px;
    margin-top: 4px;
}

.mobile-menu-bar {
    display: none;
}

@media (max-width: 960px) {
    .reports-shell {
        grid-template-columns: 1fr;
    }

    .desktop-sidebar {
        display: none;
    }

    .mobile-menu-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 8px 12px;
    }

    .mobile-title {
        font-weight: 800;
        color: #0f172a;
    }
}
</style>
