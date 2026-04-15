<script setup>
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess, error: toastError } = useToast();

const props = defineProps({
    categories: Array,
});

const isEditing = ref(null);

const form = useForm({
    name: '',
    icon: '📁',
    order: 0,
});

const editForm = useForm({
    id: null,
    name: '',
    icon: '',
    order: 0,
});

// ── Emoji Picker Logic ──────────────────────────────────────────
const showPicker = ref(false);       // for create form
const showEditPicker = ref(null);    // for edit row (stores cat.id)

const emojiGroups = [
    {
        label: '🎬 Film & Video',
        emojis: ['🎬','🎥','📽️','🎞️','📹','🎦','🎭','🎪','🎨','🖥️','📺','🎙️','🎤','🎧','📻'],
    },
    {
        label: '🌍 Genre & Tema',
        emojis: ['🌍','🌏','🌎','🏆','⚔️','🔮','💫','🌌','🏔️','🌊','🌋','🏜️','🌿','🦁','🐉'],
    },
    {
        label: '😂 Mood & Suasana',
        emojis: ['😂','😱','😍','🤩','😭','🤔','💪','🔥','❤️','💀','👻','✨','🌟','⚡','🌈'],
    },
    {
        label: '🎵 Hiburan',
        emojis: ['🎵','🎶','🎸','🎹','🎺','🎻','🥁','🎮','🕹️','🎲','🃏','♟️','🎯','🎳','🎰'],
    },
    {
        label: '📚 Edukasi & Info',
        emojis: ['📚','📖','📝','✏️','🔬','🔭','💡','🧪','🧬','🏫','🎓','📊','📈','🗺️','🧭'],
    },
    {
        label: '🏠 Gaya Hidup',
        emojis: ['🏠','🏙️','🌆','🍕','🍜','🍱','☕','🧘','🏋️','🚀','✈️','🚂','🛒','💄','👗'],
    },
    {
        label: '⭐ Khusus',
        emojis: ['📁','📂','🗂️','💾','📌','📍','🔖','🏷️','🎀','🎁','🏅','🥇','👑','💎','🆕'],
    },
];

const activeGroup = ref(0);

const selectEmoji = (emoji, mode) => {
    if (mode === 'create') {
        form.icon = emoji;
        showPicker.value = false;
    } else {
        editForm.icon = emoji;
        showEditPicker.value = null;
    }
};

const togglePicker = (mode, catId = null) => {
    activeGroup.value = 0;
    if (mode === 'create') {
        showPicker.value = !showPicker.value;
        showEditPicker.value = null;
    } else {
        showEditPicker.value = showEditPicker.value === catId ? null : catId;
        showPicker.value = false;
    }
};

// Close picker when clicking outside
const handleOutsideClick = (e) => {
    if (!e.target.closest('.emoji-picker-container')) {
        showPicker.value = false;
        showEditPicker.value = null;
    }
};

onMounted(() => document.addEventListener('click', handleOutsideClick));
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));
// ───────────────────────────────────────────────────────────────

const startEdit = (cat) => {
    isEditing.value = cat.id;
    editForm.id = cat.id;
    editForm.name = cat.name;
    editForm.icon = cat.icon;
    editForm.order = cat.order;
    showEditPicker.value = null;
};

const cancelEdit = () => {
    isEditing.value = null;
    editForm.reset();
    showEditPicker.value = null;
};

const submitStore = () => {
    form.post(route('admin.categories.store'), {
        onSuccess: () => {
            form.reset();
            form.icon = '📁';
            toastSuccess('Kategori berhasil ditambahkan.');
        },
    });
};

const submitUpdate = () => {
    editForm.patch(route('admin.categories.update', editForm.id), {
        onSuccess: () => {
            isEditing.value = null;
            toastSuccess('Kategori berhasil diperbarui.');
        },
    });
};

const deleteCategory = (id) => {
    if (confirm('Yakin ingin menghapus kategori ini? Video yang menggunakan kategori ini akan tetap ada namun tanpa kategori.')) {
        router.delete(route('admin.categories.destroy', id), {
            onSuccess: () => toastSuccess('Kategori berhasil dihapus.'),
        });
    }
};
</script>

