import os
import json
import requests
import subprocess
import threading
from queue import Queue
from urllib.parse import urlparse, quote
import urllib3

# Suppress insecure request warnings if you bypass SSL
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- Configuration ---
EXPORT_FILE = "sync_list.json"
STORAGE_DIR = os.path.join("storage", "app", "public")
VIDEO_DIR = os.path.join(STORAGE_DIR, "videos")
THUMB_DIR = os.path.join(STORAGE_DIR, "thumbnails")

# IMPORTANT: If using LOCAL_PROXY with 'php artisan serve', 
# YOU MUST set MAX_THREADS = 1 because 'serve' is NOT multi-threaded.
MAX_THREADS = 1

# If your ISP blocks videy.co, we can use your local Laravel app as a proxy
# Make sure 'php artisan serve' is running!
LOCAL_PROXY_URL = "http://localhost:8000/video-proxy?url="
USE_LOCAL_PROXY = True 

# --- Path to FFmpeg ---
# We found ffmpeg.exe in your project folder, so we'll use it by default.
FFMPEG_PATH = os.path.join(os.getcwd(), 'ffmpeg.exe') 

# Ensure directories exist
os.makedirs(VIDEO_DIR, exist_ok=True)
os.makedirs(THUMB_DIR, exist_ok=True)

import sys

def download_video(video_data):
    url = video_data['url']
    slug = video_data['slug']
    title = video_data['title']
    
    video_filename = f"{slug}.mp4"
    video_path = os.path.join(VIDEO_DIR, video_filename)
    thumb_path = os.path.join(THUMB_DIR, f"{slug}.jpg")

    print(f"\n[START] Processing: {title} ({slug})")

    # 1. Download Video if not exists (and is not 0 bytes)
    if not os.path.exists(video_path) or os.path.getsize(video_path) == 0:
        # Try Strategy 1: Local Proxy 
        if USE_LOCAL_PROXY:
            try:
                proxy_url = LOCAL_PROXY_URL + quote(url, safe='')
                print(f"  [TRY] Proxy: {proxy_url[:80]}...")
                execute_download(proxy_url, video_path, video_filename)
                generate_thumbnail(video_path, thumb_path, slug)
                return
            except requests.exceptions.HTTPError as e:
                status_code = e.response.status_code if hasattr(e, 'response') else '??'
                print(f"  [FAIL] Proxy HTTP {status_code}")
            except Exception as e:
                err_str = str(e)
                print(f"  [FAIL] Proxy Error: {err_str[:100]}")

        # Try Strategy 2: Direct Download 
        try:
            print(f"  [TRY] Downloading Direct...")
            execute_download(url, video_path, video_filename)
            generate_thumbnail(video_path, thumb_path, slug)
        except Exception as e:
            err_msg = str(e)
            print(f"\n  [ERROR] All download attempts failed: {err_msg[:100]}")
    else:
        print(f"  [SKIP] Video exists: {video_filename}")
        generate_thumbnail(video_path, thumb_path, slug)

def execute_download(target_url, save_path, filename):
    with requests.get(target_url, stream=True, timeout=60, verify=False) as r:
        r.raise_for_status()
        total_size = int(r.headers.get('content-length', 0))
        downloaded = 0
        
        with open(save_path, 'wb') as f:
            for chunk in r.iter_content(chunk_size=65536):
                if chunk:
                    f.write(chunk)
                    downloaded += len(chunk)
                    if total_size:
                        percent = int(100 * downloaded / total_size)
                        sys.stdout.write(f"\r  [PROGRESS] {percent}% ({downloaded}/{total_size} bytes)")
                        sys.stdout.flush()
        print(f"\n  [OK] Saved: {filename}")

def generate_thumbnail(video_path, thumb_path, slug):
    # 2. Extract Thumbnail if not exists
    if not os.path.exists(thumb_path):
        try:
            cmd = [
                FFMPEG_PATH, '-y', 
                '-ss', '0.1', 
                '-i', video_path, 
                '-vframes', '1', 
                '-q:v', '2', 
                thumb_path
            ]
            subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, check=True)
            print(f"  [OK] Thumbnail generated: {slug}.jpg")
        except Exception as e:
            print(f"  [ERROR] Thumbnail failed for {slug}: {e}")
    else:
        pass # print(f"  [SKIP] Thumbnail exists: {slug}.jpg")

# --- Threading Logic ---
queue = Queue()

def worker():
    while True:
        video_data = queue.get()
        if video_data is None:
            break
        download_video(video_data)
        queue.task_done()

def main():
    if not os.path.exists(EXPORT_FILE):
        print(f"Error: {EXPORT_FILE} not found. Please export it from Admin Dashboard.")
        return

    with open(EXPORT_FILE, 'r') as f:
        raw_videos = json.load(f)

    # Deduplicate by slug
    seen_slugs = set()
    videos = []
    for v in raw_videos:
        if v['slug'] not in seen_slugs:
            videos.append(v)
            seen_slugs.add(v['slug'])

    print(f"Found {len(videos)} unique videos to process (Skipped {len(raw_videos) - len(videos)} duplicates in JSON).")

    threads = []
    for i in range(MAX_THREADS):
        t = threading.Thread(target=worker)
        t.start()
        threads.append(t)

    for video in videos:
        queue.put(video)

    queue.join()

    # Stop workers
    for i in range(MAX_THREADS):
        queue.put(None)
    for t in threads:
        t.join()

    print("\n--- All Done! ---")
    print("Now go to Admin Dashboard and click 'Sync Storage'.")

if __name__ == "__main__":
    main()
