-- Migration: Remove user_tokens table (JWT Migration)
-- Tarih: 2026-01-02
-- Açıklama: Session + DB token sisteminden JWT'ye geçiş
-- user_tokens tablosu artık kullanılmıyor (stateless JWT sistemi)

-- YEDEK AL (geri dönüş için)
-- CREATE TABLE user_tokens_backup AS SELECT * FROM user_tokens;

-- user_tokens tablosunu kaldır
DROP TABLE IF EXISTS user_tokens;

-- NOTLAR:
-- 1. Bu migration geri alınamaz (verileri siliyoruz)
-- 2. Tüm kullanıcılar logout olacak ve yeniden giriş yapmalı
-- 3. JWT secret key .env'e eklenmeli: JWT_SECRET_KEY
-- 4. Redis kurulumu önerilir (blacklist için) ama opsiyonel
-- 5. Frontend/Mobile uygulamalar JWT Bearer token kullanmalı:
--    Authorization: Bearer <access_token>
