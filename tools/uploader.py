import os
import requests
import json
import time
import socket
import traceback
import sys
from pathlib import Path

# --- CONFIGURATION ---
CONFIG = {
    "BASE_DIR": r"C:\xampp\htdocs\videyview\public\storage\videos",
    "LARAVEL_API": "http://127.0.0.1:8000/api/internal/sync-mirror",
    "INTERNAL_SYNC_KEY": "vid-sync-7x8y9z-secure",
    
    "STREAMTAPE_LOGIN": "ba1402c48869ff3f5d71",
    "STREAMTAPE_KEY": "ZKbxp4w8VjIqxXb",
    
    "DOODSTREAM_KEY": "561492l0isghhqftfzqpnv",
    
    # ISP BYPASS: Domain to IP Mapping (Force Resolve)
    "DNS_MAP": {
        "api.streamtape.com": "104.21.96.46",
        "tapecontent.net": "51.89.194.202",
        "doodapi.co": "172.67.70.170",
    }
}

# --- COLORS & UI ---
class Colors:
    BLUE = '\033[94m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    CYAN = '\033[96m'
    BOLD = '\033[1m'
    END = '\033[0m'

BANNER = rf"""{Colors.CYAN}{Colors.BOLD}
 __      _______ _____  ________     ____      _______ ________          __
 \ \    / /_   _|  __ \|  ____\ \   / \ \    / /_   _|  ____\ \        / /
  \ \  / /  | | | |  | | |__   \ \_/ / \ \  / /  | | | |__   \ \  /\  / / 
   \ \/ /   | | | |  | |  __|   \   /   \ \/ /   | | |  __|   \ \/  \/ /  
    \  /   _| |_| |__| | |____   | |     \  /   _| |_| |____   \  /\  /   
     \/   |_____|_____/|______|  |_|      \/   |_____|______|   \/  \/    
{Colors.GREEN}            Massive Backdoor Uploader & Mirror Sync - v2.0
{Colors.END}"""

# Stats Tracker
STATS = {"success": 0, "skipped": 0, "failed": 0, "processed": 0}

# --- ISP BYPASS ENGINE (Socket Level) ---
original_getaddrinfo = socket.getaddrinfo

def custom_getaddrinfo(host, port, family=0, type=0, proto=0, flags=0):
    # exact matches first
    if host in CONFIG["DNS_MAP"]:
        return original_getaddrinfo(CONFIG["DNS_MAP"][host], port, family, type, proto, flags)
    
    # Wildcard/Suffix matches (Video-Proxy style)
    if host.endswith(".cloudatacdn.net") or host.endswith(".doodapi.co"):
        return original_getaddrinfo(CONFIG["DNS_MAP"]["doodapi.co"], port, family, type, proto, flags)
    
    if host.endswith(".tapecontent.net") or host.endswith(".streamtape.com"):
        return original_getaddrinfo(CONFIG["DNS_MAP"]["tapecontent.net"], port, family, type, proto, flags)
        
    return original_getaddrinfo(host, port, family, type, proto, flags)

socket.getaddrinfo = custom_getaddrinfo

# Initialize Standard Session
session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
})

def log(tag, msg, color=Colors.END):
    timestamp = time.strftime('%H:%M:%S')
    print(f"[{timestamp}] {color}{tag: <10}{Colors.END} {msg}")

def safe_json(resp):
    try:
        return resp.json()
    except Exception:
        return None

class ProgressFile:
    def __init__(self, filename):
        self.filename = filename
        self.total_size = os.path.getsize(filename)
        self.uploaded_size = 0
        self.file_obj = open(filename, 'rb')
        self.basename = os.path.basename(filename)

    def read(self, size=-1):
        # Reduced to 128KB for much higher update frequency (responsive like sync.py)
        CHUNK_SIZE = 128 * 1024 # 128 KB
        if size == -1 or size > CHUNK_SIZE:
             size = CHUNK_SIZE

        chunk = self.file_obj.read(size)
        if chunk:
            self.uploaded_size += len(chunk)
            percent = (self.uploaded_size / self.total_size) * 100
            done = int(20 * self.uploaded_size / self.total_size)
            bar = '█' * done + '░' * (20 - done)
            # Use sys.stdout for real-time responsiveness
            sys.stdout.write(f"\r  {Colors.BLUE}[PROGRESS]{Colors.END} |{bar}| {percent:3.0f}% ({self.uploaded_size/(1024*1024):.1f}/{self.total_size/(1024*1024):.1f} MB)")
            sys.stdout.flush()
        return chunk

    def __len__(self):
        return self.total_size

    def close(self):
        self.file_obj.close()
        print()

# --- HOSTING ACTIONS ---

def check_exists_streamtape(filename):
    url = f"https://api.streamtape.com/file/listfolder?login={CONFIG['STREAMTAPE_LOGIN']}&key={CONFIG['STREAMTAPE_KEY']}&search={filename}"
    try:
        resp = session.get(url, timeout=15)
        if resp.status_code == 200:
            data = safe_json(resp)
            if data and data.get("result") and data["result"].get("files"):
                return data["result"]["files"][0]["link"]
    except: pass
    return None

def check_exists_doodstream(filename):
    url = f"https://doodapi.co/api/file/list?key={CONFIG['DOODSTREAM_KEY']}&search={filename}"
    try:
        resp = session.get(url, timeout=15)
        if resp.status_code == 200:
            data = safe_json(resp)
            if data and data.get("result") and len(data["result"]) > 0:
                file_code = data["result"][0]["file_code"]
                return f"https://doodapi.co/e/{file_code}"
    except: pass
    return None

