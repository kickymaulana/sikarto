<script setup lang="ts">
import { computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';
import MastersSidebar from './Sidebar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    entity: string;
    config: {
        label: string;
        columns: Array<{ key: string; label: string }>;
    };
    items: {
        data: Array<Record<string, any>>;
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    counts: Record<string, number>;
}>();

const currentPage = computed(() => props.items.current_page);

watch(searchState, (val) => {
    router.get(route('masters.index', { entity: props.entity }), { search: val || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const onPageChange = (page: number) => {
    router.get(route('masters.index', { entity: props.entity, page }), {
        preserveScroll: true,
        preserveState: true,
    });
};

const resolved = (item: Record<string, any>, key: string) => {
    if (key === 'factory' && item.factory) return item.factory.name;
    return item[key] ?? '';
};

const displayName = (item: Record<string, any>) => {
    const key = props.config.columns[0]?.key ?? 'name';
    return resolved(item, key);
};
</script>

<template>
    <div>
        <div class="content-head">
            <MastersSidebar :entity="entity" :counts="counts" />
            <h2 class="page-title">{{ config.label }}</h2>
        </div>

        <var-table class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>{{ config.columns[0]?.label }}</th>
                        <th v-for="c in config.columns.slice(1)" :key="c.key">{{ c.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="items.data.length === 0">
                        <td :colspan="config.columns.length" class="empty">
                            Belum ada data {{ config.label.toLowerCase() }}.
                        </td>
                    </tr>
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="data-row"
                        @click="router.get(route('masters.edit', { entity, id: item.id }))"
                    >
                        <td>{{ displayName(item) }}</td>
                        <td v-for="c in config.columns.slice(1)" :key="c.key">{{ resolved(item, c.key) }}</td>
                    </tr>
                </tbody>
            </table>
        </var-table>

        <var-pagination
            :current="currentPage"
            :total="items.total"
            :size="items.per_page"
            :max-pager-count="7"
            @change="onPageChange"
            class="pagination"
        />
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

.data-table {
    margin-bottom: 16px;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.data-table th,
.data-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
}

.data-table th {
    font-weight: 700;
    color: #475569;
    background: #f8fafc;
}

.data-row {
    cursor: pointer;
    transition: background 0.15s;
}

.data-row:hover {
    background: #fff7ed;
}

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 40px;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}
</style>
