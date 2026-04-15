<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useToast } from '@/Composables/useToast';
import { ref } from 'vue';

const { success: toastSuccess, error: toastError, info: toastInfo } = useToast();

const rawText = ref('');
const extractedUrls = ref([]);
const isPremium = ref(true);
const crawlLimit = ref(50);
const scriptCopied = ref(false);
const autoSave = ref(true);

const mirrors = [
    { name: 'Lightbrd', url: 'https://lightbrd.com/search?q=videy.co' },
    { name: 'XCancel', url: 'https://xcancel.com/search?q=videy.co' },
    { name: 'Poast', url: 'https://nitter.poast.org/search?q=videy.co' },
    { name: 'Twillot', url: 'https://twillot.com/search?q=videy.co' },
    { name: 'ArchivlyX', url: 'https://archivlyx.com/search?q=videy.co' },
    { name: 'Twiiit', url: 'https://twiiit.com/search?q=videy.co' },
];

const form = useForm({
    urls: [],
    is_premium: true,
});

const cleanUrl = (text) => {
    text = text.trim();
    if (!text) return null;
    if (!text.startsWith('http')) text = 'https://' + text;

    try {
        const urlObj = new URL(text);
        if (!urlObj.hostname.endsWith('videy.co')) return null;

        let id = null;
        if (urlObj.searchParams.has('id')) {
            id = urlObj.searchParams.get('id');
        } else {
            const parts = urlObj.pathname.split('/');
            const lastPart = parts.pop();
            id = lastPart.replace('.mp4', '');
            if (id === 'v' && parts.length > 0) {
                id = parts.pop();
            }
        }

        if (id && /^[a-zA-Z0-9]+$/.test(id)) {
            return `https://cdn.videy.co/${id}.mp4`;
        }
        return null;
    } catch (e) { return null; }
};

