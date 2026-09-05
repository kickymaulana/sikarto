<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{
    entity: string;
    counts: Record<string, number>;
}>();

const show = ref(false);

const menuItems = [
    { label: 'Factory', icon: 'home', value: 'factories' },
    { label: 'Departemen', icon: 'card-account-details', value: 'departments' },
    { label: 'Jenis Alat', icon: 'format-list-checkbox', value: 'types' },
    { label: 'Merk', icon: 'tag', value: 'brands' },
    { label: 'Kapasitas', icon: 'cog', value: 'capacities' },
    { label: 'Acceptable Limit', icon: 'checkbox-marked-circle', value: 'limits' },
    { label: 'Spesifikasi', icon: 'file-document-outline', value: 'specifications' },
    { label: 'Alat Ukur', icon: 'wrench', value: 'instruments' },
    { label: 'Matriks Uji', icon: 'calendar-month', value: 'matrix' },
];

const go = (value: string) => {
    show.value = false;
    if (value === 'instruments') {
        router.get(route('instruments.index'));
    } else if (value === 'matrix') {
        router.get(route('masters.matrix'));
    } else if (value !== props.entity) {
        router.get(route('masters.index', { entity: value }));
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
                <var-avatar color="#ffffff" size="normal" round>
                    <var-icon name="account-circle" :size="22" color="#fb8c00" />
                </var-avatar>
                <div class="sidebar-brand">
                    <span class="brand-name">SI KARTO</span>
                    <span class="brand-sub">Master Data</span>
                </div>
            </div>
            <div class="sidebar-body">
                <var-cell
                    v-for="m in menuItems"
                    :key="m.value"
                    :title="`${m.label} (${counts[m.value] ?? 0})`"
                    :class="{ active: m.value === entity }"
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