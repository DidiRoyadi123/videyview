import os
import json
import requests
import subprocess
import threading
import argparse
from queue import Queue
from urllib.parse import quote
import urllib3
from urllib3.util import connection
import sys
import time

# --- ISP BYPASS: Force real IPs to avoid 'Internet Positif' ---
_original_create_connection = connection.create_connection
def patched_create_connection(address, *args, **kwargs):
    host, port = address
    # ISP BP MAP
    mappings = {
        'videy.co': '172.67.73.18',
        'cdn.videy.co': '172.67.73.18',
        'api.streamtape.com': '195.35.23.222',
        'streamtape.com': '195.35.23.222',
        '861520586.tapecontent.net': '51.89.194.202',
        '861113552.tapecontent.net': '51.83.140.208'
    }
    if host in mappings:
        host = mappings[host]
    return _original_create_connection((host, port), *args, **kwargs)
connection.create_connection = patched_create_connection

# --- Banner & Colors ---
class Colors:
    BLUE = '\033[94m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    BOLD = '\033[1m'
    END = '\033[0m'

BANNER = rf"""{Colors.BLUE}{Colors.BOLD}
  __   __ _  ____  ____  _  _  _  _  __  ____  _    _ 
  \ \ / /| ||  _ \|  __|| |/ /| || ||  ||  __|| |  | |
   \ V / | || | | || |__ | ' / | || ||  || |__ | |  | |
    | |  | || |_| ||  __||  <  | || ||  ||  __|| |/\| |
    | |  | ||____/ | |__ | . \ \ \_/ /  || |__ \  /\  /
    |_|  |_||______|____||_|\_\ \___/   ||____| \/  \/ 
{Colors.GREEN}         Unified Video Sync & Downloader - PRO v1.0
{Colors.END}"""

# --- Configuration ---
STORAGE_DIR = os.path.join("storage", "app", "public")
VIDEO_DIR = os.path.join(STORAGE_DIR, "videos")
THUMB_DIR = os.path.join(STORAGE_DIR, "thumbnails")
MAX_RETRIES = 3
RETRY_DELAY = 2

# Global counter for progress tracking
progress_counter = 0
counter_lock = threading.Lock()

def run_artisan_sync():
    print(f"\n{Colors.BLUE}{Colors.BOLD}[SYNC]{Colors.END} Updating Laravel database...")
    try:
        # Run php artisan app:sync-storage
        subprocess.run(['php', 'artisan', 'app:sync-storage'], check=True)
    except Exception as e:
        print(f"{Colors.RED}[ERROR]{Colors.END} Could not trigger Artisan sync: {e}")

def generate_thumbnail(ffmpeg_path, video_path, thumb_path, slug):
    """Extracts a thumbnail and prints detailed errors if it fails."""
    if not os.path.exists(thumb_path) or os.path.getsize(thumb_path) == 0:
        try:
            cmd = [
                ffmpeg_path, '-y', 
                '-ss', '0.1', 
                '-i', video_path, 
                '-vframes', '1', 
                '-q:v', '2', 
                thumb_path
            ]
            # Capture stderr to help diagnose "FFmpeg error"
            result = subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, text=True)
            if result.returncode == 0:
                print(f"  {Colors.GREEN}[OK]{Colors.END} Thumbnail generated: {slug}.jpg")
                return True
            else:
                stderr = result.stderr.strip()
                print(f"  {Colors.RED}[FAIL]{Colors.END} FFmpeg error for {slug}:")
                print(f"    {Colors.YELLOW}{stderr.splitlines()[-1] if stderr else 'No stderr'}{Colors.END}")
                
                # If the file is not a valid video, delete both video and thumbnail
                if "Invalid data" in stderr or "Output file is empty" in stderr:
                    print(f"  {Colors.RED}[FATAL]{Colors.END} File is corrupted. DELETING video & thumb...")
                    try: 
                        if os.path.exists(video_path): os.remove(video_path)
                        if os.path.exists(thumb_path): os.remove(thumb_path)
                    except: pass
                return False
        except Exception as e:
            e_msg = str(e)
            print(f"  {Colors.RED}[ERROR]{Colors.END} FFmpeg critical failure: {e_msg[0:100]}")
            return False
    return True

