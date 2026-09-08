<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{
    active: string;
}>();

const show = ref(false);

const menuItems = [
    { label: 'Ringkasan', icon: 'file-document-outline', value: 'index' },
    { label: 'Rekap Bulanan', icon: 'calendar-month', value: 'monthly' },
    { label: 'Rekap Tahunan', icon: 'calendar-text', value: 'yearly' },
    { label: 'Status Pengujian', icon: 'chart-box-outline', value: 'status' },
];

const go = (value: string) => {
    show.value = false;
    if (value === 'index') {
        router.get(route('laporan.index'));
    } else if (value === 'monthly') {
        router.get(route('laporan.matrix'));
    }
};
</script>

<template>
    <var-button round text @click="show = true">
        <var-icon name="menu" :size="24" />
    </var-button>

    <var-popup v-model:show="show" position="left">
        <div class="sidebar">
            <div class="sidebar-head">
                <div class="sidebar-brand">
                    <span class="brand-name">SI KARTO</span>
                    <span class="brand-sub">Laporan</span>
                </div>
            </div>
            <div class="sidebar-body">
                <var-cell
                    v-for="m in menuItems"
                    :key="m.value"
                    :title="m.label"
                    :class="{ active: m.value === active }"
                    :ripple="true"
                    @click="go(m.value)"
                >
                    <template #icon>
                        <var-icon :name="m.icon" :size="20" class="cell-icon" />
                    </template>
                </var-cell>
            </div>
        </div>
    </var-popup>
</template>

<style scoped>
.sidebar {
    width: 300px;
    min-height: 100vh;
}

.sidebar-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
}

.sidebar-brand {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 18px;
    font-weight: 800;
    color: #ffffff;
}

.brand-sub {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
}

.sidebar-body {
    padding: 8px 4px;
}

.cell-icon {
    margin-right: 16px;
}

.sidebar :deep(.var-cell.active) {
    background: #fff7ed;
    color: #f57c00;
    font-weight: 700;
}
</style>