const handleExtract = () => {
    const regex = /(?:https?:\/\/)?(?:[a-z0-9-]+\.)?videy\.co[^\s"']+/g;
    const matches = rawText.value.match(regex) || [];
    const uniqueCleaned = [];

    matches.forEach(m => {
        const cleaned = cleanUrl(m);
        if (cleaned && !uniqueCleaned.includes(cleaned)) {
            uniqueCleaned.push(cleaned);
        }
    });

    extractedUrls.value = uniqueCleaned;

    if (autoSave.value && uniqueCleaned.length > 0) {
        setTimeout(() => submitBulk(), 100);
    }
};

const submitBulk = () => {
    if (extractedUrls.value.length === 0) return;
    
    form.urls = extractedUrls.value;
    form.is_premium = isPremium.value;
    
    form.post(route('admin.videos.bulk-store'), {
        onSuccess: () => {
            rawText.value = '';
            extractedUrls.value = [];
            toastSuccess('Successfully saved videos to database.');
        },
        onError: () => {
            toastError('Failed to save videos. Please check logs.');
        }
    });
};

const removeUrl = (index) => {
    extractedUrls.value.splice(index, 1);
};

const copyCrawlScript = () => {
    const limit = crawlLimit.value || 50;
    const script = `
(async function() {
    const isX = window.location.hostname.includes('x.com') || window.location.hostname.includes('twitter.com');
    console.log("%c[Videy AutoCrawler] Memulai (" + (isX ? 'X/Twitter Mode' : 'Turbo Mode v2.1') + ")...", "color: #3b82f6; font-weight: bold; font-size: 14px;");
    
    const targetLimit = ${limit};
    let foundLinks = new Set();

    function extract(htmlOrDoc) {
        const regex = /(?:https?:\\/\\/)?(?:[a-z0-9-]+\\.)?videy\\.co[^\\s"\\'\\\\\\\\[\\]()<>]+/g;
        const src = typeof htmlOrDoc === 'string' ? htmlOrDoc : (htmlOrDoc.body.innerText + htmlOrDoc.body.innerHTML);
        const matches = src.match(regex) || [];
        matches.forEach(m => {
            if (foundLinks.size < targetLimit) {
                let clean = m.trim().replace(/[.,!?;:>]$/, '');
                if (clean.includes('videy.co')) foundLinks.add(clean);
            }
        });
        console.log("[Status] Unik: " + foundLinks.size + " / " + targetLimit);
    }

    if (isX) {
        let lastCount = 0;
        let stagnate = 0;
        while (foundLinks.size < targetLimit && stagnate < 10) {
            extract(document);
            window.scrollBy(0, 1000);
            await new Promise(r => setTimeout(r, 2000));
            if (foundLinks.size === lastCount) stagnate++;
            else stagnate = 0;
            lastCount = foundLinks.size;
        }
    } else {
        let currentHtml = document.documentElement.innerHTML;
        let nextUrl = null;

        const getNextUrl = (html) => {
            const doc = new DOMParser().parseFromString(html, "text/html");
            const nextBtn = Array.from(doc.querySelectorAll('a, button, div.show-more a')).find(el => {
                const txt = (el.textContent || el.innerText || "").toLowerCase();
                return txt.includes('load more') || txt.includes('next') || txt.includes('berikutnya') || 
                       (el.getAttribute('href') && el.getAttribute('href').includes('cursor='));
            });
            return nextBtn ? new URL(nextBtn.getAttribute('href'), window.location.href).href : null;
        };

        extract(currentHtml);
        nextUrl = getNextUrl(currentHtml);

        while (foundLinks.size < targetLimit && nextUrl) {
            console.log("Mengambil halaman berikutnya: " + nextUrl);
            try {
                const response = await fetch(nextUrl);
                const html = await response.text();
                extract(html);
                nextUrl = getNextUrl(html);
                await new Promise(r => setTimeout(r, 1500));
            } catch (e) {
                console.error("Gagal mengambil halaman:", e);
                break;
            }
        }
    }

    const result = Array.from(foundLinks).join('\\n');
    if (result) {
        const showResultUI = (msg) => {
             const div = document.createElement('div');
             div.id = 'crawl-result-overlay';
             div.style = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,6,23,0.9); backdrop-filter:blur(10px); color:white; padding:40px; z-index:100000; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family: sans-serif;';
             div.innerHTML = '<div style="background:#1e293b; padding:30px; border-radius:30px; border:1px solid #334155; width:100%; max-width:500px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">' +
                                '<h3 style="margin-bottom:10px; color:#60a5fa; font-weight:900; text-transform:uppercase; letter-spacing:-0.05em; font-style:italic;">Crawl Selesai! (' + foundLinks.size + ' Link)</h3>' +
                                '<p style="font-size:12px; margin-bottom:20px; color:#94a3b8;">' + msg + '</p>' +
                                '<textarea id="crawl-textarea" style="width:100%; height:200px; background:#020617; color:#60a5fa; border:1px solid #334155; border-radius:15px; padding:15px; margin-bottom:20px; font-size:12px; font-family:monospace; outline:none; resize:none;">' + result + '</textarea>' +
                                '<div style="display:flex; gap:10px;">' +
                                    '<button id="copyManualBtn" style="flex:1; background:#3b82f6; color:white; border:none; padding:12px; border-radius:12px; cursor:pointer; font-weight:bold; text-transform:uppercase; font-size:12px;">Copy to Clipboard</button>' +
                                    '<button id="closeFinalCrawl" style="background:rgba(255,255,255,0.05); color:#94a3b8; border:none; padding:12px 20px; border-radius:12px; cursor:pointer; font-weight:bold; text-transform:uppercase; font-size:12px;">Close</button>' +
                                '</div>' +
                              '</div>';
             document.body.appendChild(div);
             div.querySelector('#closeFinalCrawl').onclick = () => div.remove();
             div.querySelector('#copyManualBtn').onclick = () => {
                const ta = div.querySelector('#crawl-textarea');
                ta.select();
                document.execCommand('copy');
                div.querySelector('#copyManualBtn').innerText = 'Copied!';
                setTimeout(() => div.querySelector('#copyManualBtn').innerText = 'Copy to Clipboard', 2000);
             };
        };

        try { 
            await navigator.clipboard.writeText(result); 
            showResultUI("Links successfully copied to clipboard.");
        } catch(e) {
            console.error("Auto-copy failed:", e);
            showResultUI("Auto-copy failed because focus was lost. Please copy manually below:");
        }
    } else {
        alert("Tidak ditemukan link Videy.");
    }
})();
    `.trim();

    navigator.clipboard.writeText(script).then(() => {
        scriptCopied.value = true;
        toastInfo('Auto-crawl script copied to clipboard!');
        setTimeout(() => scriptCopied.value = false, 2000);
    }).catch(() => {
        toastError('Failed to copy script. Please try manually.');
    });
};
</script>

<template>
    <Head title="Admin - Link Extractor" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Link <span class="text-indigo-500">Extractor</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Alat Penemuan Konten Massal</p>
                </div>
                <div class="bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20">
                    <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest leading-none">Bulk Tool</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Input Section -->
                    <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden group">
                        <div class="absolute -top-12 -right-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-[60px]"></div>
                        <h3 class="text-base font-black text-white mb-6 flex items-center gap-3 uppercase tracking-tight">
                            <span class="w-1 h-5 bg-indigo-600 rounded-full"></span>
                            Konten Mentah (Raw)
                        </h3>
                        
                        <div class="space-y-6">
                            <!-- Crawler Helper -->
                            <div class="p-5 bg-indigo-500/10 rounded-2xl border border-indigo-500/20 space-y-4">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Script Auto-Crawl
                                    </h4>
                                    <div class="flex items-center gap-3 bg-black/40 px-3 py-1 rounded-full border border-white/5">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Limit</span>
                                        <input type="number" v-model="crawlLimit" class="w-12 !bg-transparent !border-none !text-white text-xs font-black px-0 py-0 focus:ring-0 text-center" />
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed font-bold italic">
                                    Copy - Paste script ini ke Console (F12) untuk mengumpulkan link otomatis.
                                </p>
                                <button @click="copyCrawlScript" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all active:scale-95">
                                    {{ scriptCopied ? 'BERHASIL DISALIN!' : 'SALIN SKRIP CRAWL' }}
                                </button>
                            </div>

                            <!-- Mirror Links -->
                            <div class="flex flex-wrap gap-2">
                                <a v-for="mirror in mirrors" :key="mirror.name" :href="mirror.url" target="_blank" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg text-[10px] font-bold text-slate-400 transition-all flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    {{ mirror.name }}
                                </a>
                            </div>

                            <div class="space-y-2">
                                <p class="text-[10px] font-bold uppercase text-slate-500 tracking-widest ml-1 italic">
                                    Atau tempel teks manual:
                                </p>
                                <textarea 
                                    v-model="rawText"
                                    class="w-full h-40 bg-black/40 border-none rounded-2xl p-4 text-indigo-300 font-mono text-sm focus:ring-1 focus:ring-indigo-500/30 transition-all outline-none shadow-inner"
                                    placeholder="Paste raw text here..."
                                ></textarea>
                            </div>
                            
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-6">
                                    <label class="flex items-center group cursor-pointer">
                                        <Checkbox v-model:checked="isPremium" class="w-5 h-5 !bg-white/5 !border-none !text-indigo-600 rounded-lg" />
                                        <span class="ms-2 text-[10px] font-bold text-slate-400 group-hover:text-white transition uppercase tracking-widest">Premium Content</span>
                                    </label>
                                    
                                    <label class="flex items-center group cursor-pointer">
                                        <Checkbox v-model:checked="autoSave" class="w-5 h-5 !bg-emerald-500/10 !border-none !text-emerald-500 rounded-lg" />
                                        <span class="ms-2 text-[10px] font-bold uppercase tracking-widest text-emerald-500/80 group-hover:text-emerald-400 transition flex items-center gap-2 italic">
                                            Auto Save
                                        </span>
                                    </label>
                                </div>
                                <PrimaryButton @click="handleExtract" class="!w-full !py-3 !text-xs !rounded-xl !tracking-widest">EKSTRAKSI & PROSES</PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden flex flex-col shadow-xl">
                        <h3 class="text-base font-black text-white mb-6 flex items-center justify-between uppercase tracking-tight">
                            <div class="flex items-center gap-3">
                                <span class="w-1 h-5 bg-purple-500 rounded-full"></span>
                                Tautan Terekstraksi
                            </div>
                            <span v-if="extractedUrls.length" class="text-[9px] font-black text-indigo-400 uppercase tracking-widest bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                                {{ extractedUrls.length }} FOUND
                            </span>
                        </h3>

                        <div v-if="extractedUrls.length === 0" class="flex-1 flex flex-col items-center justify-center opacity-30 py-12">
                            <div class="text-4xl mb-3">🔍</div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">No links extracted</p>
                        </div>
                        
                        <div v-else class="flex-1 flex flex-col h-full">
                            <div class="flex-1 overflow-y-auto max-h-[22rem] space-y-2 pr-2 custom-scrollbar">
                                <div v-for="(url, index) in extractedUrls" :key="index" class="bg-white/5 border border-white/5 p-3 rounded-xl flex items-center justify-between group/item hover:bg-white/[0.08] transition-all duration-200">
                                    <div class="overflow-hidden">
                                        <div class="text-[9px] text-indigo-400 font-bold uppercase tracking-widest mb-1 italic">#{{ index + 1 }}</div>
                                        <div class="text-xs text-slate-300 truncate w-64 sm:w-80 font-mono group-hover:text-white transition-colors">{{ url }}</div>
                                    </div>
                                    <button @click="removeUrl(index)" class="p-2 text-slate-600 hover:text-red-500 transition-all active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-white/5">
                                <PrimaryButton 
                                    @click="submitBulk" 
                                    class="!w-full !py-3 !font-black !uppercase !tracking-widest !text-[10px] italic shadow-lg shadow-indigo-600/20"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'MENYIMPAN...' : 'SIMPAN SEMUA KE DATABASE' }}
                                </PrimaryButton>
                            </div>
                        </div>
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
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 99px;
}
</style>
