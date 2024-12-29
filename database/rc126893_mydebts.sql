

DROP TABLE IF EXISTS `activity_logs`;

CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES (1,1,'login','Admin logged in','127.0.0.1',NULL,'2024-12-19 03:41:32'),(2,2,'payment','Made payment of $500.00','127.0.0.1',NULL,'2024-12-19 03:41:32'),(3,3,'loan','Requested loan of $3000.00','127.0.0.1',NULL,'2024-12-19 03:41:32'),(4,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 05:37:05'),(5,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 06:07:54'),(6,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 07:33:41'),(7,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 07:37:20'),(8,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 08:05:43'),(9,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 08:07:11'),(10,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 08:09:20'),(11,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:47:04'),(12,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:48:58'),(13,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:49:34'),(14,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:50:00'),(15,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:58:14'),(16,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:59:41'),(17,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 09:59:57'),(18,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 10:00:08'),(19,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 12:28:44'),(20,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 12:30:53'),(21,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 12:33:37'),(22,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 13:26:47'),(23,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 13:36:34'),(24,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 14:03:53'),(25,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-19 16:02:57'),(26,1,'payment_added','Added payment of RM500.00 (Ref: PAY-20241220-0001) for user ID: 3','172.18.0.1',NULL,'2024-12-20 10:34:00'),(27,1,'payment_added','Added payment of RM900.00 (Ref: PAY-20241220-0002) for user ID: 3','172.18.0.1',NULL,'2024-12-20 10:37:56'),(28,1,'payment_added','Added payment of RM200.00 (Ref: PAY-20241220-0003) for user ID: 2','172.18.0.1',NULL,'2024-12-20 10:47:57'),(29,1,'payment_added','Added payment of RM154.00 (Ref: PAY-20241220-0004) for user ID: 2','172.18.0.1',NULL,'2024-12-20 11:08:26'),(30,1,'payment_added','Payment added: RM33.00 via Cash (Ref: PAY-20241220-194924-988)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 11:49:29'),(31,1,'payment_added','Payment added: RM300.00 via Cash (Ref: PAY-20241220-195018-694)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 11:50:30'),(32,1,'payment_added','Payment added: RM322.00 via Cash (Ref: PAY-20241220-195405-233)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 11:54:18'),(33,1,'payment_added','Payment added: RM323.00 via Cash (Ref: PAY-20241220-195427-309)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 11:54:38'),(34,1,'payment_added','Payment added: RM550.00 via Cash (Ref: PAY-20241220-195834-135)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 11:58:41'),(35,1,'payment_added','Payment added: RM400.00 via Cash (Ref: PAY-20241220-200058-649)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 12:01:12'),(36,1,'payment_added','Payment added: RM20.00 via Cash (Ref: PAY-20241220-200247-464)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 12:02:57'),(37,1,'payment_added','Payment added: RM20.00 via Cash (Ref: PAY-20241220-200704-870)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 12:07:15'),(38,1,'payment_added','Payment added: RM120.00 via Cash (Ref: PAY-20241220-204710-060)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 12:47:18'),(39,1,'payment_added','Payment added for user ID 3: RM125.00 via Cash (Ref: PAY-20241220-213629-790)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 13:36:38'),(40,1,'payment_added','Payment added for user ID 3: RM133.00 via Cash (Ref: PAY-20241220-213929-431)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 13:39:39'),(41,1,'payment_added','Payment added for user ID 3: RM192.45 via Cash (Ref: PAY-20241220-214205-215)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 13:42:35'),(42,1,'payment_added','Payment added for user ID 3: RM980.00 via Cash (Ref: PAY-20241220-214924-021)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 13:49:37'),(43,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 14:24:25'),(44,1,'payment_added','Payment added for user ID 3: RM68.90 via Cash (Ref: PAY-20241220-230234-206)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:02:57'),(45,1,'payment_added','Payment added for user ID 3: RM88.50 via Cards (Ref: PAY-20241220-230617-835)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:06:44'),(46,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:16:20'),(47,1,'payment_added','Payment added for user ID 2: RM201.40 via QR (Ref: PAY-20241220-231904-181)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:19:27'),(48,1,'payment_added','Payment added for user ID 2: RM1209.80 via Transfer (Ref: PAY-20241220-232214-807)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:22:54'),(49,1,'payment_added','Payment added for user ID 3: RM500.00 via Cards (Ref: PAY-20241220-234619-687)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:46:44'),(50,1,'payment_added','Payment added for user ID 3: RM350.00 via Cash (Ref: PAY-20241220-235452-102)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-20 15:55:11'),(51,1,'edit_user','Updated user: MEM001','172.18.0.1',NULL,'2024-12-21 06:59:56'),(52,1,'payment_added','Payment added for user ID 3: RM230.00 via Transfer (Ref: PAY-20241221-160440-057)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 08:04:56'),(53,1,'payment_added','Payment added for user ID 3: RM400.00 via QR (Ref: PAY-20241221-160548-100)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 08:06:04'),(54,1,'payment_added','Payment added for user ID 2: RM350.50 via Cards (Ref: PAY-20241221-161011-558)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 08:10:27'),(55,1,'payment_added','Payment added for user ID 2: RM588.00 via Cash (Ref: PAY-20241221-161039-850)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 08:10:47'),(56,1,'update_user_status','Status updated','172.18.0.1',NULL,'2024-12-21 08:11:30'),(57,1,'update_user_status','Status updated','172.18.0.1',NULL,'2024-12-21 08:11:42'),(58,1,'payment_added','Payment added for user ID 2: RM429.50 via Cash (Ref: PAY-20241221-212614-624)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 13:26:28'),(59,1,'login','User logged in successfully','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-21 13:46:26'),(60,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 13:48:41'),(61,1,'update_user_status','Status updated','172.18.0.1',NULL,'2024-12-21 13:49:20'),(62,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 13:51:06'),(63,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 13:57:26'),(64,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 14:23:20'),(65,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:22:17'),(66,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:25:01'),(67,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:26:11'),(68,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:27:12'),(69,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:32:50'),(70,2,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-21 15:33:28'),(71,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-22 14:53:28'),(72,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-22 14:54:25'),(73,2,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-22 14:55:07'),(74,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-23 00:26:31'),(75,4,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-23 00:26:49'),(76,1,'login','User logged in successfully','172.18.0.1',NULL,'2024-12-23 00:29:13'),(77,1,'payment_added','Payment added for user ID 4: RM50.00 via Cash (Ref: PAY-20241223-110353-851)','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-23 03:04:07'),(78,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-23 15:40:40'),(79,4,'login','User logged in successfully','115.164.170.108',NULL,'2024-12-24 03:05:42'),(80,4,'login','User logged in successfully','115.164.170.108',NULL,'2024-12-24 05:29:22'),(81,4,'login','User logged in successfully','115.164.188.9',NULL,'2024-12-24 08:22:41'),(82,4,'login','User logged in successfully','115.164.188.9',NULL,'2024-12-24 13:11:14'),(83,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 13:12:51'),(84,1,'payment_added','Payment added for user ID 4: RM100.00 via Cash (Ref: PAY-20241224-211434-480)','175.137.255.20','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-24 13:14:39'),(85,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 17:19:05'),(86,4,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 17:21:36'),(87,4,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 18:03:46'),(88,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 18:08:28'),(89,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 18:16:08'),(90,1,'payment_added','Payment added for user ID 4: RM20.00 via Cash (Ref: PAY-20241225-021804-058)','175.137.255.20','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2024-12-24 18:18:08'),(91,4,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 18:22:45'),(92,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-24 18:46:14'),(93,4,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-25 00:25:57'),(94,4,'login','User logged in successfully','115.164.174.45',NULL,'2024-12-25 03:44:31'),(95,4,'login','User logged in successfully','183.171.30.167',NULL,'2024-12-26 10:47:19'),(96,1,'login','User logged in successfully','175.137.255.20',NULL,'2024-12-26 11:31:05'),(97,4,'login','User logged in successfully','183.171.30.167',NULL,'2024-12-26 12:40:36');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('payment_due','payment_received','loan_approved','loan_rejected','system') NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `status`, `created_at`, `read_at`) VALUES (1,2,'Payment Due Reminder','Your payment of $500.00 is due on February 15, 2024','payment_due','unread','2024-12-19 03:41:32',NULL),(2,3,'Payment Due Reminder','Your payment of $300.00 is due on February 15, 2024','payment_due','unread','2024-12-19 03:41:32',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','QR','Transfer','Cards') NOT NULL DEFAULT 'Cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` (`id`, `user_id`, `amount`, `payment_method`, `reference_number`, `transaction_id`, `notes`, `created_at`, `updated_at`, `payment_date`) VALUES (26,3,230.00,'Transfer','PAY-20241221-160440-057',NULL,'this is transfer tau','2024-12-21 08:04:56','2024-12-21 08:04:56','2024-12-21'),(27,3,400.00,'QR','PAY-20241221-160548-100',NULL,'bayar je qr','2024-12-21 08:06:03','2024-12-21 08:06:03','2024-03-21'),(28,2,350.50,'Cards','PAY-20241221-161011-558',NULL,'dah','2024-12-21 08:10:27','2024-12-21 08:10:27','2024-12-21'),(29,2,588.00,'Cash','PAY-20241221-161039-850',NULL,'','2024-12-21 08:10:47','2024-12-21 08:10:47','2024-05-21'),(30,2,429.50,'Cash','PAY-20241221-212614-624',NULL,'','2024-12-21 13:26:28','2024-12-21 13:26:28','2024-03-21'),(31,4,50.00,'Cash','PAY-20241223-110353-851',NULL,'bayar','2024-12-23 03:04:07','2024-12-23 03:04:07','2024-12-23'),(32,4,100.00,'Cash','PAY-20241224-211434-480',NULL,'','2024-12-24 13:14:39','2024-12-24 13:14:39','2024-12-24'),(33,4,20.00,'Cash','PAY-20241225-021804-058',NULL,'','2024-12-24 18:18:08','2024-12-24 18:18:08','2024-12-24');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('Loan','Purchase','Cash','QR','Transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_transaction` datetime DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(30) NOT NULL COMMENT 'Format: TRXYYYYMMdd-HHMMSS-nnn',
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `description`, `created_at`, `updated_at`, `date_transaction`, `image_path`, `transaction_id`) VALUES (24,2,'Purchase',50.00,'kasjdkasdkaj','2024-12-22 02:24:24','2024-12-22 02:24:24','2024-12-22 00:00:00','','TRX20241222-022254-670'),(25,2,'Purchase',70.00,'kasjkajaksdj','2024-12-22 02:25:12','2024-12-22 02:25:12','2024-04-22 00:00:00','uploads/receipts/TRX20241222-022426-442.jpg','TRX20241222-022426-442'),(26,2,'Purchase',76.00,'ashdashdjh','2024-12-22 02:27:37','2024-12-22 02:27:37','2024-12-22 00:00:00','','TRX20241222-022727-070'),(27,2,'Purchase',87.00,'werwrwrwr','2024-12-22 02:28:04','2024-12-22 02:28:04','2024-03-04 00:00:00','','TRX20241222-022740-080'),(28,2,'Purchase',45.00,'4545dgdfg','2024-12-22 02:28:43','2024-12-22 02:28:43','2024-12-22 00:00:00','','TRX20241222-022807-539'),(29,2,'Purchase',34.00,'dsdfs sdsdf','2024-12-22 02:29:54','2024-12-22 02:29:54','2024-12-22 00:00:00','','TRX20241222-022945-310'),(30,2,'Purchase',67.00,'asdasda','2024-12-22 03:15:36','2024-12-22 03:15:36','2024-02-22 00:00:00','uploads/receipts/TRX20241222-031514-529.jpg','TRX20241222-031514-529'),(31,2,'Cash',90.00,'minta cash','2024-12-23 00:26:08','2024-12-23 00:26:08','2024-12-23 00:00:00','','TRX20241223-002541-593'),(32,4,'QR',89.00,'QR tukar cash','2024-12-23 00:28:53','2024-12-23 00:28:53','2024-12-23 00:00:00','uploads/receipts/TRX20241223-002651-109.jpg','TRX20241223-002651-109'),(33,4,'Purchase',20.00,'Sugar','2024-12-24 03:07:04','2024-12-24 03:07:04','2024-12-24 00:00:00','uploads/receipts/TRX20241224-030542-870.jpg','TRX20241224-030542-870'),(34,4,'Purchase',25.00,'Trhi nhi','2024-12-24 05:30:30','2024-12-24 05:30:30','2024-12-24 00:00:00','uploads/receipts/TRX20241224-052922-574.jpg','TRX20241224-052922-574'),(35,4,'Purchase',33.00,'Baju','2024-12-24 05:31:11','2024-12-24 05:31:11','2024-12-24 00:00:00','uploads/receipts/TRX20241224-053049-928.jpg','TRX20241224-053049-928'),(36,4,'Purchase',6.50,'Ais','2024-12-24 05:33:05','2024-12-24 05:33:05','2024-12-24 00:00:00','uploads/receipts/TRX20241224-053239-807.jpg','TRX20241224-053239-807'),(37,4,'Purchase',11.00,'Water ','2024-12-24 05:34:42','2024-12-24 05:34:42','2024-12-24 00:00:00','uploads/receipts/TRX20241224-053330-235.jpg','TRX20241224-053330-235'),(38,4,'Purchase',25.00,'Minirel','2024-12-24 08:23:22','2024-12-24 08:23:22','2024-10-24 00:00:00','uploads/receipts/TRX20241224-082241-142.jpg','TRX20241224-082241-142'),(39,4,'Purchase',52.00,'Tepung','2024-12-24 08:25:18','2024-12-24 08:25:18','2024-12-24 00:00:00','','TRX20241224-082356-265'),(40,4,'Purchase',12.00,'-','2024-12-24 17:43:28','2024-12-24 17:43:28','2024-12-25 00:00:00','','TRX20241224-174317-882'),(41,4,'Purchase',23.00,'-','2024-12-24 17:45:15','2024-12-24 17:45:15','2024-12-25 00:00:00','','TRX20241224-174511-981'),(42,4,'Purchase',34.00,'-','2024-12-24 17:46:28','2024-12-24 17:46:28','2024-12-25 00:00:00','uploads/receipts/TRX20241224-174610-372.png','TRX20241224-174610-372'),(43,4,'Purchase',35.00,'-','2024-12-24 18:04:20','2024-12-24 18:04:20','2024-12-25 00:00:00','uploads/receipts/TRX20241224-180346-797.jpg','TRX20241224-180346-797'),(44,4,'Purchase',2.50,'-','2024-12-24 18:05:06','2024-12-24 18:05:06','2024-12-25 00:00:00','uploads/receipts/TRX20241224-180434-262.jpg','TRX20241224-180434-262'),(45,4,'Purchase',11.90,'-','2024-12-24 18:07:56','2024-12-24 18:07:56','2024-12-25 02:07:00','uploads/receipts/TRX20241224-180728-290.jpg','TRX20241224-180728-290'),(46,4,'Purchase',2.80,'-','2024-12-24 18:14:09','2024-12-24 18:14:09','2024-12-25 02:13:00','uploads/receipts/TRX20241224-181348-442.jpg','TRX20241224-181348-442'),(47,4,'Purchase',21.35,'-sugar','2024-12-24 18:30:45','2024-12-24 18:30:45','2024-12-25 02:30:00','uploads/receipts/TRX20241224-183003-429.jpg','TRX20241224-183003-429'),(48,4,'Purchase',3.69,'-','2024-12-24 18:56:13','2024-12-24 18:56:13','2024-12-25 02:56:00','','TRX20241224-185606-909'),(49,4,'Purchase',24.00,'-','2024-12-25 00:26:54','2024-12-25 00:26:54','2024-12-25 08:25:00','','TRX20241225-002557-303'),(50,4,'Purchase',3.50,'-topup','2024-12-25 00:27:18','2024-12-25 00:27:18','2024-12-25 08:27:00','','TRX20241225-002702-843');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_id` (`member_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `member_id`, `full_name`, `password`, `email`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES (1,'ADMIN001','System Administrator','$2y$10$4voDkFjSTDtOn3hbeW2dKuOvN9iXVWIkUCvnGwV6191HDjlH1RQEu','admin@example.com',NULL,'admin','active','2024-12-19 03:41:32','2024-12-19 05:36:49'),(2,'MEM001','John Doey','$2y$10$X/FEotT6LLQdm6YV/TRfe.RELHyBieS98PX8fBRU4ekVgdrOVCqBK','john@example.com','1234567890','user','active','2024-12-19 03:41:32','2024-12-21 15:33:13'),(3,'MEM002','Jane Smith','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','jane@example.com','0987654321','user','active','2024-12-19 03:41:32','2024-12-19 03:41:32'),(4,'67','Nefizon','$2y$10$0To8WwPBrwEerWK9jTtpJ.j1O9M5ifD2Eyjibd1t6kcqKuKFEjaDW','nefizon@gmail.com','0169281036','user','active','2024-12-21 13:41:37','2024-12-25 03:45:04'),(6,'nefizon','Nefizon','$2y$10$AtOO54ENy8fhkk1B8qK2geD7PX2LpS.0aMkp.2a9xrHhZ1DXc6F3e','nefizon@outlook.com','0136321806','admin','active','2024-12-24 18:15:08','2024-12-24 18:15:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
