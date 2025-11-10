-- phpMyAdmin SQL Dump
-- Database: db_kripto
-- Versi yang sudah disesuaikan

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Struktur dari Tabel `users`
-- (SUDAH DISESUAIKAN DENGAN KOLOM PROFIL)
--
CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  
  -- INI KOLOM BARU UNTUK PROFIL (DIENKRIPSI AES-128) --
  `nama_lengkap` TEXT DEFAULT NULL,
  `peran` TEXT DEFAULT NULL,
  `tujuan` TEXT DEFAULT NULL,
  -- AKHIR KOLOM BARU --
  
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari Tabel `encrypted_messages`
--
CREATE TABLE `encrypted_messages` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` text NOT NULL,
  `encrypted_message` text NOT NULL,
  `rails` int DEFAULT '3',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari Tabel `encrypted_files`
--
CREATE TABLE `encrypted_files` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `encrypted_content` longtext NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari Tabel `steganografi`
--
CREATE TABLE `steganografi` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stego_filename` varchar(255) NOT NULL,
  `stego_path` varchar(500) NOT NULL,
  `hidden_message_preview` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `encrypted_messages`
--
ALTER TABLE `encrypted_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `encrypted_files`
--
ALTER TABLE `encrypted_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `steganografi`
--
ALTER TABLE `steganografi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `encrypted_messages`
--
ALTER TABLE `encrypted_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `encrypted_files`
--
ALTER TABLE `encrypted_files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `steganografi`
--
ALTER TABLE `steganografi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (FOR KEY)
--

--
-- Ketidakleluasaan untuk tabel `encrypted_messages`
--
ALTER TABLE `encrypted_messages`
  ADD CONSTRAINT `encrypted_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `encrypted_files`
--
ALTER TABLE `encrypted_files`
  ADD CONSTRAINT `encrypted_files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `steganografi`
--
ALTER TABLE `steganografi`
  ADD CONSTRAINT `steganografi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;