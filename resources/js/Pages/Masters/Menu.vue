<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    counts: Record<string, number>;
}>();

const masters = [
    { key: 'factories', label: 'Factory', icon: '🏭', color: '#fff3e0', border: '#ffe0b2', text: '#f57c00' },
    { key: 'departments', label: 'Departemen', icon: '🏢', color: '#eef2ff', border: '#c7d2fe', text: '#4f46e5' },
    { key: 'types', label: 'Jenis Alat', icon: '⚖️', color: '#ecfeff', border: '#a5f3fc', text: '#0e7490' },
    { key: 'brands', label: 'Merk', icon: '🏷️', color: '#f0fdf4', border: '#bbf7d0', text: '#15803d' },
    { key: 'capacities', label: 'Kapasitas', icon: '📏', color: '#f0f9ff', border: '#bae6fd', text: '#0369a1' },
    { key: 'limits', label: 'Acceptable Limit', icon: '🎯', color: '#fef3c7', border: '#fde68a', text: '#b45309' },
    { key: 'specifications', label: 'Spesifikasi', icon: '📄', color: '#f5f3ff', border: '#ddd6fe', text: '#6d28d9' },
    { key: 'instruments', label: 'Alat Ukur', icon: '🔧', color: '#fdf0ea', border: '#fed7aa', text: '#ea580c' },
];

const go = (key: string) => {
    if (key === 'instruments') {
        router.get(route('instruments.index'));
    } else {
        router.get(route('masters.index', { entity: key }));
    }
};
</script>

<template>
    <div class="menu-grid">
        <div
            v-for="m in masters"
            :key="m.key"
            class="menu-card"
            :style="{ background: m.color, borderColor: m.border }"
            @click="go(m.key)"
        >
            <div class="menu-icon">{{ m.icon }}</div>
            <div class="menu-text">
                <span class="menu-label" :style="{ color: m.text }">{{ m.label }}</span>
                <span class="menu-count">{{ counts[m.key] ?? 0 }} data</span>
            </div>
            <var-icon name="chevron-right" :size="20" color="#94a3b8" />
        </div>
    </div>
</template>

<style scoped>
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.menu-card {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 16px;
    padding: 14px;
    border: 2px solid;
    cursor: pointer;
}

.menu-icon {
    font-size: 26px;
}

.menu-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.menu-label {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
}

.menu-count {
    font-size: 11px;
    color: #64748b;
}
</style>