<template>
    <Head title="Admin - Manajemen Kategori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Manajemen <span class="text-indigo-500">Kategori</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Pengaturan Taksonomi Konten</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Create Category Form -->
                <div class="glass shadow-royale p-5 sm:p-6 rounded-2xl border border-[rgb(var(--border-main))] relative overflow-hidden">
                    <div class="absolute -top-16 -right-16 w-40 h-40 bg-indigo-500/10 rounded-full blur-[60px] pointer-events-none"></div>
                    <h3 class="text-base font-black text-[rgb(var(--text-main))] mb-5 flex items-center gap-3 uppercase tracking-tight">
                        <span class="w-1 h-5 bg-indigo-600 rounded-full"></span>
                        Tambah Kategori Baru
                    </h3>
                    
                    <form @submit.prevent="submitStore" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div class="space-y-1.5">
                            <InputLabel for="name" value="Nama Kategori" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="name" type="text" v-model="form.name" required class="block w-full !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-sm !shadow-inner focus:ring-2 focus:ring-indigo-500/30 transition-all" placeholder="e.g. Barat" />
                            <InputError :message="form.errors.name" class="mt-1 text-xs" />
                        </div>
                        
                        <!-- Emoji Picker Field -->
                        <div class="space-y-1.5 emoji-picker-container relative">
                            <InputLabel value="Ikon Kategori" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <div class="flex items-center gap-2">
                                <!-- Emoji Preview Button -->
                                <button 
                                    type="button"
                                    @click.stop="togglePicker('create')"
                                    class="w-12 h-10 flex items-center justify-center text-2xl bg-[rgb(var(--bg-input))] rounded-xl border border-transparent hover:border-indigo-500/40 transition-all shadow-inner flex-shrink-0"
                                    title="Pilih ikon"
                                >
                                    {{ form.icon || '📁' }}
                                </button>
                                <!-- Text fallback input -->
                                <TextInput 
                                    type="text" 
                                    v-model="form.icon" 
                                    class="flex-1 !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-lg !shadow-inner text-center focus:ring-2 focus:ring-indigo-500/30 transition-all" 
                                    placeholder="📁" 
                                    maxlength="4"
                                />
                            </div>
                            <InputError :message="form.errors.icon" class="mt-1 text-xs" />

                            <!-- Emoji Picker Popup -->
                            <Teleport to="body">
                                <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="ease-in duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                    <div v-if="showPicker" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showPicker = false"></div>
                                        <div class="relative bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden w-full max-w-[320px] picker-popup" @click.stop>
                                            <div class="flex items-center justify-between px-3 py-2 border-b border-white/5 bg-white/5">
                                                <h4 class="text-[10px] font-black text-white uppercase tracking-widest">Pilih Ikon</h4>
                                                <button type="button" @click="showPicker = false" class="text-slate-500 hover:text-white transition">✖</button>
                                            </div>
                                            <!-- Group Tabs -->
                                    <div class="flex overflow-x-auto no-scrollbar border-b border-white/5 bg-black/20">
                                        <button 
                                            v-for="(group, idx) in emojiGroups" 
                                            :key="idx"
                                            type="button"
                                            @click="activeGroup = idx"
                                            class="px-3 py-2.5 text-sm flex-shrink-0 transition-all whitespace-nowrap"
                                            :class="activeGroup === idx 
                                                ? 'border-b-2 border-indigo-500 text-white bg-indigo-500/10' 
                                                : 'text-slate-500 hover:text-slate-300 hover:bg-white/5 border-b-2 border-transparent'"
                                        >
                                            {{ group.label.split(' ')[0] }}
                                        </button>
                                    </div>
                                    <!-- Emoji label -->
                                    <div class="px-3 pt-2 pb-1">
                                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ emojiGroups[activeGroup].label }}</div>
                                    </div>
                                    <!-- Emoji Grid -->
                                    <div class="grid grid-cols-8 gap-0.5 p-2 max-h-44 overflow-y-auto">
                                        <button 
                                            v-for="emoji in emojiGroups[activeGroup].emojis" 
                                            :key="emoji"
                                            type="button"
                                            @click="selectEmoji(emoji, 'create')"
                                            class="w-9 h-9 text-xl flex items-center justify-center rounded-xl hover:bg-indigo-500/20 transition-all hover:scale-125 active:scale-95"
                                            :class="form.icon === emoji ? 'bg-indigo-500/30 ring-1 ring-indigo-500' : ''"
                                            :title="emoji"
                                        >{{ emoji }}</button>
                                    </div>
                                    <!-- Current selection -->
                                    <div class="px-3 pb-3 pt-1 border-t border-white/5 flex items-center gap-2">
                                        <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Terpilih:</span>
                                        <span class="text-xl">{{ form.icon }}</span>
                                        <span class="text-xs text-slate-400 font-mono">{{ form.icon }}</span>
                                    </div>
                                        </div>
                                    </div>
                                </Transition>
                            </Teleport>
                        </div>

                        <div class="space-y-1.5">
                            <InputLabel for="order" value="Urutan" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="order" type="number" v-model="form.order" class="block w-full !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-sm !shadow-inner focus:ring-2 focus:ring-indigo-500/30 transition-all" />
                            <InputError :message="form.errors.order" class="mt-1 text-xs" />
                        </div>

                        <PrimaryButton class="!py-2.5 !rounded-xl !text-xs !font-bold !tracking-widest shadow-lg shadow-indigo-600/20 transition-all active:scale-95" :disabled="form.processing">
                            {{ form.processing ? 'Memproses...' : 'Tambahkan' }}
                        </PrimaryButton>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="glass-dark rounded-2xl border border-white/10 overflow-hidden shadow-xl">
                    <div class="px-5 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <h3 class="font-black text-white uppercase tracking-widest text-sm">Daftar Kategori Aktif</h3>
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 py-1 bg-white/5 rounded-full border border-white/5">{{ categories.length }} Kategori</div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-black uppercase text-slate-500 tracking-widest border-b border-white/10 bg-white/[0.02]">
                                <tr>
                                    <th class="py-3 px-4 w-16">Urutan</th>
                                    <th class="py-3 px-4 w-24 text-center">Ikon</th>
                                    <th class="py-3 px-4">Nama Kategori</th>
                                    <th class="py-3 px-4 text-center">Video</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="cat in categories" :key="cat.id" class="group hover:bg-white/[0.04] transition-all duration-200">
                                    <td class="py-3 px-4">
                                        <div v-if="isEditing === cat.id">
                                            <TextInput type="number" v-model="editForm.order" class="w-16 !bg-white/10 !border-none !rounded-lg !py-1.5 !px-2.5 !text-sm shadow-inner" />
                                        </div>
                                        <span v-else class="text-sm font-bold text-slate-500 group-hover:text-indigo-400 transition-colors">{{ cat.order }}</span>
                                    </td>

                                    <!-- Ikon cell with inline picker -->
                                    <td class="py-3 px-4 text-center">
                                        <div v-if="isEditing === cat.id" class="emoji-picker-container relative flex items-center justify-center gap-1.5">
                                            <!-- Emoji preview / picker trigger -->
                                            <button 
                                                type="button"
                                                @click.stop="togglePicker('edit', cat.id)"
                                                class="text-2xl w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-indigo-500/20 transition-all border border-transparent hover:border-indigo-500/40"
                                                title="Pilih ikon"
                                            >{{ editForm.icon || '📁' }}</button>

                                            <!-- Edit Picker Popup -->
                                            <Teleport to="body">
                                                <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="ease-in duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                                    <div v-if="showEditPicker === cat.id" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                                                        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showEditPicker = null"></div>
                                                        <div class="relative bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden w-full max-w-[320px] picker-popup" @click.stop>
                                                            <div class="flex items-center justify-between px-3 py-2 border-b border-white/5 bg-white/5">
                                                                <h4 class="text-[10px] font-black text-white uppercase tracking-widest">Ubah Ikon</h4>
                                                                <button type="button" @click="showEditPicker = null" class="text-slate-500 hover:text-white transition">✖</button>
                                                            </div>
                                                            <!-- Group tabs -->
                                                    <div class="flex overflow-x-auto no-scrollbar border-b border-white/5 bg-black/20">
                                                        <button 
                                                            v-for="(group, idx) in emojiGroups" 
                                                            :key="idx"
                                                            type="button"
                                                            @click="activeGroup = idx"
                                                            class="px-2.5 py-2 text-sm flex-shrink-0 transition-all whitespace-nowrap"
                                                            :class="activeGroup === idx 
                                                                ? 'border-b-2 border-indigo-500 text-white bg-indigo-500/10' 
                                                                : 'text-slate-500 hover:text-slate-300 hover:bg-white/5 border-b-2 border-transparent'"
                                                        >
                                                            {{ group.label.split(' ')[0] }}
                                                        </button>
                                                    </div>
                                                    <div class="px-3 pt-2 pb-1">
                                                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ emojiGroups[activeGroup].label }}</div>
                                                    </div>
                                                    <div class="grid grid-cols-8 gap-0.5 p-2 max-h-44 overflow-y-auto">
                                                        <button 
                                                            v-for="emoji in emojiGroups[activeGroup].emojis" 
                                                            :key="emoji"
                                                            type="button"
                                                            @click="selectEmoji(emoji, 'edit')"
                                                            class="w-9 h-9 text-xl flex items-center justify-center rounded-xl hover:bg-indigo-500/20 transition-all hover:scale-125 active:scale-95"
                                                            :class="editForm.icon === emoji ? 'bg-indigo-500/30 ring-1 ring-indigo-500' : ''"
                                                            :title="emoji"
                                                        >{{ emoji }}</button>
                                                    </div>
                                                    <div class="px-3 pb-3 pt-1 border-t border-white/5 flex items-center gap-2">
                                                        <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Terpilih:</span>
                                                        <span class="text-xl">{{ editForm.icon }}</span>
                                                    </div>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </Teleport>
                                        </div>
                                        <div v-else class="text-2xl transform group-hover:scale-110 transition-transform duration-200">{{ cat.icon }}</div>
                                    </td>

                                    <td class="py-3 px-4">
                                        <div v-if="isEditing === cat.id">
                                            <TextInput type="text" v-model="editForm.name" class="w-full !bg-white/10 !border-none !rounded-lg !py-1.5 !px-3 !text-sm text-white shadow-inner" />
                                        </div>
                                        <div v-else>
                                            <div class="text-sm font-bold text-white uppercase tracking-tight group-hover:text-indigo-300 transition-colors">{{ cat.name }}</div>
                                            <div class="text-[10px] font-mono text-slate-600 mt-0.5 tracking-wider">/category/{{ cat.slug }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 rounded-full border border-indigo-500/20 text-[10px] font-bold uppercase tracking-wider">
                                            {{ cat.videos_count }} video
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <template v-if="isEditing === cat.id">
                                                <button @click="submitUpdate" class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition" title="Simpan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                </button>
                                                <button @click="cancelEdit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500 hover:text-white transition" title="Batalkan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button @click="startEdit(cat)" class="p-2 text-indigo-400 hover:bg-indigo-500 hover:text-white rounded-lg border border-indigo-500/20 transition-all active:scale-95" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.242 3.758a2.121 2.121 0 013.03 3.03L9 17 4 17 4 12l10.242-10.242z" /></svg>
                                                </button>
                                                <button @click="deleteCategory(cat.id)" class="p-2 text-red-500 hover:bg-red-500 hover:text-white rounded-lg border border-red-500/20 transition-all active:scale-95" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="text-3xl mb-2">📭</div>
                                        <div class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Belum Ada Kategori</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.picker-popup {
    filter: drop-shadow(0 25px 50px rgba(0, 0, 0, 0.7));
}

/* Smooth emoji hover scale */
.picker-popup button:hover {
    transform: scale(1.25);
}

/* Hide scrollbar but allow scrolling */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Custom scrollbar for emoji grid */
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 99px; }
</style>
