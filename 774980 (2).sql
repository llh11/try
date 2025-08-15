-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- 主机： localhost:3306
-- 生成日期： 2025-08-15 22:46:22
-- 服务器版本： 5.6.51
-- PHP 版本： 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `774980`
--

-- --------------------------------------------------------

--
-- 表的结构 `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$0rvytWwR4grycfFpe3KQqejlVy0jlcUq1y6HqZ/CNGBKvMC6OaH.q');

-- --------------------------------------------------------

--
-- 表的结构 `device_commands`
--

CREATE TABLE `device_commands` (
  `id` int(11) NOT NULL,
  `device_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `command` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameter` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','executed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `device_status`
--

CREATE TABLE `device_status` (
  `id` int(11) NOT NULL,
  `device_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `first_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `device_status`
--

INSERT INTO `device_status` (`id`, `device_identifier`, `ip_address`, `app_version`, `last_seen`, `first_seen`) VALUES
(1, '4432c4b5-21b0-4106-ad5e-33df2fb540b9', '112.230.152.204', '2.4.3', '2025-06-14 16:25:43', '2025-04-20 04:56:49'),
(20, 'b32a1c8e-f073-4dd6-af9f-fd0127d5670b', '119.188.2.221', '2.4.3', '2025-07-01 12:09:57', '2025-04-20 10:04:01'),
(878, '050b517a-e9b0-42ab-ab3a-c73fcad02b94', '119.188.2.223', '2.1.0', '2025-06-17 01:56:21', '2025-04-25 04:04:01'),
(11788, 'cpp-client-001', '39.64.187.35', '1.0.0', '2025-06-02 06:26:46', '2025-06-01 18:09:39'),
(11830, 'cpp-client-002', '39.64.187.35', '1.0.0', '2025-06-02 06:27:21', '2025-06-02 06:26:58'),
(11838, 'cpp-client-003', '39.64.187.35', '1.0.0', '2025-06-02 06:27:26', '2025-06-02 06:27:26'),
(11839, 'cpp-client-004', '39.64.187.35', '1.0.0', '2025-06-02 06:27:31', '2025-06-02 06:27:31'),
(11840, 'cpp-client-005', '39.64.187.35', '1.0.0', '2025-06-02 06:27:35', '2025-06-02 06:27:35'),
(11841, 'cpp-client-006', '39.64.187.35', '1.0.0', '2025-06-02 06:27:39', '2025-06-02 06:27:39'),
(11842, 'cpp-client-007', '39.64.187.35', '1.0.0', '2025-06-02 06:27:43', '2025-06-02 06:27:43'),
(11843, 'cpp-client-008', '39.64.187.35', '1.0.0', '2025-06-02 06:27:46', '2025-06-02 06:27:46'),
(11844, 'cpp-client-009', '39.64.187.35', '1.0.0', '2025-06-02 06:27:52', '2025-06-02 06:27:52'),
(11845, 'cpp-client-010', '39.64.187.35', '1.0.0', '2025-06-02 06:27:55', '2025-06-02 06:27:55'),
(15322, '81f0c25c-d274-4f25-bcf6-7847b22c13c3', '119.188.2.246', '2.4.3', '2025-06-15 11:20:16', '2025-06-11 00:01:52'),
(15837, '75593e20-bb1c-4bfc-b202-75eedea9bd57', '112.230.152.204', '2.4.3', '2025-06-15 04:38:32', '2025-06-15 04:26:23'),
(19222, '69db2da2-5873-4d08-8fc9-02e500823bfa', '39.71.243.119', '7.9.0', '2025-08-15 07:15:07', '2025-06-28 09:48:10'),
(19456, '858b3873-6993-405c-815e-b23114b94b04', '119.188.2.232', '3.4.0', '2025-07-11 03:01:46', '2025-07-03 09:45:38'),
(20691, 'b2276b78-d1de-4225-b649-2013703de055', '119.164.34.154', '7.9.0', '2025-08-11 06:58:22', '2025-08-11 05:38:04');

-- --------------------------------------------------------

--
-- 表的结构 `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `login_logs`
--