def upload_to_streamtape(file_path):
    log("UPLOAD", f"Starting Streamtape: {os.path.basename(file_path)}", Colors.YELLOW)
    try:
        ul_resp = session.get(f"https://api.streamtape.com/file/ul?login={CONFIG['STREAMTAPE_LOGIN']}&key={CONFIG['STREAMTAPE_KEY']}")
        ul_data = safe_json(ul_resp)
        if not ul_data or ul_data.get("status") != 200: return None
            
        target_url = ul_data.get("result", {}).get("url")
        pf = ProgressFile(file_path)
        up_resp = session.post(target_url, files={"file1": pf})
        pf.close()
        
        if up_resp.status_code == 200:
            return check_exists_streamtape(os.path.basename(file_path))
    except Exception as e:
        log("ERROR", f"Streamtape exception: {str(e)[:50]}", Colors.RED)
    return None

def upload_to_doodstream(file_path):
    log("UPLOAD", f"Starting Doodstream: {os.path.basename(file_path)}", Colors.YELLOW)
    try:
        ul_resp = session.get(f"https://doodapi.co/api/upload/server?key={CONFIG['DOODSTREAM_KEY']}")
        ul_data = safe_json(ul_resp)
        if not ul_data or ul_data.get("status") != 200: return None
            
        target_url = ul_data.get("result")
        if "?" in target_url: target_url += f"&api_key={CONFIG['DOODSTREAM_KEY']}"
        else: target_url += f"?api_key={CONFIG['DOODSTREAM_KEY']}"
        
        pf = ProgressFile(file_path)
        # Some servers want api_key in URL, some in data. We send in both for safety.
        up_resp = session.post(target_url, data={"api_key": CONFIG["DOODSTREAM_KEY"]}, files={"file": pf})
        pf.close()
        
        if up_resp.status_code == 200:
            data = safe_json(up_resp)
            result = data.get("result") if data else None
            file_code = None
            if isinstance(result, list) and len(result) > 0: file_code = result[0].get("filecode")
            elif isinstance(result, dict): file_code = result.get("filecode")
            
            if file_code: return f"https://doodapi.co/e/{file_code}"
            return check_exists_doodstream(os.path.basename(file_path))
    except Exception as e:
        log("ERROR", f"Doodstream exception: {str(e)[:50]}", Colors.RED)
    return None

def sync_to_laravel(slug, host, link, status="success"):
    headers = {"X-Internal-Sync-Key": CONFIG["INTERNAL_SYNC_KEY"]}
    payload = {"slug": slug, "host": host, "status": status, "mirror_link": link}
    try:
        # Increased timeout to 60s to handle local server pressure
        resp = session.post(CONFIG["LARAVEL_API"], headers=headers, json=payload, timeout=60)
        if resp.status_code == 200:
            log("SYNC", f"Updated {host} link for {slug}", Colors.GREEN)
            return True
        else:
            log("SYNC FAIL", f"HTTP {resp.status_code} for {slug}", Colors.RED)
    except Exception as e:
        log("SYNC ERR", str(e)[:50], Colors.RED)
    return False

def run():
    os.system('cls' if os.name == 'nt' else 'clear')
    print(BANNER)
    
    video_files = list(Path(CONFIG["BASE_DIR"]).glob("*.mp4"))
    total = len(video_files)
    log("INFO", f"Detected {Colors.BOLD}{total}{Colors.END} local videos in storage.", Colors.CYAN)
    print("-" * 75)
    
    # Process all files
    for idx, v_path in enumerate(video_files, 1):
        filename = v_path.name
        slug = v_path.stem
        STATS["processed"] += 1
        
        print(f"\n{Colors.BOLD}[{idx}/{total}]{Colors.END} {Colors.CYAN}{filename}{Colors.END}")
        
        # 1. Streamtape Logic
        st_link = check_exists_streamtape(filename)
        if st_link:
            log("SKIP", f"Found on Streamtape: {st_link[:40]}...", Colors.BLUE)
            sync_to_laravel(slug, "streamtape", st_link)
        else:
            st_link = upload_to_streamtape(str(v_path))
            if st_link:
                log("OK", f"Uploaded to Streamtape", Colors.GREEN)
                sync_to_laravel(slug, "streamtape", st_link)
                STATS["success"] += 1
            else:
                STATS["failed"] += 1
        
        # 2. Doodstream Logic
        ds_link = check_exists_doodstream(filename)
        if ds_link:
            log("SKIP", f"Found on Doodstream: {ds_link[:40]}...", Colors.BLUE)
            sync_to_laravel(slug, "doodstream", ds_link)
            STATS["skipped"] += 1
        else:
            ds_link = upload_to_doodstream(str(v_path))
            if ds_link:
                log("OK", f"Uploaded to Doodstream", Colors.GREEN)
                sync_to_laravel(slug, "doodstream", ds_link)
                STATS["success"] += 1
            else:
                STATS["failed"] += 1

    print("\n" + "=" * 75)
    print(f"{Colors.BOLD}SUMMARY REPORT{Colors.END}")
    print(f"  Processed : {STATS['processed']}")
    print(f"  Uploaded  : {Colors.GREEN}{STATS['success']}{Colors.END}")
    print(f"  Skipped   : {Colors.BLUE}{STATS['skipped']}{Colors.END}")
    print(f"  Failed    : {Colors.RED}{STATS['failed']}{Colors.END}")
    print("=" * 75)
    input(f"\n{Colors.BOLD}Process completed. Press Enter to exit...{Colors.END}")

if __name__ == "__main__":
    try:
        run()
    except KeyboardInterrupt:
        print(f"\n{Colors.RED}[STOP]{Colors.END} Process interrupted by user.")
