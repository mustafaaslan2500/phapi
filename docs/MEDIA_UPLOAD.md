# Media Upload System - Dosya Yükleme Servisi

## Kullanım

### 1. Dosya Yükleme
**POST** `/media/upload`

**Headers:**
```
Authorization: Bearer <access_token>
Content-Type: multipart/form-data
```

**Body (form-data):**
```
file: (dosya seç)
```

**Response:**
```json
{
  "status": true,
  "message": "Dosya başarıyla yüklendi",
  "unique_id": "a1b2c3d4e5f6...",
  "media_type": "image",
  "file_size": 102400
}
```

---

### 2. Dosya Bilgisi Getir
**GET** `/media/{unique_id}`

**Response:**
```json
{
  "status": true,
  "message": "Başarılı",
  "id": 1,
  "unique_id": "a1b2c3d4e5f6...",
  "user_id": 123,
  "file_name": "ornek.jpg",
  "file_path": "files/2026/01/a1b2c3d4e5f6....jpg",
  "file_url": "https://example.com/files/2026/01/...",
  "media_type": "image",
  "file_size": 102400,
  "file_size_human": "100 KB",
  "mime_type": "image/jpeg",
  "created_at": "2026-01-02 12:00:00"
}
```

---

### 3. Kullanıcının Dosyalarını Listele
**GET** `/media/list?media_type=image&page=1&per_page=20`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Query Params (opsiyonel):**
- `media_type`: `image` veya `document` (boş = hepsi)
- `page`: Sayfa numarası (default: 1)
- `per_page`: Sayfa başına kayıt (default: 20, max: 100)

**Response:**
```json
{
  "status": true,
  "message": "Başarılı",
  "items": [
    {
      "id": 1,
      "unique_id": "a1b2c3d4...",
      "file_name": "ornek.jpg",
      "file_url": "https://...",
      "media_type": "image",
      "file_size_human": "100 KB",
      "created_at": "2026-01-02 12:00:00"
    }
  ],
  "total": 42,
  "page": 1,
  "per_page": 20,
  "total_pages": 3
}
```

---

### 4. Dosya Sil
**DELETE** `/media/{unique_id}`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response:**
```json
{
  "status": true,
  "message": "Dosya başarıyla silindi"
}
```

---

## Desteklenen Dosya Türleri

### Resimler (max 10 MB)
- JPEG/JPG
- PNG
- GIF
- WebP

### Dökümanlar (max 20 MB)
- PDF
- Word (DOC, DOCX)
- Excel (XLS, XLSX)
- Text (TXT)

---

## Dosya Yapısı

```
files/
  2026/
    01/
      a1b2c3d4e5f6....jpg
      f6e5d4c3b2a1....pdf
    02/
      ...
```

- Yıl/Ay klasör yapısı otomatik oluşturulur
- Dosya adı: `{unique_id}.{extension}`
- unique_id: 32 karakterlik hex (md5 benzeri)

---

## Veritabanı

**Tablo:** `media`

```sql
CREATE TABLE media (
  m_id INT AUTO_INCREMENT PRIMARY KEY,
  m_unique_id VARCHAR(32) UNIQUE NOT NULL,
  m_user_id INT NOT NULL,
  m_file_name VARCHAR(255) NOT NULL,
  m_file_path VARCHAR(500) NOT NULL,
  m_media_type ENUM('image', 'document') NOT NULL,
  m_file_size INT NOT NULL,
  m_mime_type VARCHAR(100) NOT NULL,
  m_created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (m_user_id) REFERENCES users(u_id) ON DELETE CASCADE
);
```

---

## Güvenlik

- **JWT Authentication:** Tüm upload/list/delete işlemleri JWT gerektirir
- **File Validation:** MIME type ve boyut kontrolü
- **Unique ID:** Tahmin edilemez 32 karakter
- **User Ownership:** Kullanıcı sadece kendi dosyalarını görebilir/silebilir
- **File Permissions:** 0644 (read-only for others)

---

## Hata Mesajları

- `Dosya bulunamadı` - $_FILES'da file yok
- `Dosya yüklenemedi` - Upload hatası
- `Desteklenmeyen dosya türü` - MIME type izin listesinde değil
- `Dosya boyutu maksimum X MB olabilir` - Boyut aşıldı
- `Dosya kaydedilemedi` - move_uploaded_file() hatası
- `Veritabanı hatası` - DB insert hatası
- `Dosya silinemedi veya bulunamadı` - unique_id yok veya başka kullanıcıya ait

---

## Örnek Kullanım (cURL)

### Upload:
```bash
curl -X POST https://api.example.com/media/upload \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -F "file=@/path/to/image.jpg"
```

### Get:
```bash
curl https://api.example.com/media/a1b2c3d4e5f6...
```

### List:
```bash
curl https://api.example.com/media/list?media_type=image&page=1 \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Delete:
```bash
curl -X DELETE https://api.example.com/media/a1b2c3d4e5f6... \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```
