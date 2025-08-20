-- Add GPS columns to kunjungan table
ALTER TABLE kunjungan ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER foto;
ALTER TABLE kunjungan ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;
ALTER TABLE kunjungan ADD COLUMN location_address TEXT NULL AFTER longitude;

-- Add index for better performance
CREATE INDEX idx_kunjungan_location ON kunjungan(latitude, longitude);

-- Update existing records to have NULL GPS data
UPDATE kunjungan SET latitude = NULL, longitude = NULL, location_address = NULL WHERE latitude IS NULL;