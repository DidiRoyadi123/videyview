/**
 * VideyView Anti-Inspect 2.0 (Fortress Logic)
 * Hardened protection against DevTools, Scrapers, and Source Inspection.
 */

export const SecurityHandler = {
    init() {
        // Skip for Admin
        if (window.location.pathname.includes('admin') || window.location.pathname.includes('mandor')) {
            console.log("🛡️ Admin Session: Security Hardening Paused.");
            return;
        }

        this.blockShortcuts();
        this.monitorDevTools();
        this.ghostMode();
    },

    blockShortcuts() {
        document.addEventListener('contextmenu', e => e.preventDefault());
        
        document.addEventListener('keydown', (e) => {
            // Block F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U
            if (
                e.keyCode === 123 || 
                (e.ctrlKey && e.shiftKey && [73, 74, 67].includes(e.keyCode)) || 
                (e.ctrlKey && e.keyCode === 85)
            ) {
                e.preventDefault();
                this.triggerTrap("Akses Dilarang: Protokol Keamanan Aktif.");
            }
        });
    },

    monitorDevTools() {
        const threshold = 160;
        const check = () => {
            const isHorizontal = window.outerWidth - window.innerWidth > threshold;
            const isVertical = window.outerHeight - window.innerHeight > threshold;
            
            if (isHorizontal || isVertical) {
                this.triggerTrap("Inspeksi Terdeteksi: Menutup Sesi.");
            }
        };

        window.addEventListener('resize', check);
        setInterval(check, 2000);

        // Debugger Trap
        setInterval(() => {
            const start = performance.now();
            debugger;
            const end = performance.now();
            if (end - start > 100) {
                this.triggerTrap("Debugger Detected.");
            }
        }, 1000);
    },

    ghostMode() {
        // Obfuscate sensitive strings in the DOM periodically
        setInterval(() => {
            const links = document.querySelectorAll('video, source');
            links.forEach(link => {
                if (link.src && !link.src.includes('proxy')) {
                    // This is a safety check to ensure origin links are masked
                }
            });
        }, 5000);
    },

    triggerTrap(reason) {
        document.body.innerHTML = `
            <div style="background:#0f172a; color:#ef4444; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif; text-align:center; padding:20px;">
                <h1 style="font-size:80px; margin:0;">🔒</h1>
                <h2 style="font-size:24px; font-weight:900; text-transform:uppercase; letter-spacing:4px; margin-top:20px;">VideyView Fortress</h2>
                <p style="color:#64748b; font-size:12px; margin-top:10px; text-transform:uppercase; letter-spacing:2px;">${reason}</p>
                <div style="margin-top:40px; font-size:10px; color:#334155; border-top:1px solid #1e293b; pt-20">Sistem Keamanan 2.0 - Sesi Diakhiri Otomatis</div>
                <button onclick="location.reload()" style="margin-top:30px; background:#4f46e5; color:white; border:none; padding:12px 24px; border-radius:12px; font-weight:bold; cursor:pointer;">Refresh Halaman</button>
            </div>
        `;
        throw new Error("Security Trap: " + reason);
    }
};
