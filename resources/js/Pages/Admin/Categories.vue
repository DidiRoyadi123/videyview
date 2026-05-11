<script setup>
import { useForm, router, Head } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
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

const showPicker = ref(false);
const showEditPicker = ref(null);

const emojiGroups = [
    { label: '🎬 Film & Video', emojis: ['🎬','🎥','📽️','🎞️','📹','🎦','🎭','🎪','🎨','🖥️','📺','🎙️','🎤','🎧','📻'] },
    { label: '🌍 Genre & Tema', emojis: ['🌍','🌏','🌎','🏆','⚔️','🔮','💫','🌌','🏔️','🌊','🌋','🏜️','🌿','🦁','🐉'] },
    { label: '😂 Mood & Suasana', emojis: ['😂','😱','😍','🤩','😭','🤔','💪','🔥','❤️','💀','👻','✨','🌟','⚡','🌈'] },
    { label: '🎵 Hiburan', emojis: ['🎵','🎶','🎸','🎹','🎺','🎻','🥁','🎮','🕹️','🎲','🃏','♟️','🎯','🎳','🎰'] },
    { label: '📚 Edukasi', emojis: ['📚','📖','📝','✏️','🔬','🔭','💡','🧪','🧬','🏫','🎓','📊','📈','🗺️','🧭'] },
    { label: '🏠 Gaya Hidup', emojis: ['🏠','🏙️','🌆','🍕','🍜','🍱','☕','🧘','🏋️','🚀','✈️','🚂','🛒','💄','👗'] },
    { label: '⭐ Khusus', emojis: ['📁','📂','🗂️','💾','📌','📍','🔖','🏷️','🎀','🎁','🏅','🥇','👑','💎','🆕'] },
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

const handleOutsideClick = (e) => {
    if (!e.target.closest('.emoji-picker-container')) {
        showPicker.value = false;
        showEditPicker.value = null;
    }
};

onMounted(() => document.addEventListener('click', handleOutsideClick));
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));

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
    <Head title="Manajemen Kategori - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Manajemen Kategori</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola taksonomi dan pengelompokan konten.</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Create Category Card -->
            <div class="materio-card p-6 relative overflow-hidden">
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-[#8C57FF]/10 rounded-full blur-[60px] pointer-events-none"></div>
                <h3 class="text-lg font-bold text-[#3A3541] mb-6 flex items-center gap-3">
                    <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                    Tambah Kategori Baru
                </h3>
                
                <form @submit.prevent="submitStore" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-5 items-end">
                    <div class="lg:col-span-4 space-y-1.5">
                        <InputLabel for="name" value="Nama Kategori" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="name" type="text" v-model="form.name" required class="block w-full border-gray-200" placeholder="Contoh: Petualangan" />
                        <InputError :message="form.errors.name" class="mt-1 text-xs" />
                    </div>
                    
                    <div class="lg:col-span-3 space-y-1.5 emoji-picker-container relative">
                        <InputLabel value="Ikon Kategori" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <div class="flex items-center gap-2">
                            <button 
                                type="button"
                                @click.stop="togglePicker('create')"
                                class="w-12 h-10 flex items-center justify-center text-2xl bg-gray-50 rounded-lg border border-gray-100 hover:border-[#8C57FF]/40 transition-all shadow-sm shrink-0"
                            >
                                {{ form.icon || '📁' }}
                            </button>
                            <TextInput 
                                type="text" 
                                v-model="form.icon" 
                                class="flex-1 !text-lg text-center border-gray-200" 
                                placeholder="📁" 
                                maxlength="4"
                            />
                        </div>

                        <!-- Emoji Picker Popup -->
                        <Teleport to="body">
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div v-if="showPicker" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPicker = false"></div>
                                    <div class="relative bg-white border border-gray-100 rounded-2xl shadow-2xl overflow-hidden w-full max-w-[320px]" @click.stop>
                                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 bg-gray-50/50">
                                            <h4 class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Pilih Ikon</h4>
                                            <button type="button" @click="showPicker = false" class="text-gray-400 hover:text-[#3A3541]">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <div class="flex overflow-x-auto no-scrollbar border-b border-gray-50 bg-white">
                                            <button 
                                                v-for="(group, idx) in emojiGroups" 
                                                :key="idx"
                                                type="button"
                                                @click="activeGroup = idx"
                                                class="px-4 py-3 text-sm shrink-0 transition-all whitespace-nowrap"
                                                :class="activeGroup === idx 
                                                    ? 'border-b-2 border-[#8C57FF] text-[#8C57FF] bg-[#8C57FF]/5' 
                                                    : 'text-gray-400 hover:text-gray-600 border-b-2 border-transparent'"
                                            >
                                                {{ group.label.split(' ')[0] }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-7 gap-1 p-3 max-h-52 overflow-y-auto no-scrollbar">
                                            <button 
                                                v-for="emoji in emojiGroups[activeGroup].emojis" 
                                                :key="emoji"
                                                type="button"
                                                @click="selectEmoji(emoji, 'create')"
                                                class="w-10 h-10 text-xl flex items-center justify-center rounded-lg hover:bg-[#8C57FF]/10 transition-all hover:scale-110"
                                            >{{ emoji }}</button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </Teleport>
                    </div>

                    <div class="lg:col-span-2 space-y-1.5">
                        <InputLabel for="order" value="Urutan Tampil" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="order" type="number" v-model="form.order" class="block w-full border-gray-200" />
                    </div>

                    <div class="lg:col-span-3">
                        <button class="w-full bg-[#8C57FF] text-white font-bold py-2.5 rounded-lg hover:bg-[#7B47E6] transition shadow-md shadow-[#8C57FF]/20 uppercase text-xs tracking-widest disabled:opacity-50" :disabled="form.processing">
                            {{ form.processing ? 'Memproses...' : 'Tambahkan Kategori' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories List Card -->
            <div class="materio-card overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-bold text-[#3A3541]">Daftar Kategori</h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50 px-3 py-1 rounded-full border border-gray-100">{{ categories.length }} Aktif</span>
                </div>
                
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-bold uppercase text-gray-400 tracking-wider border-b border-gray-100 bg-gray-50/30">
                                <th class="py-4 px-6 w-20">Order</th>
                                <th class="py-4 px-4 w-24 text-center">Ikon</th>
                                <th class="py-4 px-4">Nama Kategori</th>
                                <th class="py-4 px-4 text-center">Video Count</th>
                                <th class="py-4 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="cat in categories" :key="cat.id" class="group hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-6">
                                    <div v-if="isEditing === cat.id">
                                        <TextInput type="number" v-model="editForm.order" class="w-16 shadow-sm" />
                                    </div>
                                    <span v-else class="text-sm font-bold text-gray-500">{{ cat.order }}</span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <div v-if="isEditing === cat.id" class="flex justify-center">
                                        <button 
                                            type="button"
                                            @click.stop="togglePicker('edit', cat.id)"
                                            class="text-2xl w-10 h-10 flex items-center justify-center rounded-lg bg-gray-50 hover:bg-[#8C57FF]/10 transition-all border border-transparent hover:border-[#8C57FF]/30"
                                        >{{ editForm.icon || '📁' }}</button>
                                        
                                        <!-- Inline Edit Picker -->
                                        <Teleport to="body">
                                            <Transition
                                                enter-active-class="transition duration-200 ease-out"
                                                enter-from-class="opacity-0 scale-95"
                                                enter-to-class="opacity-100 scale-100"
                                                leave-active-class="transition duration-150 ease-in"
                                                leave-from-class="opacity-100 scale-100"
                                                leave-to-class="opacity-0 scale-95"
                                            >
                                                <div v-if="showEditPicker === cat.id" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                                                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditPicker = null"></div>
                                                    <div class="relative bg-white border border-gray-100 rounded-2xl shadow-2xl overflow-hidden w-full max-w-[320px]" @click.stop>
                                                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 bg-gray-50/50">
                                                            <h4 class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Ubah Ikon</h4>
                                                            <button type="button" @click="showEditPicker = null" class="text-gray-400 hover:text-[#3A3541]">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                        <div class="flex overflow-x-auto no-scrollbar border-b border-gray-50 bg-white">
                                                            <button 
                                                                v-for="(group, idx) in emojiGroups" 
                                                                :key="idx"
                                                                type="button"
                                                                @click="activeGroup = idx"
                                                                class="px-4 py-3 text-sm shrink-0 transition-all whitespace-nowrap"
                                                                :class="activeGroup === idx 
                                                                    ? 'border-b-2 border-[#8C57FF] text-[#8C57FF] bg-[#8C57FF]/5' 
                                                                    : 'text-gray-400 hover:text-gray-600 border-b-2 border-transparent'"
                                                            >
                                                                {{ group.label.split(' ')[0] }}
                                                            </button>
                                                        </div>
                                                        <div class="grid grid-cols-7 gap-1 p-3 max-h-52 overflow-y-auto no-scrollbar">
                                                            <button 
                                                                v-for="emoji in emojiGroups[activeGroup].emojis" 
                                                                :key="emoji"
                                                                type="button"
                                                                @click="selectEmoji(emoji, 'edit')"
                                                                class="w-10 h-10 text-xl flex items-center justify-center rounded-lg hover:bg-[#8C57FF]/10 transition-all hover:scale-110"
                                                            >{{ emoji }}</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </div>
                                    <div v-else class="text-2xl transition-transform group-hover:scale-125 duration-300">{{ cat.icon }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    <div v-if="isEditing === cat.id">
                                        <TextInput type="text" v-model="editForm.name" class="w-full shadow-sm" />
                                    </div>
                                    <div v-else>
                                        <div class="text-sm font-bold text-[#3A3541] group-hover:text-[#8C57FF] transition-colors uppercase tracking-tight">{{ cat.name }}</div>
                                        <div class="text-[10px] text-gray-400 mt-0.5 tracking-wider font-mono">/category/{{ cat.slug }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-3 py-1 bg-[#8C57FF]/5 text-[#8C57FF] rounded-lg border border-[#8C57FF]/10 text-[10px] font-bold uppercase">
                                        {{ cat.videos_count }} video
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <template v-if="isEditing === cat.id">
                                            <button @click="submitUpdate" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-600 hover:text-white transition shadow-sm" title="Simpan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button @click="cancelEdit" class="p-2 bg-red-50 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition shadow-sm" title="Batal">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="startEdit(cat)" class="p-2 text-gray-400 hover:text-[#8C57FF] hover:bg-[#8C57FF]/10 rounded-lg transition-all" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button @click="deleteCategory(cat.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
