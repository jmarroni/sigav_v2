-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-12-2020 a las 02:34:36
-- Versión del servidor: 10.4.10-MariaDB
-- Versión de PHP: 7.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mercado-artesanal`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_logs`
--

DROP TABLE IF EXISTS `stock_logs`;
CREATE TABLE IF NOT EXISTS `stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_anterior` int(11) DEFAULT NULL,
  `stock_minimo_anterior` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `stock_minimo` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `usuario` varchar(200) DEFAULT NULL,
  `productos_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `tipo_operacion` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `stock_logs`
--

INSERT INTO `stock_logs` (`id`, `stock_anterior`, `stock_minimo_anterior`, `stock`, `stock_minimo`, `sucursal_id`, `usuario`, `productos_id`, `updated_at`, `created_at`, `tipo_operacion`) VALUES
(4, 1, 1, 1, 1, 1, 'jmarroni', 1438, '2020-07-06 23:43:30', '2020-07-06 23:43:30', NULL),
(5, 1, 1, 3, 2, 1, 'jmarroni', 1438, '2020-07-06 23:44:11', '2020-07-06 23:44:11', NULL),
(6, 3, 2, 1000, 67, 1, 'jmarroni', 1438, '2020-07-07 00:05:49', '2020-07-07 00:05:49', NULL),
(7, 9, 1, 12, 1, 2, 'jmarroni', 1439, '2020-07-07 00:06:09', '2020-07-07 00:06:09', NULL),
(8, 12, 1, 12, 2, 2, 'jmarroni', 1439, '2020-07-07 00:07:11', '2020-07-07 00:07:11', NULL),
(9, -1, -1, 56, 32, 2, 'jmarroni', 2875, '2020-07-07 00:07:46', '2020-07-07 00:07:46', NULL),
(10, -1, -1, 1, 0, 1, 'jmarroni', 2330, '2020-07-07 00:08:15', '2020-07-07 00:08:15', NULL),
(11, 2, 1, 2, 1, 1, 'jmarroni', 2394, '2020-07-07 01:12:21', '2020-07-07 01:12:21', NULL),
(12, 0, 0, 0, 0, 1, 'jmarroni', 2390, '2020-07-07 01:12:38', '2020-07-07 01:12:38', NULL),
(13, 2, 1, 2, 22, 1, 'jmarroni', 2394, '2020-07-07 01:44:49', '2020-07-07 01:44:49', NULL),
(14, 0, 0, 0, 0, 1, 'jmarroni', 1482, '2020-07-07 03:30:30', '2020-07-07 03:30:30', NULL),
(15, 0, 0, 0, 0, 1, 'jmarroni', 1482, '2020-07-07 03:34:09', '2020-07-07 03:34:09', NULL),
(16, 0, 0, 33, 2, 1, 'jmarroni', 2390, '2020-07-07 18:36:30', '2020-07-07 18:36:30', NULL),
(17, -1, -1, 0, 0, 1, 'jmarroni', 2875, '2020-07-07 18:49:43', '2020-07-07 18:49:43', NULL),
(18, 700, 1, 700, 1, 1, 'jmarroni', 2874, '2020-07-07 22:10:22', '2020-07-07 22:10:22', NULL),
(19, -1, -1, 10, 11, 1, 'jmarroni', 2, '2020-09-16 11:21:00', '2020-09-16 11:21:00', NULL),
(20, 0, 0, 1, 2, 1, 'jmarroni', 3, '2020-09-16 11:22:10', '2020-09-16 11:22:10', NULL),
(21, 10, 11, 11, 15, 1, 'jmarroni', 2, '2020-09-16 11:22:21', '2020-09-16 11:22:21', NULL),
(22, 0, 2, 4, 1, 1, 'jmarroni', 3, '2020-09-16 11:46:09', '2020-09-16 11:46:09', NULL),
(23, 12, 1, 12, 1, 1, 'jmarroni', 2873, '2020-09-17 12:59:06', '2020-09-17 12:59:06', NULL),
(24, -1, -1, 1, 2, 2, 'jmarroni', 2873, '2020-09-17 12:59:48', '2020-09-17 12:59:48', NULL),
(25, -1, -1, 50, 10, 2, 'jmarroni', 2692, '2020-12-23 03:17:40', '2020-12-23 03:17:40', NULL),
(26, 0, 0, 0, 0, 0, 'ss', 0, NULL, NULL, NULL),
(27, 0, 0, 0, 0, 0, '', 0, NULL, NULL, NULL),
(28, 0, 0, 0, 0, 0, '', 0, NULL, NULL, NULL),
(29, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(30, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(31, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(32, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(33, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(34, 0, 0, 0, 0, 0, '', 1509, NULL, NULL, NULL),
(35, 0, 0, 0, 0, 0, 'ad', 1510, NULL, NULL, NULL),
(36, 0, 0, 0, 0, 0, 'jmarroni', 1511, NULL, NULL, NULL),
(37, 0, 0, 0, 0, 0, 'jmarroni', 2874, '2020-12-23 04:12:38', '2020-12-23 04:12:38', NULL),
(38, 0, 0, 0, 0, 0, 'jmarroni', 2875, '2020-12-24 00:08:42', '2020-12-24 00:08:42', 'Alta'),
(39, 0, 0, 1, 1, 0, 'jmarroni', 1508, '2020-12-24 00:11:48', '2020-12-24 00:11:48', 'Baja'),
(40, 0, 0, 0, 0, 0, 'jmarroni', 1741, '2020-12-24 00:33:19', '2020-12-24 00:33:19', 'Baja'),
(41, 0, 0, 0, 0, 0, 'jmarroni', 1878, '2020-12-24 00:38:01', '2020-12-24 00:38:01', 'Baja'),
(42, 0, 0, 0, 0, 0, 'jmarroni', 1757, '2020-12-24 00:42:33', '2020-12-24 00:42:33', 'Baja'),
(43, 0, 0, 0, 0, 0, 'jmarroni', 2056, '2020-12-24 00:46:00', '2020-12-24 00:46:00', 'Baja'),
(44, 0, 0, 0, 0, 0, 'jmarroni', 2057, '2020-12-24 00:51:08', '2020-12-24 00:51:08', 'Baja'),
(45, 0, 0, 0, 0, 0, 'jmarroni', 2069, '2020-12-24 00:55:35', '2020-12-24 00:55:35', 'Baja'),
(46, 0, 0, 0, 0, 0, 'jmarroni', 1756, '2020-12-24 00:55:40', '2020-12-24 00:55:40', 'Baja'),
(47, 0, 0, 0, 0, 0, 'jmarroni', 1734, '2020-12-24 00:55:45', '2020-12-24 00:55:45', 'Baja'),
(48, 1, 0, 1, 1, 0, 'jmarroni', 1486, '2020-12-24 00:55:50', '2020-12-24 00:55:50', 'Baja'),
(49, 0, 0, 1, 1, 0, 'jmarroni', 1545, '2020-12-24 00:55:55', '2020-12-24 00:55:55', 'Baja'),
(50, 0, 0, 0, 0, 0, 'jmarroni', 2876, '2020-12-24 00:57:45', '2020-12-24 00:57:45', 'Alta'),
(51, -1, -1, 50, 25, 1, 'jmarroni', 2207, '2020-12-24 00:58:38', '2020-12-24 00:58:38', 'Actualización'),
(52, 0, 0, 50, 25, 0, 'jmarroni', 2207, '2020-12-24 02:33:21', '2020-12-24 02:33:21', 'Baja');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
