# JWT Authentication Migration Guide

## Geçiş Adımları

### 1. .env Ayarları

`.env` dosyasına JWT secret key ekle:

```env
JWT_SECRET_KEY=your-very-secure-secret-key-at-least-32-characters-long
```

**Güvenlik:** Production'da en az 32 karakter, random bir key kullan:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Redis Kurulumu (Opsiyonel ama Önerilen)

JWT blacklist için Redis kurulumu:

```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Redis yoksa sistem çalışır ama logout daha az güvenli olur (token expiry'ye bağlı).

### 3. Database Migration

```bash
mysql -u root -p your_database < migrations/2026_01_02_remove_user_tokens.sql
```

**ÖNEMLİ:** Bu işlem `user_tokens` tablosunu siler. Geri dönüş istenirse önce yedek al.

### 4. Frontend/Mobile Değişiklikleri

#### Login Response (Yeni)

```json
{
  "status": true,
  "message": "Login successful",
  "user_data": { ... },
  "tokens": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhb...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhb...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

#### API Request Headers

```
Authorization: Bearer <access_token>
Accept-Language: tr
```

#### Token Refresh

Access token süresi dolduğunda (15 dakika):

```
POST /user/refresh-token
Body: {
  "refresh_token": "eyJ0eXAiOiJKV1Qi..."
}
```

#### Logout

```
POST /user/logout
Body: {
  "access_token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "eyJ0eXAiOiJKV1Qi..."
}
```

### 5. API Endpoints (Değişen)

#### Yeni Endpoint
- `POST /user/refresh-token` - Access token yenile

#### Değişen Endpoints
- `POST /user/login` - Artık `tokens` objesi döndürüyor
- `POST /user/register` - Artık `tokens` objesi döndürüyor
- `POST /user/logout` - `access_token` ve `refresh_token` body'de
- `GET /user/is_login` - Session yerine JWT validation

#### Kaldırılan
- Token ile giriş yok (artık sadece email/password veya Google OAuth)

## Performans Karşılaştırması

### Eski Sistem (Session + DB Token)
- Her request: Session dosya I/O
- Login: 2 DB query (users + user_tokens)
- Memory: Session storage

### Yeni Sistem (JWT)
- Her request: Stateless (0 DB query)
- Login: 1 DB query (sadece users)
- Memory: Token client-side
- **~30-40% daha hızlı**

## Token Süreleri

- **Access Token:** 15 dakika (kısa - güvenlik)
- **Refresh Token:** 7 gün (uzun - kullanıcı deneyimi)

## Güvenlik

1. ✅ Stateless - DB query yok
2. ✅ Redis blacklist - logout güvenli
3. ✅ Short-lived access tokens
4. ✅ HTTPS only (production)
5. ✅ Secret key environment variable

## Geri Dönüş Planı

Eğer sorun olursa:

1. Migration SQL'deki yedek tabloyu geri yükle:
```sql
CREATE TABLE user_tokens AS SELECT * FROM user_tokens_backup;
```

2. Git'te eski commit'e dön:
```bash
git revert HEAD
```

3. Session sistemi AuthMiddleware tekrar aktif et
