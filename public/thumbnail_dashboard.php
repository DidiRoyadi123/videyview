<?php
// Securely get ENV values
function getEnvValue($key, $default = null) {
    if (!file_exists('../.env')) return $default;
    $lines = file('../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = @explode('=', $line, 2);
        if (trim($name) == $key) {
            return trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    return $default;
}

// Database Connection
$dbName = getEnvValue('DB_DATABASE', 'videyview');
$dbUser = getEnvValue('DB_USERNAME', 'root');
$dbPass = getEnvValue('DB_PASSWORD', '');
$dbHost = getEnvValue('DB_HOST', '127.0.0.1');

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// AJAX Handler
if (isset($_GET['action']) && $_GET['action'] === 'sync') {
    header('Content-Type: application/json');
    $id = $_GET['id'];
    $ffmpeg = $_GET['ffmpeg'];
    
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) die(json_encode(['success' => false, 'error' => 'Video not found']));

    $proxyUrl = "http://" . $_SERVER['HTTP_HOST'] . "/video-proxy?url=" . urlencode($video['url']);
    $filename = "thumb_view_" . $id . "_" . time() . ".jpg";
    $outputPath = __DIR__ . '/storage/thumbnails/' . $filename;
    
    if (!is_dir(dirname($outputPath))) mkdir(dirname($outputPath), 0777, true);

    // FFmpeg reads from our local Laravel proxy endpoint, skipping all ISP headaches
    $cmd = "\"$ffmpeg\" -an -sn -threads 1 -ss 00:00:01 -i \"$proxyUrl\" -t 1 -vframes 1 -q:v 2 \"$outputPath\" -y 2>&1";
    
    exec($cmd, $output, $resCode);
    
    $logOutput = implode("\n", array_slice($output, -10)); // Get the last 10 lines of FFmpeg output
    
    if ($resCode === 0 && file_exists($outputPath)) {
        $publicUrl = '/storage/thumbnails/' . $filename;
        $upd = $pdo->prepare("UPDATE videos SET thumbnail_url = ? WHERE id = ?");
        $upd->execute([$publicUrl, $id]);
        die(json_encode(['success' => true, 'url' => $publicUrl, 'log' => $logOutput]));
    }
    
    die(json_encode(['success' => false, 'error' => 'FFmpeg Error', 'log' => $logOutput]));
}

// Count remaining
$pending = $pdo->query("SELECT COUNT(*) FROM videos WHERE thumbnail_url IS NULL")->fetchColumn();
$list = $pdo->query("SELECT id, title, url FROM videos WHERE thumbnail_url IS NULL LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideyView | Local Sync Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #020617; color: white; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .text-gradient { background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-premium { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -10px #6366f1; }
        .btn-stop { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .btn-stop:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -10px #ef4444; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .animate-spin-slow { animation: spin 8s linear infinite; }
        .terminal { background: #000; font-family: 'Courier New', Courier, monospace; overflow-y: scroll; font-size: 11px; }
        .terminal-line { border-left: 2px solid #6366f1; padding-left: 8px; margin-bottom: 4px; }
        .terminal-line.error { border-left-color: #ef4444; color: #f87171; }
    </style>
</head>
<body class="min-h-screen p-8 relative overflow-x-hidden">
    <div class="fixed -top-24 -left-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px] animate-pulse"></div>
    <div class="fixed -bottom-48 -right-48 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-[150px] animate-pulse"></div>

    <div class="max-w-6xl mx-auto relative z-10">
        <header class="mb-12 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-black tracking-tighter italic text-gradient">SYNC CENTER</h1>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-[0.3em] mt-2">Local Bypass & Frame Extraction</p>
            </div>
            <div class="glass px-6 py-3 rounded-2xl flex items-center gap-4">
                <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Database Linked</span>
            </div>
        </header>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="glass p-6 rounded-[2rem] border-indigo-500/20">
                <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Queue Status</p>
                <h2 class="text-4xl font-black italic"><span id="completed-count">0</span><span class="text-slate-700 mx-2">/</span><span id="pending-count"><?php echo $pending; ?></span></h2>
                <p class="text-xs font-bold text-slate-400 mt-2">Completed <span id="active-workers-container" class="hidden ml-2 text-indigo-400">| <span id="active-workers">0</span> threads activity</span></p>
            </div>
            <div class="md:col-span-3 glass p-8 rounded-[2rem]">
                <div class="flex justify-between items-center mb-4">
                    <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">FFmpeg Binary Path</label>
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Threads:</label>
                        <select id="concurrency" class="bg-white/5 border border-white/5 rounded-lg px-2 py-1 text-[10px] font-bold text-indigo-400 focus:outline-none" onchange="document.getElementById('load-warning').classList.toggle('hidden', this.value < 5)">
                            <option value="1">1 (Slow)</option>
                            <option value="3">3 (Normal)</option>
                            <option value="5" selected>5 (Fast)</option>
                            <option value="10">10 (Turbo)</option>
                        </select>
                    </div>
                </div>
                <div id="load-warning" class="mb-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center gap-3">
                    <span class="text-lg">⚠️</span>
                    <p class="text-[10px] font-bold text-amber-500/80 uppercase tracking-widest leading-relaxed">
                        High concurrency detected. Your system might feel "heavy" or slow while FFmpeg processes are active. Reduce threads if performance drops.
                    </p>
                </div>
                <div class="flex gap-4">
                    <input type="text" id="ffmpeg-path" class="flex-1 bg-white/5 border border-white/5 rounded-xl px-4 py-3 text-sm font-mono text-indigo-300 focus:outline-none focus:ring-2 ring-indigo-500/50 transition-all font-bold" value="C:\ffmpeg\bin\ffmpeg.exe">
                    <button id="start-btn" class="btn-premium px-10 py-3 rounded-xl font-black uppercase tracking-widest text-xs">Start Extraction</button>
                    <button id="stop-btn" class="btn-stop px-10 py-3 rounded-xl font-black uppercase tracking-widest text-xs hidden">Stop</button>
                </div>
                <p class="text-[10px] text-slate-600 mt-3 font-medium">Verify path: Folder <code>bin</code> must contain <code>ffmpeg.exe</code></p>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <!-- Processing Queue -->
            <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col">
                <div class="p-8 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-black text-white uppercase tracking-widest text-sm italic">Live Processing Queue</h3>
                    <div id="loader" class="hidden flex items-center gap-3">
                        <div class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Processing...</span>
                    </div>
                </div>
                <div class="max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left">
                        <tbody id="queue-body" class="divide-y divide-white/5">
                            <?php foreach($list as $video): ?>
                            <tr data-id="<?php echo $video['id']; ?>" class="group hover:bg-white/5 transition duration-300">
                                <td class="py-5 px-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-slate-900 border border-white/5 flex items-center justify-center text-xl grayscale opacity-40">🎬</div>
                                        <div class="overflow-hidden">
                                            <div class="text-sm font-bold text-white truncate w-48"><?php echo $video['title']; ?></div>
                                            <div class="text-[9px] text-slate-600 font-mono truncate"><?php echo $video['url']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 px-8 text-right">
                                    <span class="status-badge text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-slate-800 text-slate-500">Waiting</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Terminal Logs -->
            <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col bg-black/40">
                <div class="p-8 border-b border-white/5 flex items-center justify-between bg-white/5">
                    <h3 class="font-black text-white uppercase tracking-widest text-sm italic">Process & Debug Logs</h3>
                    <button onclick="document.getElementById('terminal').innerHTML=''" class="text-[9px] font-black text-slate-500 uppercase tracking-widest hover:text-white transition">Clear Console</button>
                </div>
                <div id="terminal" class="terminal p-6 flex-1 h-[500px] text-indigo-300/80 leading-relaxed font-mono">
                    <div class="terminal-line">Initialized Sync Center. Waiting for trigger...</div>
                </div>
            </div>
        </section>

        <footer class="mt-8 text-center pb-12">
            <p class="text-slate-600 text-[10px] font-bold uppercase tracking-[0.2em]">&copy; 2026 VIDEYVIEW | LOCAL DEPLOYMENT UTILITY</p>
        </footer>
    </div>

    <script>
        const startBtn = document.getElementById('start-btn');
        const stopBtn = document.getElementById('stop-btn');
        const ffmpegPath = document.getElementById('ffmpeg-path');
        const concurrencyInp = document.getElementById('concurrency');
        const queueBody = document.getElementById('queue-body');
        const pendingCount = document.getElementById('pending-count');
        const completedCount = document.getElementById('completed-count');
        const activeWorkers = document.getElementById('active-workers');
        const activeWorkersContainer = document.getElementById('active-workers-container');
        const loader = document.getElementById('loader');
        const terminal = document.getElementById('terminal');

        let isRunning = false;
        let threadCount = 0;
        let successCount = 0;

        function updateThreadCount(delta) {
            threadCount += delta;
            activeWorkers.innerText = threadCount;
            if (threadCount > 0) activeWorkersContainer.classList.remove('hidden');
            else activeWorkersContainer.classList.add('hidden');
        }

        function logToConsole(message, type = 'info') {
            const line = document.createElement('div');
            line.className = `terminal-line ${type}`;
            const time = new Date().toLocaleTimeString();
            line.innerHTML = `<span class="text-slate-600">[${time}]</span> ${message}`;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight;
        }

        async function processItem(row) {
            if (!isRunning) return;
            const id = row.dataset.id;
            const title = row.querySelector('.text-sm').innerText;
            const badge = row.querySelector('.status-badge');
            
            updateThreadCount(1);
            badge.innerText = 'Extracting';
            badge.classList.replace('bg-slate-800', 'bg-indigo-500/20');
            badge.classList.replace('text-slate-500', 'text-indigo-400');
            
            logToConsole(`Starting extraction for: <strong>${title}</strong>...`);
            
            try {
                const res = await fetch(`?action=sync&id=${id}&ffmpeg=${encodeURIComponent(ffmpegPath.value)}`);
                const data = await res.json();
                
                if (data.log) {
                    const logSnippet = data.log.split('\n').map(l => `<div class='pl-4 opacity-50'>${l}</div>`).join('');
                    logToConsole(`Process output for ${id}:\n${logSnippet}`);
                }

                if(data.success) {
                    badge.innerText = 'Completed';
                    badge.classList.replace('bg-indigo-500/20', 'bg-green-500/20');
                    badge.classList.replace('text-indigo-400', 'text-green-500');
                    
                    successCount++;
                    completedCount.innerText = successCount;
                    pendingCount.innerText = parseInt(pendingCount.innerText) - 1;
                    row.querySelector('div.grayscale').classList.remove('grayscale', 'opacity-40');
                    logToConsole(`Successfully processed: ${title}`, 'success');
                } else {
                    badge.innerText = 'Error';
                    badge.classList.replace('bg-indigo-500/20', 'bg-red-500/20');
                    badge.classList.replace('text-indigo-400', 'text-red-500');
                    logToConsole(`Error processing ${title}: ${data.error}`, 'error');
                }
            } catch(e) {
                badge.innerText = 'Network Error';
                logToConsole(`Network error for ${title}`, 'error');
            } finally {
                updateThreadCount(-1);
            }
        }

        stopBtn.onclick = () => {
            isRunning = false;
            stopBtn.disabled = true;
            stopBtn.innerText = 'Stopping threads...';
            logToConsole('Stop requested. Waiting for active threads to finish...', 'error');
        };

        startBtn.onclick = async () => {
            if(isRunning) return;
            isRunning = true;
            
            startBtn.classList.add('hidden');
            stopBtn.classList.remove('hidden');
            stopBtn.disabled = false;
            stopBtn.innerText = 'Stop';
            
            loader.classList.remove('hidden');
            logToConsole(`Starting process with ${concurrencyInp.value} threads.`, 'success');

            const rows = Array.from(queueBody.children).filter(r => !r.querySelector('.text-green-500'));
            const concurrency = parseInt(concurrencyInp.value) || 1;
            
            // Worker Pool
            const queue = [...rows];
            const workers = Array(Math.min(concurrency, queue.length)).fill(null).map(async () => {
                while (queue.length > 0 && isRunning) {
                    const row = queue.shift();
                    await processItem(row);
                }
            });

            await Promise.all(workers);

            isRunning = false;
            startBtn.classList.remove('hidden');
            stopBtn.classList.add('hidden');
            loader.classList.add('hidden');
            
            if (threadCount === 0) {
                logToConsole('All processes finished or stopped.', 'success');
            }
        };
    </script>
</body>
</html>
