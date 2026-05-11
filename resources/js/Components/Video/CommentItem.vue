<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    comment: Object,
    depth: {
        type: Number,
        default: 0
    },
    videoId: Number,
    activeAccentColor: String
});

const page = usePage();
const isReplying = ref(false);
const replyForm = useForm({
    content: '',
    parent_id: props.comment.id
});

const submitReply = () => {
    replyForm.post(route('comments.store', props.videoId), {
        onSuccess: () => {
            replyForm.reset();
            isReplying.value = false;
        },
        preserveScroll: true
    });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric', year: 'numeric' });
};

const canDelete = (comment) => {
    const authUser = page.props.auth.user;
    if (!authUser) return false;
    return authUser.id === comment.user_id || authUser.is_admin;
};

const deleteComment = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
        useForm({}).delete(route('comments.destroy', id), {
            preserveScroll: true,
        });
    }
};

const emojis = ['🔥', '🎬', '😍', '👍', '😲', '💎', '👑'];
const addEmoji = (emoji) => {
    replyForm.content += emoji;
};
</script>

<template>
    <div :class="['group relative flex gap-4 sm:gap-6 p-4 sm:p-6 rounded-[32px] transition-all duration-500 border border-transparent hover:bg-white/40 hover:border-[rgb(var(--border-main))] hover:shadow-xl hover:shadow-indigo-500/5', depth > 0 ? 'mt-4 ml-6 sm:ml-12 border-l-2 border-indigo-500/10' : '']">
        <div class="flex-shrink-0">
            <div :class="[
                'w-10 h-10 sm:w-14 sm:h-14 rounded-[18px] sm:rounded-[24px] flex items-center justify-center font-black text-lg sm:text-xl shadow-xl relative transition-transform group-hover:scale-110',
                comment.user.active_subscription ? 'bg-gradient-to-br from-indigo-500 to-indigo-700 text-white ring-4 ring-indigo-500/10 shadow-indigo-500/30' : 'bg-[rgb(var(--bg-input))] text-[rgb(var(--text-muted))]'
            ]">
                {{ comment.user.name.charAt(0).toUpperCase() }}
                <div v-if="comment.user.active_subscription" class="absolute -top-1 -right-1 w-4 h-4 sm:w-6 sm:h-6 bg-amber-500 rounded-full flex items-center justify-center text-[8px] sm:text-[11px] border-2 sm:border-4 border-[rgb(var(--bg-main))] shadow-lg">👑</div>
            </div>
        </div>
        <div class="flex-grow pt-0.5">
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span class="text-xs sm:text-sm font-black text-[rgb(var(--text-main))] tracking-tight uppercase">{{ comment.user.name }}</span>
                <span v-if="comment.user.active_subscription" class="bg-indigo-500 text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">PREMIUM</span>
                <span class="text-[9px] text-[rgb(var(--text-muted))] font-bold uppercase tracking-widest ml-auto">{{ formatDate(comment.created_at) }}</span>
            </div>
            <p class="text-[rgb(var(--text-muted))] text-sm leading-relaxed font-medium mb-4">
                {{ comment.content }}
            </p>
            
            <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-all">
                <button 
                    v-if="$page.props.auth.user"
                    @click="isReplying = !isReplying" 
                    class="text-[9px] text-indigo-500 hover:text-indigo-600 font-black uppercase tracking-[0.2em] transition-colors bg-indigo-500/5 px-4 py-1.5 rounded-full border border-indigo-500/10"
                >
                    {{ isReplying ? 'Batal' : 'Balas' }}
                </button>
                <button 
                    v-if="canDelete(comment)" 
                    @click="deleteComment(comment.id)" 
                    class="text-[9px] text-red-500/60 hover:text-red-500 font-black uppercase tracking-[0.2em] transition-colors bg-red-500/5 px-4 py-1.5 rounded-full border border-red-500/10"
                >
                    Hapus
                </button>
            </div>

            <!-- Reply Form -->
            <div v-if="isReplying" class="mt-6 animate-in slide-in-from-top-2 duration-300">
                <form @submit.prevent="submitReply" class="space-y-3">
                    <div class="relative">
                        <textarea 
                            v-model="replyForm.content"
                            placeholder="Tulis balasan..."
                            class="w-full bg-[rgb(var(--bg-input))] border-none rounded-2xl p-4 text-sm text-[rgb(var(--text-main))] placeholder:text-[rgb(var(--text-muted))]/40 focus:ring-2 focus:ring-indigo-500/20 transition min-h-[80px] resize-none shadow-inner"
                        ></textarea>
                        
                        <!-- Emoji Picker Mini -->
                        <div class="flex gap-2 mt-2 px-2">
                            <button 
                                v-for="emoji in emojis" 
                                :key="emoji"
                                type="button"
                                @click="addEmoji(emoji)"
                                class="hover:scale-125 transition-transform text-lg"
                            >
                                {{ emoji }}
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button 
                            type="submit" 
                            :disabled="replyForm.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50"
                        >
                            Kirim Balasan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recursive Replies -->
            <div v-if="comment.replies && comment.replies.length > 0" class="mt-4">
                <CommentItem 
                    v-for="reply in comment.replies" 
                    :key="reply.id" 
                    :comment="reply" 
                    :depth="depth + 1"
                    :videoId="videoId"
                    :activeAccentColor="activeAccentColor"
                />
            </div>
        </div>
    </div>
</template>
