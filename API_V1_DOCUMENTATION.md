# VideyView API v1 Documentation
**Version**: 1.0 (Mobile Ready)
**Base URL**: `http://localhost:8000/api/v1`

## Overview
Dokumentasi ini menyediakan panduan teknis untuk integrasi pihak ketiga atau aplikasi mobile dengan VideyView. API menggunakan format data JSON dan mendukung paginasi standard Laravel.

---

## 1. Video Endpoints

### Get All Videos
Mengambil daftar video terbaru secara terpaginasi.

- **URL**: `/videos`
- **Method**: `GET`
- **Params**: `page` (optional)
- **Response Sample**:
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "title": "Amazing Video Sample",
            "slug": "amazing-video-sample",
            "thumbnail_url": "/storage/thumbnails/sample.jpg",
            "category": { "name": "Action", "slug": "action" },
            "created_at": "2026-04-27T12:00:00Z"
        }
    ],
    "total": 120
}
```

### Search Videos
Mencari video berdasarkan judul.

- **URL**: `/search`
- **Method**: `GET`
- **Params**: `q` (required)
- **Response Sample**: Sama seperti index video.

### Get Video Detail
Mengambil detail lengkap satu video berdasarkan slug.

- **URL**: `/videos/{slug}`
- **Method**: `GET`
- **Response Sample**:
```json
{
    "id": 1,
    "title": "Amazing Video Sample",
    "description": "Video description here...",
    "video_urls": {
        "server_1": "https://videy.co/v?id=...",
        "server_2": "https://streamtape.com/e/..."
    },
    "likes_count": 42,
    "views_count": 1250
}
```

---

## 2. Category Endpoints

### List Categories
Mengambil semua kategori aktif.

- **URL**: `/categories`
- **Method**: `GET`
- **Response Sample**:
```json
[
    {
        "id": 1,
        "name": "Movies",
        "slug": "movies",
        "icon": "🎬"
    }
]
```

---

## 3. Technical Notes
- **Paginasi**: Menggunakan limit default 12 item per halaman.
- **CORS**: Sudah dikonfigurasi untuk mengizinkan akses cross-origin dari frontend otonom.
- **Header**: Disarankan menggunakan header `Accept: application/json`.
