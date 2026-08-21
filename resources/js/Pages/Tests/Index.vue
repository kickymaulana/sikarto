<script setup lang="ts">
import { ref, watch } from 'vue';
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
    };
    filters: {
        status?: string;
        search?: string;
    };
}>();

searchState.value = props.filters?.search ?? '';

const isRefreshing = ref(false);

const performSearch = (search: string) => {
    router.get(route('tests.index'), { search: search || undefined }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { isRefreshing.value = false; },
    });
};

watch(searchState, (val) => performSearch(val));

const handleRefresh = () => {
    isRefreshing.value = true;
    router.get(route('tests.index'), { search: searchState.value || undefined }, {
        preserveState: false,
        onSuccess: () => { isRefreshing.value = false; },
        onError: () => { isRefreshing.value = false; },
    });
};

const chipType = (status: string) => (status === 'PASS' ? 'success' : 'danger');
</script>

<template>
    <var-pull-refresh v-model="isRefreshing" @refresh="handleRefresh">
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
    </var-pull-refresh>

    <div class="pagination">
        <span
            v-if="tests.current_page > 1"
            class="page-btn"
            @click="router.get(route('tests.index', { page: tests.current_page - 1 }), {}, { preserveScroll: true, preserveState: true })"
        >
            Sebelumnya
        </span>
        <span class="page-info">
            {{ tests.from }}–{{ tests.to }} dari {{ tests.total }}
        </span>
        <span
            v-if="tests.current_page < tests.last_page"
            class="page-btn"
            @click="router.get(route('tests.index', { page: tests.current_page + 1 }), {}, { preserveScroll: true, preserveState: true })"
        >
            Selanjutnya
        </span>
    </div>
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

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    font-size: 13px;
    padding: 8px 0;
}

.page-btn {
    padding: 6px 16px;
    border-radius: 8px;
    background: #fff3e0;
    color: #f57c00;
    cursor: pointer;
    font-weight: 600;
    user-select: none;
}

.page-info {
    color: #64748b;
}
</style>
