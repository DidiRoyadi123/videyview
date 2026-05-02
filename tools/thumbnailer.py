import os
import subprocess
import sys

# --- Configuration ---
STORAGE_DIR = os.path.join("storage", "app", "public")
VIDEO_DIR = os.path.join(STORAGE_DIR, "videos")
THUMB_DIR = os.path.join(STORAGE_DIR, "thumbnails")

# --- Path to FFmpeg ---
# We found ffmpeg.exe in your project folder, so we'll use it by default.
FFMPEG_PATH = os.path.join(os.getcwd(), 'ffmpeg.exe') 

# Ensure directories exist
os.makedirs(THUMB_DIR, exist_ok=True)

def generate_thumbnail(video_path, thumb_path, filename):
    try:
        # FFmpeg: Extract frame at 0.1s
        cmd = [
            FFMPEG_PATH, '-y', 
            '-ss', '0.1', 
            '-i', video_path, 
            '-vframes', '1', 
            '-q:v', '2', 
            thumb_path
        ]
        # Use subprocess.run for better control
        result = subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, text=True)
        if result.returncode == 0:
            print(f"  [OK] Generated: {filename}.jpg")
            return True
        else:
            print(f"  [FAIL] FFmpeg error for {filename}")
            return False
    except Exception as e:
        print(f"  [ERROR] {filename}: {e}")
        return False

def main():
    if not os.path.exists(VIDEO_DIR):
        print(f"Error: {VIDEO_DIR} not found.")
        return

    print(f"Scanning {VIDEO_DIR} for videos...")
    
    files = [f for f in os.listdir(VIDEO_DIR) if f.endswith('.mp4')]
    print(f"Found {len(files)} videos.")

    processed = 0
    skipped = 0
    failed = 0

    for filename in files:
        slug = filename.replace('.mp4', '')
        video_path = os.path.join(VIDEO_DIR, filename)
        thumb_path = os.path.join(THUMB_DIR, f"{slug}.jpg")

        # Check if thumbnail exists and is not 0 bytes
        if not os.path.exists(thumb_path) or os.path.getsize(thumb_path) == 0:
            print(f"Processing: {filename}...")
            if generate_thumbnail(video_path, thumb_path, slug):
                processed += 1
            else:
                failed += 1
        else:
            skipped += 1

    print(f"\n--- Done! ---")
    print(f"Processed: {processed}")
    print(f"Skipped:   {skipped} (already exist)")
    print(f"Failed:    {failed}")
    print("\nNext: Go to Admin Dashboard and click 'Sync Storage' to update the website.")

if __name__ == "__main__":
    main()