def execute_download(target_url, save_path, filename, simple=False):
    """Downloads a file with a progress bar and redirect protection."""
    # simple=True disables the carriage-return progress bar for multi-threading
    
    for attempt in range(MAX_RETRIES):
        try:
            if not simple:
                print(f"    {Colors.YELLOW}[WAIT]{Colors.END} Waiting for server response (attempt {attempt+1}/{MAX_RETRIES})...")
            
            with requests.get(target_url, stream=True, timeout=30, verify=False, allow_redirects=False) as r:
                if r.status_code in [301, 302, 303, 307, 308]:
                    redirect_url = r.headers.get('Location', '')
                    if 'internet-positif' in redirect_url:
                        raise Exception(f"ISP blocked connection (Internet Positif detection)")
                    else:
                        raise Exception(f"Unexpected redirect to: {redirect_url}")
                        
                r.raise_for_status()
                total_size = int(r.headers.get('content-length', 0))
                downloaded = 0
                
                with open(save_path, 'wb') as f:
                    for chunk in r.iter_content(chunk_size=65536):
                        if chunk:
                            f.write(chunk)
                            downloaded += len(chunk)
                            if total_size and not simple:
                                percent = int(100 * downloaded / total_size)
                                bar_length = 20
                                filled = int(bar_length * downloaded // total_size)
                                bar = '█' * filled + '-' * (bar_length - filled)
                                
                                sys.stdout.write(f"\r  {Colors.BLUE}[PROGRESS]{Colors.END} |{bar}| {percent}% ({downloaded}/{total_size} bytes)")
                                sys.stdout.flush()
                
                if not simple:
                    print(f"\n  {Colors.GREEN}[OK]{Colors.END} Saved: {filename}")
                else:
                    print(f"  {Colors.GREEN}[OK]{Colors.END} Downloaded: {filename}")
                return # Success!
                
        except (requests.exceptions.ConnectionError, requests.exceptions.ChunkedEncodingError) as e:
            if attempt < MAX_RETRIES - 1:
                print(f"\n  {Colors.YELLOW}[RETRY]{Colors.END} Connection issue ({str(e)[:50]}). Retrying in {RETRY_DELAY}s...")
                time.sleep(RETRY_DELAY)
                continue
            else:
                raise e
        except Exception as e:
            raise e

def register_remote_sync(args, slug, local_path, status):
    """Notifies the remote Laravel app about the sync completion."""
    if not args.remote_url or not args.api_key:
        return

    api_endpoint = f"{args.remote_url.rstrip('/')}/api/internal/sync-video"
    headers = {
        'X-Internal-Sync-Key': args.api_key,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    payload = {
        'slug': slug,
        'local_path': local_path.replace('\\', '/'),
        'download_status': status
    }

    try:
        # Increase timeout slightly for remote connection
        r = requests.post(api_endpoint, json=payload, headers=headers, timeout=15, verify=False)
        if r.status_code == 200:
            print(f"  {Colors.GREEN}[REMOTE]{Colors.END} Status updated on hosting for {slug}")
        else:
            print(f"  {Colors.RED}[REMOTE]{Colors.END} Failed (HTTP {r.status_code}): {r.text[:100]}")
    except Exception as e:
        print(f"  {Colors.RED}[REMOTE]{Colors.END} Connection error: {str(e)[:100]}")

def process_video(args, video_data, total_count):
    """Orchestrates download and thumbnail generation for one video."""
    global progress_counter
    
    with counter_lock:
        progress_counter += 1
        current_num = progress_counter

    url = video_data['url']
    slug = video_data['slug']
    title = video_data.get('title', slug)
    
    # Standardize filename: Ensure only one "video-" prefix exists
    if slug.startswith('video-'):
        video_filename = f"{slug}.mp4"
    else:
        video_filename = f"video-{slug}.mp4"
    
    # We use relative path for database storage
    relative_video_path = f"videos/{video_filename}"
    video_path = os.path.join(VIDEO_DIR, video_filename)
    thumb_path = os.path.join(THUMB_DIR, f"{slug}.jpg")

    # Check if download OR thumbnail is needed
    video_needed = not os.path.exists(video_path) or os.path.getsize(video_path) == 0
    thumb_needed = not os.path.exists(thumb_path) or os.path.getsize(thumb_path) == 0

    processing_done = False

    if video_needed or thumb_needed:
        print(f"\n{Colors.BOLD}[START] [{current_num}/{total_count}]{Colors.END} {title}")

    # 1. Download Video
    if video_needed:
        download_success = False
        
        # Strategy 1: Local Proxy
        if args.proxy:
            try:
                proxy_url = f"{args.url}/video-proxy?url={quote(url, safe='')}"
                print(f"  {Colors.YELLOW}[TRY]{Colors.END} Proxy: {proxy_url[:60]}...")
                execute_download(proxy_url, video_path, video_filename, simple=(args.threads > 1))
                download_success = True
            except Exception as e:
                print(f"  {Colors.RED}[FAIL]{Colors.END} Proxy unresponsive or failed: {str(e)[:80]}")

        # Strategy 2: Direct (Fallback)
        if not download_success:
            try:
                print(f"  {Colors.YELLOW}[TRY]{Colors.END} Direct download...")
                execute_download(url, video_path, video_filename, simple=(args.threads > 1))
                download_success = True
            except Exception as e:
                print(f"  {Colors.RED}[ERROR]{Colors.END} Total failure: {str(e)[:80]}")
    else:
        print(f"  {Colors.BLUE}[SKIP]{Colors.END} Video exists: {video_filename}")

    # 2. Thumbnail Generation
    if os.path.exists(video_path) and os.path.getsize(video_path) > 0:
        if generate_thumbnail(args.ffmpeg, video_path, thumb_path, slug):
            processing_done = True
            # ONLY register if we actually have the file and thumbnail
            if args.remote_url:
                register_remote_sync(args, slug, relative_video_path, 'completed')

def worker(args, queue, total_count):
    while True:
        video_data = queue.get()
        if video_data is None:
            break
        try:
            process_video(args, video_data, total_count)
        except Exception as e:
            print(f"{Colors.RED}Critical error in worker:{Colors.END} {e}")
        queue.task_done()

def main():
    parser = argparse.ArgumentParser(description='VideyView Unified Sync Utility')
    parser.add_argument('--url', '-u', default='http://127.0.0.1:8000', help='Base URL of your Laravel app (default: http://127.0.0.1:8000)')
    parser.add_argument('--remote-url', '-r', help='Remote hosting URL (e.g. https://videyview.com)')
    parser.add_argument('--api-key', '-k', help='Internal Sync API Key for the remote app')
    parser.add_argument('--threads', '-t', type=int, default=1, help='Number of parallel downloads (default: 1 for local server compatibility)')
    parser.add_argument('--no-proxy', dest='proxy', action='store_false', help='Disable local proxy')
    parser.add_argument('--ffmpeg', default='ffmpeg', help='Path to ffmpeg executable')
    parser.set_defaults(proxy=True)
    
    args = parser.parse_args()

    # Auto-detect local ffmpeg if it's default
    if args.ffmpeg == 'ffmpeg':
        # Check bin folder first
        bin_ffmpeg = os.path.join(os.getcwd(), 'bin', 'ffmpeg.exe')
        if os.path.exists(bin_ffmpeg):
            args.ffmpeg = bin_ffmpeg
        else:
            # Check root folder
            local_ffmpeg = os.path.join(os.getcwd(), 'ffmpeg.exe')
            if os.path.exists(local_ffmpeg):
                args.ffmpeg = local_ffmpeg

    print(BANNER)
    print(f"{Colors.BOLD}Domain:{Colors.END} {args.url}")
    print(f"{Colors.BOLD}Proxy:{Colors.END} {'Enabled' if args.proxy else 'Disabled'}")
    print(f"{Colors.BOLD}FFmpeg:{Colors.END} {args.ffmpeg}")
    print("-" * 50)

    # 1. Start with a Sync
    run_artisan_sync()

    # 2. Load sync_list.json
    export_file = "sync_list.json"
    if not os.path.exists(export_file):
        print(f"\n{Colors.RED}[ERROR]{Colors.END} {export_file} not found! Export it from Admin > Videos first.")
        return

    with open(export_file, 'r') as f:
        raw_list = json.load(f)

    # Deduplicate
    seen = set()
    videos = []
    for v in raw_list:
        if v['slug'] not in seen:
            videos.append(v)
            seen.add(v['slug'])
    
    print(f"\n{Colors.GREEN}{Colors.BOLD}[INFO]{Colors.END} Found {len(videos)} unique videos to process.")

    # 3. Queue & Threads
    q = Queue()
    for v in videos:
        q.put(v)

    threads = []
    for i in range(args.threads):
        t = threading.Thread(target=worker, args=(args, q, len(videos)))
        t.start()
        threads.append(t)

    q.join()

    for _ in range(args.threads):
        q.put(None)
    for t in threads:
        t.join()

    # 4. Final Sync
    print(f"\n{Colors.GREEN}{Colors.BOLD}[FINISH]{Colors.END} All downloads completed.")
    run_artisan_sync()
    print("-" * 50)
    print(f"{Colors.GREEN}Your local library is now up to date!{Colors.END}")
    
    # Keep window open for double-click users
    input(f"\n{Colors.BOLD}Press Enter to exit...{Colors.END}")

if __name__ == "__main__":
    main()
