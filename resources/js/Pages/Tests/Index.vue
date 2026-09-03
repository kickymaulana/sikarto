<script setup lang="ts">
import { computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tests: {
        data: Array<any>;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
    };
    filters: {
        status?: string;
        search?: string;
    };
}>();

searchState.value = props.filters?.search ?? '';

const currentPage = computed(() => props.tests.current_page);

const performSearch = (search: string) => {
    router.get(route('tests.index'), { search: search || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(searchState, (val) => performSearch(val));

const onPageChange = (page: number) => {
    router.get(route('tests.index', { page }), {
        preserveScroll: true,
        preserveState: true,
    });
};

const chipType = (status: string) => (status === 'PASS' ? 'success' : 'danger');
</script>

<template>
    <div v-if="tests.data.length === 0" class="empty-state">
        <var-icon name="file-document-outline" :size="64" color="#cbd5e1" />
        <p>Tidak ada data pengujian.</p>
    </div>

    <div v-else class="request-list">
        <Link
            v-for="t in tests.data"
            :key="t.id"
            :href="route('tests.show', { test: t.id })"
            class="request-card"
        >
            <div class="request-header">
                <span class="request-code">{{ t.instrument?.code }}</span>
                <var-chip :type="chipType(t.status)" size="small" round>{{ t.status }}</var-chip>
            </div>
            <div class="request-meta">
                <span>{{ t.instrument?.type?.name }}</span>
                <span>{{ t.test_date }}</span>
                <span>Next: {{ t.next_test_date }}</span>
                <span>{{ t.tester?.name }}</span>
            </div>
        </Link>
    </div>

    <var-pagination
        :current="currentPage"
        :total="tests.total"
        :size="tests.per_page"
        :max-pager-count="7"
        @change="onPageChange"
        class="pagination-wrap"
    />
</template>

<style scoped>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-top: 60px;
    color: #94a3b8;
}

.empty-state p {
    font-size: 13px;
}

.request-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    display: block;
    text-decoration: none;
    color: inherit;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.request-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 13px;
}

.request-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}

.pagination-wrap {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}
</style>
