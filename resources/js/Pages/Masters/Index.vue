<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

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
    };
}>();

const listData = ref([...props.items.data]);
const currentPage = ref(props.items.current_page);
const loading = ref(false);
const isRefreshing = ref(false);
const finished = ref(props.items.current_page >= props.items.last_page);

const resolved = (item: Record<string, any>, key: string) => {
    if (key === 'factory' && item.factory) return item.factory.name;
    return item[key] ?? '';
};

const displayName = (item: Record<string, any>) => {
    const key = props.config.columns[0]?.key ?? 'name';
    return resolved(item, key);
};

const displayMeta = (item: Record<string, any>) =>
    props.config.columns.slice(1).map((c) => ({ label: c.label, value: resolved(item, c.key) }));

const filteredItems = computed(() => {
    if (!searchState.value) return listData.value;
    const q = searchState.value.toLowerCase();
    return listData.value.filter((item) =>
        props.config.columns.some((c) => String(resolved(item, c.key)).toLowerCase().includes(q))
    );
});

const loadMore = () => {
    if (finished.value || loading.value) return;
    loading.value = true;
    router.get(route('masters.index', { entity: props.entity, page: currentPage.value + 1 }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['items'],
        onSuccess: (page) => {
            const items = page.props.items as any;
            listData.value.push(...items.data);
            currentPage.value = items.current_page;
            finished.value = currentPage.value >= items.last_page;
            loading.value = false;
        },
        onError: () => { loading.value = false; },
    });
};

const refresh = () => {
    isRefreshing.value = true;
    router.get(route('masters.index', { entity: props.entity }), {}, {
        preserveState: false,
        replace: true,
        only: ['items'],
        onSuccess: (page) => {
            const items = page.props.items as any;
            listData.value = [...items.data];
            currentPage.value = items.current_page;
            finished.value = currentPage.value >= items.last_page;
            isRefreshing.value = false;
        },
        onError: () => { isRefreshing.value = false; },
    });
};
</script>

<template>
    <var-pull-refresh v-model="isRefreshing" @refresh="refresh">
        <var-list
            v-model:loading="loading"
            :finished="finished"
            loading-text="Memuat..."
            finished-text="Semua data sudah dimuat"
            @load="loadMore"
        >
            <div v-if="filteredItems.length === 0 && !loading" class="empty">
                Belum ada data {{ config.label.toLowerCase() }}.
            </div>

            <div v-for="item in filteredItems" :key="item.id" class="row-card">
                <Link
                    :href="route('masters.edit', { entity, id: item.id })"
                    class="row-link"
                >
                    <div class="row-info">
                        <span class="name">{{ displayName(item) }}</span>
                        <span v-if="displayMeta(item).length" class="meta">
                            <template v-for="(m, idx) in displayMeta(item)" :key="idx">
                                {{ m.label }}: {{ m.value }}{{ idx < displayMeta(item).length - 1 ? ' • ' : '' }}
                            </template>
                        </span>
                    </div>
                </Link>
            </div>
        </var-list>
    </var-pull-refresh>
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
</style>
