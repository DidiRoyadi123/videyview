<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
            toastSuccess('Berhasil menyimpan video ke database.');
        },
        onError: () => {
            toastError('Gagal menyimpan video. Periksa log sistem.');
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
    console.log("%c[Videy AutoCrawler] Memulai (" + (isX ? 'X/Twitter Mode' : 'Turbo Mode v2.1') + ")...", "color: #8C57FF; font-weight: bold; font-size: 14px;");
    
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
             div.style = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); backdrop-filter:blur(10px); color:#3A3541; padding:40px; z-index:100000; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family: sans-serif;';
             div.innerHTML = '<div style="background:white; padding:30px; border-radius:24px; border:1px solid #E0E0E0; width:100%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.1);">' +
                                '<h3 style="margin-bottom:10px; color:#8C57FF; font-weight:900; text-transform:uppercase; letter-spacing:0.05em;">Crawl Selesai! (' + foundLinks.size + ' Link)</h3>' +
                                '<p style="font-size:12px; margin-bottom:20px; color:#898591;">' + msg + '</p>' +
                                '<textarea id="crawl-textarea" style="width:100%; height:200px; background:#F4F5FA; color:#3A3541; border:1px solid #E0E0E0; border-radius:12px; padding:15px; margin-bottom:20px; font-size:12px; font-family:monospace; outline:none; resize:none;">' + result + '</textarea>' +
                                '<div style="display:flex; gap:10px;">' +
                                    '<button id="copyManualBtn" style="flex:1; background:#8C57FF; color:white; border:none; padding:12px; border-radius:12px; cursor:pointer; font-weight:bold; text-transform:uppercase; font-size:12px;">Salin Ke Clipboard</button>' +
                                    '<button id="closeFinalCrawl" style="background:#F4F5FA; color:#898591; border:none; padding:12px 20px; border-radius:12px; cursor:pointer; font-weight:bold; text-transform:uppercase; font-size:12px;">Tutup</button>' +
                                '</div>' +
                              '</div>';
             document.body.appendChild(div);
             div.querySelector('#closeFinalCrawl').onclick = () => div.remove();
             div.querySelector('#copyManualBtn').onclick = () => {
                const ta = div.querySelector('#crawl-textarea');
                ta.select();
                document.execCommand('copy');
                div.querySelector('#copyManualBtn').innerText = 'Tersalin!';
                setTimeout(() => div.querySelector('#copyManualBtn').innerText = 'Salin Ke Clipboard', 2000);
             };
        };

        try { 
            await navigator.clipboard.writeText(result); 
            showResultUI("Tautan berhasil disalin ke papan klip secara otomatis.");
        } catch(e) {
            console.error("Auto-copy failed:", e);
            showResultUI("Salin otomatis gagal karena fokus hilang. Silakan salin manual di bawah ini:");
        }
    } else {
        alert("Tidak ditemukan link Videy.");
    }
})();
    `.trim();

    navigator.clipboard.writeText(script).then(() => {
        scriptCopied.value = true;
        toastInfo('Skrip auto-crawl disalin ke papan klip!');
        setTimeout(() => scriptCopied.value = false, 2000);
    }).catch(() => {
        toastError('Gagal menyalin skrip. Silakan coba manual.');
    });
};
</script>

<template>
    <Head title="Link Extractor - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Link Extractor</h2>
                    <p class="text-sm text-gray-500 mt-1">Alat penemuan konten massal dan ekstraksi tautan Videy.</p>
                </div>
                <div class="bg-[#8C57FF]/5 px-4 py-2 rounded-xl border border-[#8C57FF]/10">
                    <span class="text-xs font-bold uppercase text-[#8C57FF] tracking-widest leading-none">Bulk Utility Tool</span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Input Section -->
                <div class="materio-card p-6 relative overflow-hidden group">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#8C57FF]/5 rounded-full blur-[60px] pointer-events-none"></div>
                    <h3 class="text-lg font-bold text-[#3A3541] mb-6 flex items-center gap-3">
                        <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                        Konten Mentah (Raw Text)
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Crawler Helper -->
                        <div class="p-5 bg-[#8C57FF]/5 rounded-2xl border border-[#8C57FF]/10 space-y-4">
                            <div class="flex items-center justify-between flex-wrap gap-4">
                                <h4 class="text-[11px] font-bold text-[#8C57FF] uppercase tracking-widest flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Skrip Auto-Crawl
                                </h4>
                                <div class="flex items-center gap-3 bg-white px-3 py-1 rounded-full border border-gray-100">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Limit</span>
                                    <input type="number" v-model="crawlLimit" class="w-10 !bg-transparent !border-none !text-[#3A3541] text-xs font-bold px-0 py-0 focus:ring-0 text-center" />
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed italic">
                                Jalankan skrip ini di Console (F12) browser pada target situs untuk mengumpulkan link Videy secara otomatis.
                            </p>
                            <button @click="copyCrawlScript" class="w-full py-2.5 bg-[#8C57FF] hover:bg-[#7B47E6] text-white text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all shadow-md shadow-[#8C57FF]/20">
                                {{ scriptCopied ? 'BERHASIL DISALIN!' : 'SALIN SKRIP CRAWL' }}
                            </button>
                        </div>

                        <!-- Target Sources -->
                        <div class="flex flex-wrap gap-2">
                            <a v-for="mirror in mirrors" :key="mirror.name" :href="mirror.url" target="_blank" class="px-3 py-1.5 bg-gray-50 hover:bg-[#8C57FF]/5 border border-gray-100 rounded-lg text-[10px] font-bold text-gray-500 hover:text-[#8C57FF] transition-all flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                {{ mirror.name }}
                            </a>
                        </div>

                        <div class="space-y-2">
                            <InputLabel value="Atau tempel teks manual di bawah ini:" class="!text-gray-400 !text-[11px] !font-bold !uppercase !tracking-widest italic ml-1" />
                            <textarea 
                                v-model="rawText"
                                class="w-full h-44 bg-white border-gray-200 rounded-2xl p-4 text-[#3A3541] font-mono text-sm focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all outline-none resize-none"
                                placeholder="Tempel teks di sini..."
                            ></textarea>
                        </div>
                        
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-6">
                                <label class="flex items-center group cursor-pointer">
                                    <Checkbox v-model:checked="isPremium" />
                                    <span class="ms-2 text-[11px] font-bold text-gray-500 group-hover:text-[#3A3541] transition uppercase tracking-widest">Premium Content</span>
                                </label>
                                
                                <label class="flex items-center group cursor-pointer">
                                    <Checkbox v-model:checked="autoSave" />
                                    <span class="ms-2 text-[11px] font-bold uppercase tracking-widest text-green-600/80 group-hover:text-green-600 transition italic flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Auto Save
                                    </span>
                                </label>
                            </div>
                            <button @click="handleExtract" class="w-full py-3.5 bg-[#3A3541] hover:bg-[#2A2531] text-white font-bold rounded-xl transition shadow-lg text-xs uppercase tracking-widest">
                                EKSTRAKSI & PROSES SEKARANG
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="materio-card p-6 relative overflow-hidden flex flex-col">
                    <h3 class="text-lg font-bold text-[#3A3541] mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-1 h-6 bg-green-500 rounded-full"></span>
                            Tautan Terekstraksi
                        </div>
                        <span v-if="extractedUrls.length" class="text-[10px] font-bold text-[#8C57FF] uppercase tracking-widest bg-[#8C57FF]/5 px-3 py-1 rounded-full border border-[#8C57FF]/10">
                            {{ extractedUrls.length }} Link Ditemukan
                        </span>
                    </h3>

                    <div v-if="extractedUrls.length === 0" class="flex-1 flex flex-col items-center justify-center py-20">
                        <div class="text-4xl mb-4">🔍</div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Belum ada tautan terekstraksi</p>
                    </div>
                    
                    <div v-else class="flex-1 flex flex-col h-full">
                        <div class="flex-1 overflow-y-auto max-h-[25rem] space-y-2 pr-2 no-scrollbar">
                            <div v-for="(url, index) in extractedUrls" :key="index" class="bg-gray-50 border border-gray-100 p-3.5 rounded-xl flex items-center justify-between group/item hover:bg-white hover:border-[#8C57FF]/20 hover:shadow-sm transition-all duration-300">
                                <div class="min-w-0 pr-4">
                                    <div class="text-[10px] text-[#8C57FF] font-bold uppercase tracking-widest mb-0.5 italic opacity-60">#{{ index + 1 }}</div>
                                    <div class="text-xs text-[#3A3541] font-mono truncate">{{ url }}</div>
                                </div>
                                <button @click="removeUrl(index)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <button 
                                @click="submitBulk" 
                                class="w-full py-4 bg-[#8C57FF] hover:bg-[#7B47E6] text-white font-bold rounded-xl transition shadow-lg shadow-[#8C57FF]/25 text-xs uppercase tracking-widest disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'MENYIMPAN DATA...' : 'SIMPAN SEMUA KE DATABASE' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
