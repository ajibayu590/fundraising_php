-- WhatsApp Templates Table
CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `variables` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default templates
INSERT INTO `whatsapp_templates` (`template_id`, `name`, `message`, `variables`) VALUES
('welcome_donor', 'Welcome Donor', 'Halo {nama_donatur}, terima kasih telah mendukung program fundraising kami. Kami akan menghubungi Anda segera untuk informasi lebih lanjut.', 'nama_donatur'),
('kunjungan_success', 'Kunjungan Berhasil', 'Halo {nama_donatur}, kunjungan fundraising kami telah berhasil. Terima kasih atas donasi sebesar {nominal_donasi} pada {tanggal_kunjungan}. Kami sangat menghargai dukungan Anda.', 'nama_donatur, nominal_donasi, tanggal_kunjungan'),
('kunjungan_followup', 'Kunjungan Follow Up', 'Halo {nama_donatur}, kami akan melakukan follow up kunjungan fundraising pada {tanggal_kunjungan}. Terima kasih atas waktu dan perhatian Anda.', 'nama_donatur, tanggal_kunjungan'),
('reminder_target', 'Reminder Target', 'Halo {nama_fundraiser}, target kunjungan hari ini adalah {target}. Silakan lakukan kunjungan untuk mencapai target yang telah ditentukan.', 'nama_fundraiser, target'),
('donation_thankyou', 'Thank You Donation', 'Terima kasih {nama_donatur} atas donasi sebesar {nominal_donasi}. Donasi Anda akan digunakan untuk membantu program sosial kami. Semoga Allah SWT membalas kebaikan Anda.', 'nama_donatur, nominal_donasi'),
('fundraiser_update', 'Fundraiser Update', 'Halo {nama_donatur}, ini adalah update dari fundraiser {nama_fundraiser}. Status kunjungan: {status_kunjungan}. Terima kasih.', 'nama_donatur, nama_fundraiser, status_kunjungan'),
('location_info', 'Location Information', 'Halo {nama_donatur}, kami akan melakukan kunjungan ke alamat: {alamat_donatur}. Mohon konfirmasi jika ada perubahan alamat. Terima kasih.', 'nama_donatur, alamat_donatur');