INSERT INTO `login_logs` (`id`, `ip_address`, `user_agent`, `login_time`, `status`) VALUES
(1, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:15:02', '失败'),
(2, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:15:08', '失败'),
(3, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:16:01', '失败'),
(4, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:16:07', '失败'),
(5, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:16:20', '失败'),
(6, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:16:30', '失败'),
(7, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:17:01', '失败'),
(8, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:20:15', '失败'),
(9, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:20:29', '失败'),
(10, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:23:57', '成功'),
(11, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:24:04', '成功'),
(12, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:24:13', '成功'),
(13, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:24:54', '成功'),
(14, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:26:05', '成功'),
(15, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:30:11', '成功'),
(16, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:35:41', '成功'),
(17, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:39:17', '成功'),
(18, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:39:58', '成功'),
(19, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 10:50:24', '成功'),
(20, '103.151.172.32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:06:39', '成功');

-- --------------------------------------------------------

--
-- 表的结构 `support_tools`
--

CREATE TABLE `support_tools` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `filesize` bigint(20) NOT NULL,
  `filetype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checksum` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `download_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `support_tools`
--

INSERT INTO `support_tools` (`id`, `filename`, `description`, `filesize`, `filetype`, `checksum`, `upload_timestamp`, `download_count`) VALUES
(1, 'VC_redist.x64.exe', '如果电脑提示缺少文件，那么就安装这个', 25641968, 'application/x-dosexec', 'c0077fe61901918d4e99c9d631245c60', '2025-05-26 17:58:17', 2),
(4, 'users.exe', '客户端程序，用于下载和安装文件，同时也能上传文件，功能和网页端类似', 236032, 'application/x-dosexec', 'e7b702fc38dfe42058002ea4300b9033', '2025-06-02 02:37:07', 0),
(5, 'up.exe', '上传工具', 131584, 'application/x-dosexec', 'e562a4b88dda3511e7a5dee979495605', '2025-06-02 02:58:56', 0),
(6, 'Updater.exe', '安装包', 50176, 'application/x-dosexec', '1f953e7726aa76af15436fe39b109a1b', '2025-08-11 13:35:24', 1),
(7, '____________.txt', '21', 1219, 'text/plain', 'cba9e87073548e3dacc22f7e6062718a', '2025-08-11 14:28:24', 1);

-- --------------------------------------------------------

--
-- 表的结构 `versions`
--

CREATE TABLE `versions` (
  `id` int(11) NOT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` bigint(20) NOT NULL,
  `checksum` varchar(32) CHARACTER SET ascii NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `upload_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `download_count` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `versions`
--

INSERT INTO `versions` (`id`, `version`, `filename`, `filesize`, `checksum`, `type`, `description`, `upload_timestamp`, `download_count`) VALUES
(1, '0.9', 'con-0.9.exe', 40960, '0edc843a2747fad74f1c69b686653a09', 'configurator', NULL, '2025-04-20 04:54:15', 6),
(2, '0.9', 'news-0.9.exe', 250880, 'bfbfbb3b2d6c65958b1bc7a5633827ba', 'newsapp', NULL, '2025-04-20 05:37:31', 1),
(3, '2.1.1', 'news-2.1.1.exe', 250880, '5cc5aa7585a23375567749711582a4f1', 'newsapp', NULL, '2025-04-20 06:00:50', 8),
(6, '2.1.0', 'news-2.1.0.exe', 250880, '5cc5aa7585a23375567749711582a4f1', 'newsapp', NULL, '2025-04-20 06:00:35', 2),
(9, '2.2.2', 'news-2.2.2.exe', 233984, '275f0c07f97a8e9a6a921dd69a7366a3', 'newsapp', NULL, '2025-06-01 18:57:18', 1),
(10, '2.4.3', 'news-2.4.3.exe', 267776, '4a811490db987bdc6118a83a19880a89', 'newsapp', NULL, '2025-06-02 06:25:05', 4),
(11, '2.1.3', 'con-2.1.3.exe', 47104, '1873a28fc027402fc59f830a6d992687', 'configurator', NULL, '2025-06-02 06:25:33', 3),
(12, '3.0.0', 'con-3.0.0.exe', 99840, '9f6d8c31556373a78dd0f099312ee1ca', 'configurator', NULL, '2025-06-15 04:45:24', 8),
(13, '3.1.0', 'con-3.1.0.exe', 101376, '1bd830b824813f7730a8f30bcc11d3d4', 'configurator', NULL, '2025-06-15 10:00:00', 7),
(14, '3.3.3', 'news-3.3.3.exe', 146432, '16ed83acef94fa26fc03e788bf67e8d6', 'newsapp', NULL, '2025-06-15 10:00:33', 3),
(15, '3.3.4', 'news-3.3.4.exe', 155648, 'aa60062b46c4da993b41f34873a13b2f', 'newsapp', NULL, '2025-06-29 08:35:00', 3),
(17, '8.0.0', 'news-8.0.0.exe', 150016, 'c61e5db18087f8ceb8443d40b18f0984', 'newsapp', '新闻调度器 v8.9 更新公告\r\n发布日期： 2024年8月8日\r\n\r\n各位亲爱的用户：\r\n\r\n我们很高兴地宣布，新闻调度器 v8.9 版本现已正式发布！\r\n\r\n本次更新的核心是为您带来一个更智能、更透明、更无缝的程序更新体验。我们认真听取了反馈，并对整个更新机制进行了彻底的升级。\r\n\r\n本次更新亮点：\r\n【新增】更透明的更新信息\r\n告别盲目升级！当检测到新版本时，更新提示框内将直接为您展示详细的更新日志和功能描述。您可以清晰地了解到新版本带来了哪些改进和优化，再决定是否更新。\r\n\r\n【新增】智能的自动检查\r\n为了让您不错过任何重要更新，程序现在会每小时在后台自动静默检查新版本。您无需任何操作，即可确保使用的是最新、最稳定的程序。当然，您仍然可以随时通过托盘菜单进行手动检查。\r\n\r\n【优化】无缝的更新与重启\r\n我们对更新脚本逻辑进行了优化。现在，当您点击“立即更新”后，程序将自动在后台完成新版本的下载和替换，并在完成后流畅地重启至最新版本，整个过程一气呵成，无需手动干预。\r\n\r\n我们强烈建议所有用户更新到 v8.9 版本，以享受此次带来的全新便捷体验。\r\n\r\n感谢您一直以来的支持！\r\n\r\n新闻调度器团队', '2025-08-08 07:29:19', 8),
(18, '8.1.0', 'news-8.1.0.exe', 113152, '52352b557c5a2cdc467d713f66d89d21', 'newsapp', '123', '2025-08-08 09:15:54', 2),
(19, '8.1.0', 'con-8.1.0.exe', 113152, '0904177c79251ce1bdcd1a6242d77001', 'configurator', '更新配置工具', '2025-08-11 05:39:37', 2);

--
-- 转储表的索引
--

--
-- 表的索引 `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `device_commands`
--
ALTER TABLE `device_commands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_identifier_status` (`device_identifier`,`status`);

--
-- 表的索引 `device_status`
--
ALTER TABLE `device_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_identifier` (`device_identifier`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_app_version` (`app_version`);

--
-- 表的索引 `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `support_tools`
--
ALTER TABLE `support_tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_filename` (`filename`(191));

--
-- 表的索引 `versions`
--
ALTER TABLE `versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `filename` (`filename`),
  ADD KEY `idx_timestamp` (`upload_timestamp`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `device_commands`
--
ALTER TABLE `device_commands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `device_status`
--
ALTER TABLE `device_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20807;

--
-- 使用表AUTO_INCREMENT `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 使用表AUTO_INCREMENT `support_tools`
--
ALTER TABLE `support_tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `versions`
--
ALTER TABLE `versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
