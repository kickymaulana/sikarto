<script setup lang="ts">
import { computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    instruments: {
        data: Array<any>;
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
}>();

const currentPage = computed(() => props.instruments.current_page);

watch(searchState, (val) => {
    router.get(route('instruments.index'), { search: val || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const onPageChange = (page: number) => {
    router.get(route('instruments.index', { page }), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div v-if="instruments.data.length === 0" class="empty">
        Tidak ada alat ukur.
    </div>

    <div v-for="i in instruments.data" :key="i.id" class="row-card">
        <Link
            :href="route('instruments.edit', { instrument: i.id })"
            class="row-link"
        >
            <div class="row-info">
                <span class="name">{{ i.code }}</span>
                <span class="meta">
                    {{ i.type?.name }} • {{ i.brand?.name }} • {{ i.capacity?.name }}
                    {{ i.specification?.name ? '• '+i.specification.name : '' }}
                    <span v-if="i.deleted_at || !i.is_active" class="nonaktif">• NONAKTIF</span>
                </span>
            </div>
        </Link>
    </div>

    <var-pagination
        :current="currentPage"
        :total="instruments.total"
        :size="instruments.per_page"
        :max-pager-count="7"
        @change="onPageChange"
        class="pagination"
    />
</template>

<style scoped>
.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.row-card {
    background: #ffffff;
    border-radius: 12px;
    margin-bottom: 8px;
    border: 1px solid #f1f5f9;
}

.row-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    text-decoration: none;
    color: inherit;
}

.row-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.meta {
    font-size: 12px;
    color: #64748b;
}

.nonaktif {
    color: #ef4444;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}
</style>