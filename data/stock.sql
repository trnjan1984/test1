-- --------------------------------------------------------
-- Poslužitelj:                  127.0.0.1
-- Server version:               5.7.14 - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Verzija:             9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table foreo.stock
CREATE TABLE IF NOT EXISTS `stock` (
  `stock_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created` date NOT NULL,
  UNIQUE KEY `stock_id` (`stock_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Dumping data for table foreo.stock: 1 rows
/*!40000 ALTER TABLE `stock` DISABLE KEYS */;
INSERT INTO `stock` (`stock_id`, `product_id`, `product_name`, `quantity`, `type`, `created`) VALUES
	(1, 1, 'product1', 5, 'CC order', '2017-02-01'),
	(2, 1, 'product1', 3, 'CC order', '2017-02-07'),
	(3, 3, 'product3', 3, 'CC order', '2017-02-07'),
	(4, 3, 'product3', 3, 'CC order', '2017-02-07'),
	(5, 4, 'product4', 4, 'CC order', '2017-02-07'),
	(6, 4, 'product4', 3, 'CC order', '2017-02-07'),
	(7, 2, 'product2', 55, 'CC order', '2017-02-07'),
	(8, 2, 'product2', 33, 'CC order', '2017-02-07'),
	(100, 10, 'FOREO Luna', 100, 'CC Order', '2016-03-04'),
	(110, 13, 'FOREO Issa', 200, 'OS Order', '2016-03-04'),
	(120, 15, 'FOREO Iris', 150, 'Other', '2016-03-04'),
	(130, 16, 'FOREO Iris', 200, 'CC Order', '2016-03-05'),
	(140, 17, 'FOREO Iris', 225, 'CC Order', '2016-03-06'),
	(150, 18, 'FOREO Iris', 250, 'CC Order', '2016-03-07'),
	(160, 19, 'FOREO Iris', 275, 'CC Order', '2016-03-08'),
	(170, 20, 'FOREO Iris', 300, 'CC Order', '2016-03-09'),
	(180, 21, 'FOREO Iris', 325, 'CC Order', '2016-03-10'),
	(190, 22, 'FOREO 1', 350, 'CC Order', '2016-03-11'),
	(200, 23, 'FOREO 2', 375, 'CC Order', '2016-03-12'),
	(210, 24, 'FOREO 3', 400, 'CC Order', '2016-03-13'),
	(220, 25, 'FOREO 4', 425, 'CC Order', '2016-03-14'),
	(230, 26, 'FOREO 5', 450, 'CC Order', '2016-03-15'),
	(240, 27, 'FOREO 6', 475, 'CC Order', '2016-03-16'),
	(250, 28, 'FOREO 7', 500, 'CC Order', '2016-03-17'),
	(260, 29, 'FOREO 8', 525, 'CC Order', '2016-03-18'),
	(270, 30, 'FOREO 9', 550, 'Other', '2016-03-19'),
	(280, 31, 'FOREO 10', 575, 'Other', '2016-03-20'),
	(290, 32, 'FOREO 11', 600, 'Other', '2016-03-21'),
	(300, 33, 'FOREO 12', 625, 'Other', '2016-03-22'),
	(310, 34, 'FOREO 13', 650, 'Other', '2016-03-23'),
	(320, 35, 'FOREO 14', 675, 'Other', '2016-03-24'),
	(330, 36, 'FOREO 15', 700, 'Other', '2016-03-25'),
	(340, 37, 'FOREO 16', 725, 'Other', '2016-03-26'),
	(350, 38, 'FOREO 17', 750, 'Other', '2016-03-27'),
	(360, 39, 'FOREO 18', 775, 'Other', '2016-03-28'),
	(370, 40, 'FOREO 19', 800, 'Other', '2016-03-29'),
	(380, 41, 'FOREO 20', 825, 'Other', '2016-03-30'),
	(390, 42, 'FOREO 21', 850, 'OS Order', '2016-04-01'),
	(400, 43, 'FOREO 22', 875, 'OS Order', '2016-04-02'),
	(410, 44, 'FOREO 23', 900, 'OS Order', '2016-04-03'),
	(420, 45, 'FOREO 24', 925, 'OS Order', '2016-04-04'),
	(430, 46, 'FOREO 25', 950, 'OS Order', '2016-04-05'),
	(440, 47, 'FOREO 26', 975, 'OS Order', '2016-04-06'),
	(450, 48, 'FOREO 27', 1000, 'OS Order', '2016-04-07'),
	(460, 49, 'FOREO 28', 1025, 'OS Order', '2016-04-08'),
	(470, 50, 'FOREO 29', 1050, 'OS Order', '2016-04-09'),
	(480, 51, 'FOREO 30', 1075, 'OS Order', '2016-04-10'),
	(490, 52, 'FOREO 31', 1100, 'OS Order', '2016-04-11'),
	(500, 53, 'FOREO 32', 1125, 'OS Order', '2016-04-12'),
	(510, 54, 'FOREO 33', 1150, 'OS Order', '2016-04-13'),
	(520, 55, 'FOREO 34', 1175, 'OS Order', '2016-04-14'),
	(530, 56, 'FOREO 35', 1200, 'OS Order', '2016-04-15'),
	(540, 57, 'FOREO 36', 1225, 'OS Order', '2016-04-16'),
	(550, 58, 'FOREO 37', 1250, 'OS Order', '2016-04-17'),
	(560, 59, 'FOREO 38', 1275, 'OS Order', '2016-04-18'),
	(570, 60, 'FOREO 39', 1300, 'OS Order', '2016-04-19'),
	(580, 61, 'FOREO 40', 1325, 'OS Order', '2016-04-20'),
	(590, 62, 'FOREO 41', 1350, 'OS Order', '2016-04-21'),
	(600, 63, 'FOREO 42', 1375, 'OS Order', '2016-04-22'),
	(610, 64, 'FOREO 43', 1400, 'OS Order', '2016-04-23'),
	(620, 65, 'FOREO 44', 1425, 'OS Order', '2016-04-24'),
	(630, 66, 'FOREO 45', 1450, 'OS Order', '2016-04-25'),
	(640, 67, 'FOREO 46', 1475, 'OS Order', '2016-04-26'),
	(650, 68, 'FOREO 47', 1500, 'OS Order', '2016-04-27'),
	(660, 69, 'FOREO 48', 1525, 'OS Order', '2016-04-28'),
	(670, 70, 'FOREO 49', 1550, 'OS Order', '2016-04-29'),
	(680, 71, 'FOREO 50', 1575, 'OS Order', '2016-04-30'),
	(690, 72, 'FOREO 51', 1600, 'OS Order', '2016-03-22'),
	(700, 73, 'FOREO 52', 1625, 'OS Order', '2016-03-23'),
	(710, 74, 'FOREO 53', 1650, 'OS Order', '2016-03-24'),
	(720, 75, 'FOREO 54', 1675, 'OS Order', '2016-03-25');
/*!40000 ALTER TABLE `stock` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
