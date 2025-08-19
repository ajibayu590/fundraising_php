-- Add foto column to kunjungan table
ALTER TABLE kunjungan ADD COLUMN foto VARCHAR(255) NULL AFTER catatan;

-- Add index for better performance
CREATE INDEX idx_kunjungan_foto ON kunjungan(foto);

-- Update existing records to have NULL foto
UPDATE kunjungan SET foto = NULL WHERE foto IS NULL;