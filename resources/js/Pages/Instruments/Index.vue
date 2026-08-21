<script setup lang="ts">
import { ref, computed } from 'vue';
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
    };
}>();

const listData = ref([...props.instruments.data]);
const currentPage = ref(props.instruments.current_page);
const loading = ref(false);
const isRefreshing = ref(false);
const finished = ref(props.instruments.current_page >= props.instruments.last_page);

const filteredList = computed(() => {
    if (!searchState.value) return listData.value;
    const q = searchState.value.toLowerCase();
    return listData.value.filter((i) =>
        i.code?.toLowerCase().includes(q) || i.type?.name?.toLowerCase().includes(q) ||
        i.brand?.name?.toLowerCase().includes(q)
    );
});

const loadMore = () => {
    if (finished.value || loading.value) return;
    loading.value = true;
    router.get(route('instruments.index', { page: currentPage.value + 1 }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['instruments'],
        onSuccess: (page) => {
            const items = page.props.instruments as any;
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
    router.get(route('instruments.index'), {}, {
        preserveState: false,
        replace: true,
        only: ['instruments'],
        onSuccess: (page) => {
            const items = page.props.instruments as any;
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
            <div v-if="filteredList.length === 0 && !loading" class="empty">
                Tidak ada alat ukur.
            </div>

            <div v-for="i in filteredList" :key="i.id" class="row-card">
                <Link
                    :href="route('instruments.edit', { instrument: i.id })"
                    class="row-link"
                >
                    <div class="row-info">
                        <span class="name">{{ i.code }}</span>
                        <span class="meta">
                            {{ i.type?.name }} • {{ i.brand?.name }} • {{ i.capacity?.name }}
                            {{ i.is_active ? '' : '• NONAKTIF' }}
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
