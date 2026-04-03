# JWT Security Documentation

## 🔒 Güvenlik Özellikleri

### 1. Token İmzalama
- **Algoritma:** HS256 (HMAC-SHA256)
- **Secret Key:** Environment variable (.env)
- **Key Length:** Min 32 karakter önerilen

### 2. Token Süreleri
- **Access Token:** 15 dakika (kısa ömürlü - güvenlik)
- **Refresh Token:** 7 gün (uzun ömürlü - kullanıcı deneyimi)
- **Risk Penceresi:** Max 15 dakika

### 3. Şifre Hash Versioning ✅
```php
// Token'da şifre hash'inin ilk 8 karakteri
'pwd_hash' => substr(md5($passwordHash), 0, 8)
```

**Avantaj:**
- Kullanıcı şifre değiştirince TÜM eski tokenlar geçersiz olur
- Token revoke olmadan güvenlik sağlanır
- Stateless kalır (DB sorgusu yok)

**Örnek:**
1. User login → Token (pwd_hash: abc12345)
2. User şifre değiştirir
3. Eski token verify → pwd_hash eşleşmez → REJECTED
4. Yeni login gerekir

### 4. HTTPS Zorunluluğu ✅
```php
// Production'da otomatik kontrol
JWTService::enforceHttps()
```

**Kontroller:**
- `$_SERVER['HTTPS']`
- `$_SERVER['HTTP_X_FORWARDED_PROTO']` (reverse proxy)
- `$_SERVER['SERVER_PORT']`

**Development:** Bypass edilir
**Production:** HTTP request → 426 Upgrade Required

### 5. Stateless Architecture
- **DB Query:** 0 (sadece verify için)
- **Redis:** YOK (gereksiz overhead)
- **Ölçeklenebilirlik:** Horizontal scaling ready

## ⚠️ Potansiyel Riskler ve Çözümler

### 1. XSS (Cross-Site Scripting)

**Risk:** LocalStorage'dan token çalınabilir

**Çözüm:**
```javascript
// ❌ KÖTÜ: LocalStorage
localStorage.setItem('token', accessToken);

// ✅ İYİ: httpOnly cookie (backend set eder)
// Set-Cookie: access_token=...; HttpOnly; Secure; SameSite=Strict
```

**Öneri:**
- Production'da **httpOnly cookie** kullan
- Development'ta localStorage OK

### 2. Token Çalınması (Stolen Token)

**Risk:** Token çalınırsa 15 dakika kullanılabilir

**Mitigasyon:**
- ✅ Kısa token süresi (15 dakika)
- ✅ Şifre değişince invalidate
- ✅ HTTPS zorunlu
- ⚠️ IP/User-Agent binding (opsiyonel)

**IP/User-Agent Binding (Opsiyonel):**
```php
// Token'a ekle
'fingerprint' => md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])

// Verify'da kontrol et
if ($decoded->fingerprint !== md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])) {
    return null; // Farklı cihaz/ağ
}
```

### 3. Replay Attack

**Risk:** Token yakalanıp tekrar kullanılabilir

**Mitigasyon:**
- ✅ HTTPS (şifreleme)
- ✅ Kısa token süresi
- ✅ Şifre hash versioning
- ⏸️ Redis blacklist (şu an yok - gerekirse eklenebilir)

### 4. Brute Force

**Risk:** Secret key tahmin edilmeye çalışılabilir

**Çözüm:**
```bash
# Güçlü secret key oluştur (32+ karakter)
php -r "echo bin2hex(random_bytes(32));"

# .env
JWT_SECRET_KEY=64karakteruzunluktarandombircikti...
```

**Rate Limiting (Önerilen):**
```php
// Middleware eklenebilir
// Max 10 login attempt per IP per 15 min
```

## 🛡️ Production Checklist

### Zorunlu:
- [x] HTTPS enforced
- [x] Secret key 32+ karakter
- [x] .env file .gitignore'da
- [x] Token süresi 15 dakika
- [x] Şifre hash versioning

### Önerilen:
- [ ] httpOnly cookies (XSS koruması)
- [ ] Rate limiting (brute force koruması)
- [ ] IP/User-Agent binding (opsiyonel)
- [ ] Security headers (CSP, HSTS)
- [ ] CORS ayarları

### İzleme:
- [ ] Failed login attempts log
- [ ] Token verification failures log
- [ ] Suspicious activity alerts

## 🔧 Güvenlik Headers (Nginx/Apache)

**Nginx:**
```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Content-Security-Policy "default-src 'self'" always;
```

**Apache (.htaccess):**
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
```

## 📊 Güvenlik Seviyesi

| Özellik | Durum | Risk Level |
|---------|-------|-----------|
| Token İmzalama (HS256) | ✅ | LOW |
| HTTPS Zorunlu | ✅ | LOW |
| Kısa Token Süresi | ✅ | LOW |
| Şifre Hash Versioning | ✅ | LOW |
| Stateless (No DB) | ✅ | LOW |
| **TOPLAM** | **🟢 GÜVENLI** | **LOW** |

## 🎯 Kullanım Önerileri

### Küçük/Orta Projeler:
✅ Mevcut sistem yeterli
- Kısa token süresi
- HTTPS zorunlu
- Şifre versioning

### Enterprise Projeler:
➕ Ek öneriler:
- Redis blacklist ekle (logout güvenliği)
- IP/User-Agent binding
- Rate limiting
- Security monitoring
- 2FA (Two-Factor Authentication)

## 🔍 Test

```bash
# 1. Token güvenlik testi
php test_jwt_security.php

# 2. HTTPS testi (production)
curl -k http://yourapi.com/user/me
# Beklenen: 426 Upgrade Required

# 3. Şifre değişimi testi
# Login → Token al → Şifre değiştir → Eski token'ı kullan
# Beklenen: 401 Unauthorized
```

## 📚 Kaynaklar

- [JWT.io](https://jwt.io/)
- [OWASP JWT Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/JSON_Web_Token_for_Java_Cheat_Sheet.html)
- [RFC 7519 - JWT Standard](https://tools.ietf.org/html/rfc7519)

---

**Son Güncelleme:** 2026-01-02
**Güvenlik Seviyesi:** 🟢 Production Ready
