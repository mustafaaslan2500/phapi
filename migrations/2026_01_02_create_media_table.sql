-- Media (Dosya) Tablosu
-- Resim ve döküman yükleme için

CREATE TABLE IF NOT EXISTS media (
    m_id INT AUTO_INCREMENT PRIMARY KEY,
    m_unique_id VARCHAR(32) NOT NULL UNIQUE COMMENT 'Random unique ID',
    m_user_id INT NOT NULL COMMENT 'Yükleyen kullanıcı',
    m_file_name VARCHAR(255) NOT NULL COMMENT 'Orijinal dosya adı',
    m_file_path VARCHAR(500) NOT NULL COMMENT 'Dosya yolu',
    m_media_type ENUM('image', 'document') NOT NULL COMMENT 'Dosya tipi',
    m_file_size INT NOT NULL COMMENT 'Dosya boyutu (byte)',
    m_mime_type VARCHAR(100) NOT NULL COMMENT 'MIME type (image/jpeg, application/pdf)',
    m_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (m_user_id),
    INDEX idx_unique_id (m_unique_id),
    INDEX idx_media_type (m_media_type),
    INDEX idx_created_at (m_created_at),
    
    FOREIGN KEY (m_user_id) REFERENCES users(u_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test verisi (opsiyonel)
-- INSERT INTO media (m_unique_id, m_user_id, m_file_name, m_file_path, m_media_type, m_file_size, m_mime_type) 
-- VALUES ('abc123xyz456', 1, 'test.jpg', 'files/2026/01/abc123xyz456.jpg', 'image', 51200, 'image/jpeg');
