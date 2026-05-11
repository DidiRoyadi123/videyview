<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import { useToast } from '@/Composables/useToast';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    videos: Object,
    categories: Array,
    filters: Object
});

const { success: toastSuccess } = useToast();
const search = ref(props.filters.search || '');

// Track changed rows to send only what's necessary
const changes = ref({});

const onFieldChange = (video) => {
    changes.value[video.id] = {
        id: video.id,
        title: video.title,
        category_id: video.category_id,
        is_premium: video.is_premium
    };
};

const submitChanges = () => {
    const updateArray = Object.values(changes.value);
    if (updateArray.length === 0) return;

    router.post(route('admin.videos.bulk-update-metadata'), {
        updates: updateArray
    }, {
        onSuccess: () => {
            toastSuccess('Berhasil memperbarui ' + updateArray.length + ' video.');
            changes.value = {};
        },
        preserveScroll: true
    });
};

watch(search, (value) => {
    router.get(route('admin.videos.bulk-edit'), { search: value }, {
        preserveState: true,
        replace: true
    });
});

const toggleAllPremium = (val) => {
    props.videos.data.forEach(v => {
        v.is_premium = val;
        onFieldChange(v);
    });
};
</script>

<template>
    <Head title="Bulk Metadata Editor - VideyView" />

    <MaterioLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Bulk Metadata Editor</h2>
                    <p class="text-sm text-gray-500 mt-1">Grid-based high-speed library management.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        @click="submitChanges" 
                        :disabled="Object.keys(changes).length === 0"
                        class="px-8 py-2.5 bg-[#8C57FF] text-white rounded-xl font-bold text-sm shadow-lg shadow-[#8C57FF]/30 disabled:opacity-50 transition-all uppercase tracking-widest"
                    >
                        Save {{ Object.keys(changes).length }} Changes
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="materio-card p-4">
                <div class="flex items-center gap-4">
                    <div class="relative flex-1 max-w-md">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari berdasarkan judul..." 
                            class="w-full bg-gray-50 border-gray-100 text-[#3A3541] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] px-10 py-2.5 text-sm transition-all"
                        />
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 ml-auto">
                        <button @click="toggleAllPremium(true)" class="text-[10px] font-black uppercase text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 hover:bg-indigo-100 transition">Set All Premium</button>
                        <button @click="toggleAllPremium(false)" class="text-[10px] font-black uppercase text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 hover:bg-gray-100 transition">Set All Free</button>
                    </div>
                </div>
            </div>

            <!-- Grid Editor -->
            <div class="materio-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest w-16">ID</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Video Metadata</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest w-48">Category</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest w-24">Premium</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest w-16">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr 
                                v-for="video in videos.data" 
                                :key="video.id" 
                                :class="[changes[video.id] ? 'bg-amber-50/30' : 'hover:bg-gray-50/30']"
                                class="transition-colors duration-200"
                            >
                                <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ video.id }}</td>
                                <td class="px-6 py-4">
                                    <input 
                                        v-model="video.title" 
                                        @change="onFieldChange(video)"
                                        class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-[#3A3541] p-0 mb-1"
                                    />
                                    <div class="text-[10px] text-gray-400 font-medium truncate max-w-xs">{{ video.slug }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <select 
                                        v-model="video.category_id" 
                                        @change="onFieldChange(video)"
                                        class="w-full bg-white/50 border-gray-100 text-xs rounded-lg focus:ring-2 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all py-1 px-3"
                                    >
                                        <option :value="null">Uncategorized</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="video.is_premium = !video.is_premium; onFieldChange(video)"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center transition-all mx-auto"
                                        :class="video.is_premium ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-gray-100 text-gray-400'"
                                    >
                                        <div v-if="video.is_premium" class="text-xs">👑</div>
                                        <div v-else class="text-xs">🆓</div>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="changes[video.id]" class="w-2 h-2 bg-amber-500 rounded-full animate-pulse mx-auto" title="Unsaved changes"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="videos.data.length === 0" class="py-20 text-center">
                    <div class="text-4xl mb-4">🔍</div>
                    <div class="text-sm font-bold text-gray-400 uppercase tracking-widest">No videos found</div>
                </div>

                <div class="p-6 border-t border-gray-50 flex items-center justify-between">
                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                        Showing {{ videos.data.length }} of {{ videos.total }} items
                    </div>
                    <Pagination :links="videos.links" />
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>
