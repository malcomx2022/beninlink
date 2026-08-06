-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2022 at 12:56 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `we-courier`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` int(11) DEFAULT NULL COMMENT '1=Admin, 2=User',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `gateway` tinyint(4) DEFAULT NULL,
  `balance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `account_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank` tinyint(4) DEFAULT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_balance` decimal(16,2) DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` tinyint(4) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `type`, `user_id`, `gateway`, `balance`, `account_holder_name`, `account_no`, `bank`, `branch_name`, `opening_balance`, `mobile`, `account_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 2, '0.00', 'User', '123654789', 1, 'Dhaka', '0.00', NULL, NULL, 1, '2022-09-24 06:53:28', '2022-09-24 10:35:54'),
(2, 1, 3, 2, '70.00', 'User2', '987456321', 2, 'Mirpur', '0.00', NULL, NULL, 1, '2022-09-24 06:53:28', '2022-09-24 10:36:16'),
(3, 2, 6, 1, '50000.00', NULL, NULL, NULL, NULL, '50000.00', NULL, NULL, 1, '2022-09-24 10:56:19', '2022-09-24 10:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `account_heads`
--

CREATE TABLE `account_heads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` int(11) DEFAULT NULL COMMENT '1=Income, 2=Expense',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_heads`
--

INSERT INTO `account_heads` (`id`, `type`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Payment received from Merchant', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(2, 1, 'Cash received from delivery man', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(3, 1, 'Others', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(4, 2, 'Payment paid to merchant', 0, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(5, 2, 'Commission paid to delivery man', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(6, 2, 'Others', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(7, 1, 'Payment receive from hub', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(1, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 1, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 2, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user2.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 3, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user3.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 4, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user4.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 5, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user5.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 6, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user6.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 7, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user7.png\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(8, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 1, NULL, NULL, '{\"attributes\":{\"name\":\"Mirpur-10\",\"phone\":\"01000000001\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(9, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 2, NULL, NULL, '{\"attributes\":{\"name\":\"Uttara\",\"phone\":\"01000000002\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(10, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 3, NULL, NULL, '{\"attributes\":{\"name\":\"Dhanmundi\",\"phone\":\"01000000003\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(11, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 4, NULL, NULL, '{\"attributes\":{\"name\":\"Old Dhaka\",\"phone\":\"01000000004\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(12, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 5, NULL, NULL, '{\"attributes\":{\"name\":\"Jatrabari\",\"phone\":\"01000000005\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(13, 'Hub', 'created', 'App\\Models\\Backend\\Hub', 'created', 6, NULL, NULL, '{\"attributes\":{\"name\":\"Badda\",\"phone\":\"01000000006\",\"address\":\"Dhaka, Bangladesh\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(14, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 1, NULL, NULL, '{\"attributes\":{\"title\":\"General Management\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(15, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 2, NULL, NULL, '{\"attributes\":{\"title\":\"Marketing\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(16, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 3, NULL, NULL, '{\"attributes\":{\"title\":\"Operations\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(17, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 4, NULL, NULL, '{\"attributes\":{\"title\":\"Finance\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(18, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 5, NULL, NULL, '{\"attributes\":{\"title\":\"Sales\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(19, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 6, NULL, NULL, '{\"attributes\":{\"title\":\"Human Resource\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(20, 'Department', 'created', 'App\\Models\\Backend\\Department', 'created', 7, NULL, NULL, '{\"attributes\":{\"title\":\"Purchase\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(21, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 1, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Executive Officer (CEO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(22, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 2, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Operating Officer (COO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(23, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 3, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Financial Officer (CFO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(24, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 4, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Technology Officer (CTO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(25, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 5, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Legal Officer (CLO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(26, 'Designation', 'created', 'App\\Models\\Backend\\Designation', 'created', 6, NULL, NULL, '{\"attributes\":{\"title\":\"Chief Marketing Officer (CMO)\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(27, 'Role', 'created', 'App\\Models\\Backend\\Role', 'created', 1, NULL, NULL, '{\"attributes\":{\"name\":\"Super Admin\",\"permissions\":[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(28, 'Role', 'created', 'App\\Models\\Backend\\Role', 'created', 2, NULL, NULL, '{\"attributes\":{\"name\":\"Admin\",\"permissions\":[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"income_read\",\"expense_read\",\"todo_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"general_settings_read\",\"account_heads_read\",\"salary_read\",\"support_read\",\"fund_transfer_read\",\"role_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"sms_settings_status_change\",\"sms_send_settings_status_change\",\"bank_transaction_read\",\"database_backup_read\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(29, 'User', 'created', 'App\\Models\\User', 'created', 1, NULL, NULL, '{\"attributes\":{\"name\":\"Wemaxit\",\"email\":\"admin@wemaxit.com\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(30, 'User', 'created', 'App\\Models\\User', 'created', 2, NULL, NULL, '{\"attributes\":{\"name\":\"User\",\"email\":\"user@gmail.com\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(31, 'User', 'created', 'App\\Models\\User', 'created', 3, NULL, NULL, '{\"attributes\":{\"name\":\"User 2\",\"email\":\"user2@gmail.com\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(32, 'User', 'created', 'App\\Models\\User', 'created', 4, NULL, NULL, '{\"attributes\":{\"name\":\"Delivery Man\",\"email\":\"deliveryman@wemaxit.com\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(33, 'DeliveryMan', 'created', 'App\\Models\\Backend\\DeliveryMan', 'created', 1, NULL, NULL, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"0.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(34, 'InCharges', 'created', 'App\\Models\\Backend\\HubInCharge', 'created', 1, NULL, NULL, '{\"attributes\":{\"user.name\":\"User\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(35, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 1, NULL, NULL, '{\"attributes\":{\"title\":\"KG\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(36, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 2, NULL, NULL, '{\"attributes\":{\"title\":\"Mobile\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(37, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 3, NULL, NULL, '{\"attributes\":{\"title\":\"Laptop\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(38, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 4, NULL, NULL, '{\"attributes\":{\"title\":\"Tabs\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(39, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 5, NULL, NULL, '{\"attributes\":{\"title\":\"Gaming Kybord\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(40, 'Deliverycategory', 'created', 'App\\Models\\Backend\\Deliverycategory', 'created', 6, NULL, NULL, '{\"attributes\":{\"title\":\"Cosmetices\",\"description\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(41, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 1, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":1,\"same_day\":\"50.00\",\"next_day\":\"60.00\",\"sub_city\":\"70.00\",\"outside_city\":\"80.00\",\"position\":1}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(42, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 2, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":2,\"same_day\":\"90.00\",\"next_day\":\"100.00\",\"sub_city\":\"110.00\",\"outside_city\":\"120.00\",\"position\":2}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(43, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 3, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":3,\"same_day\":\"130.00\",\"next_day\":\"140.00\",\"sub_city\":\"150.00\",\"outside_city\":\"160.00\",\"position\":3}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(44, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 4, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":4,\"same_day\":\"170.00\",\"next_day\":\"180.00\",\"sub_city\":\"190.00\",\"outside_city\":\"200.00\",\"position\":4}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(45, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 5, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":5,\"same_day\":\"210.00\",\"next_day\":\"220.00\",\"sub_city\":\"230.00\",\"outside_city\":\"240.00\",\"position\":5}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(46, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 6, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":6,\"same_day\":\"250.00\",\"next_day\":\"260.00\",\"sub_city\":\"270.00\",\"outside_city\":\"280.00\",\"position\":6}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(47, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 7, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":7,\"same_day\":\"290.00\",\"next_day\":\"300.00\",\"sub_city\":\"310.00\",\"outside_city\":\"320.00\",\"position\":7}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(48, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 8, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":8,\"same_day\":\"340.00\",\"next_day\":\"350.00\",\"sub_city\":\"360.00\",\"outside_city\":\"370.00\",\"position\":8}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(49, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 9, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":9,\"same_day\":\"380.00\",\"next_day\":\"390.00\",\"sub_city\":\"400.00\",\"outside_city\":\"410.00\",\"position\":9}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(50, 'DeliveryCharge', 'created', 'App\\Models\\Backend\\DeliveryCharge', 'created', 10, NULL, NULL, '{\"attributes\":{\"category.name\":null,\"weight\":10,\"same_day\":\"420.00\",\"next_day\":\"430.00\",\"sub_city\":\"440.00\",\"outside_city\":\"450.00\",\"position\":10}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(51, 'User', 'created', 'App\\Models\\User', 'created', 5, NULL, NULL, '{\"attributes\":{\"name\":\"Merchant\",\"email\":\"merchant@wemaxit.com\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(52, 'Merchant', 'created', 'App\\Models\\Backend\\Merchant', 'created', 1, NULL, NULL, '{\"attributes\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"0.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(53, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 1, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":1,\"same_day\":\"50.00\",\"next_day\":\"60.00\",\"sub_city\":\"70.00\",\"outside_city\":\"80.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(54, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 2, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":2,\"same_day\":\"90.00\",\"next_day\":\"100.00\",\"sub_city\":\"110.00\",\"outside_city\":\"120.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(55, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 3, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":3,\"same_day\":\"130.00\",\"next_day\":\"140.00\",\"sub_city\":\"150.00\",\"outside_city\":\"160.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(56, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 4, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":4,\"same_day\":\"170.00\",\"next_day\":\"180.00\",\"sub_city\":\"190.00\",\"outside_city\":\"200.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(57, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 5, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":5,\"same_day\":\"210.00\",\"next_day\":\"220.00\",\"sub_city\":\"230.00\",\"outside_city\":\"240.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(58, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 6, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":6,\"same_day\":\"250.00\",\"next_day\":\"260.00\",\"sub_city\":\"270.00\",\"outside_city\":\"280.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(59, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 7, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":7,\"same_day\":\"290.00\",\"next_day\":\"300.00\",\"sub_city\":\"310.00\",\"outside_city\":\"320.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(60, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 8, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":8,\"same_day\":\"340.00\",\"next_day\":\"350.00\",\"sub_city\":\"360.00\",\"outside_city\":\"370.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(61, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 9, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":9,\"same_day\":\"380.00\",\"next_day\":\"390.00\",\"sub_city\":\"400.00\",\"outside_city\":\"410.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(62, 'MerchantDeliveryCharge', 'created', 'App\\Models\\Backend\\MerchantDeliveryCharge', 'created', 10, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"deliveryCharge.category.title\":\"KG\",\"weight\":10,\"same_day\":\"420.00\",\"next_day\":\"430.00\",\"sub_city\":\"440.00\",\"outside_city\":\"450.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(63, 'MerchantShops', 'created', 'App\\Models\\MerchantShops', 'created', 1, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"name\":\"Shop 1\",\"contact_no\":\"+88013000000\",\"address\":\"wemaxit,Dhaka\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(64, 'MerchantShops', 'created', 'App\\Models\\MerchantShops', 'created', 2, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"name\":\"Shop 2\",\"contact_no\":\"+88013000000\",\"address\":\"wemaxit,Dhaka\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(65, 'MerchantShops', 'created', 'App\\Models\\MerchantShops', 'created', 3, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"name\":\"Shop 3\",\"contact_no\":\"+88013000000\",\"address\":\"wemaxit,Dhaka\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(66, 'MerchantShops', 'created', 'App\\Models\\MerchantShops', 'created', 4, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"name\":\"Shop 4\",\"contact_no\":\"+88013000000\",\"address\":\"wemaxit,Dhaka\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(67, 'MerchantShops', 'created', 'App\\Models\\MerchantShops', 'created', 5, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"name\":\"Shop 5\",\"contact_no\":\"+88013000000\",\"address\":\"wemaxit,Dhaka\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(68, 'MerchantPayment', 'created', 'App\\Models\\MerchantPayment', 'created', 1, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"payment_method\":\"bank\",\"bank_name\":\"NRB Commercial Bank Ltd.\",\"holder_name\":\"Marchant\",\"account_no\":\"123456\",\"branch_name\":\"Dhaka branch\",\"routing_no\":\"123456\",\"mobile_company\":null,\"mobile_no\":null,\"account_type\":null}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(69, 'MerchantPayment', 'created', 'App\\Models\\MerchantPayment', 'created', 2, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"payment_method\":\"mobile\",\"bank_name\":null,\"holder_name\":\"Marchant\",\"account_no\":null,\"branch_name\":null,\"routing_no\":null,\"mobile_company\":\"Bkash\",\"mobile_no\":\"01300000000\",\"account_type\":\"Personal\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(70, 'MerchantPayment', 'created', 'App\\Models\\MerchantPayment', 'created', 3, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"payment_method\":\"mobile\",\"bank_name\":null,\"holder_name\":\"Marchant\",\"account_no\":null,\"branch_name\":null,\"routing_no\":null,\"mobile_company\":\"Nagad\",\"mobile_no\":\"01300000000\",\"account_type\":\"Personal\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(71, 'MerchantPayment', 'created', 'App\\Models\\MerchantPayment', 'created', 4, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"payment_method\":\"mobile\",\"bank_name\":null,\"holder_name\":\"Marchant\",\"account_no\":null,\"branch_name\":null,\"routing_no\":null,\"mobile_company\":\"Rocket\",\"mobile_no\":\"01300000000\",\"account_type\":\"Personal\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(72, 'Account', 'created', 'App\\Models\\Backend\\Account', 'created', 1, NULL, NULL, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(73, 'Account', 'created', 'App\\Models\\Backend\\Account', 'created', 2, NULL, NULL, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(74, 'Config', 'created', 'App\\Models\\Config', 'created', 1, NULL, NULL, '{\"attributes\":{\"key\":\"fragile_liquid_status\",\"value\":\"1\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(75, 'Config', 'created', 'App\\Models\\Config', 'created', 2, NULL, NULL, '{\"attributes\":{\"key\":\"fragile_liquid_charge\",\"value\":\"20\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(76, 'Config', 'created', 'App\\Models\\Config', 'created', 3, NULL, NULL, '{\"attributes\":{\"key\":\"same_day\",\"value\":\"1\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(77, 'Config', 'created', 'App\\Models\\Config', 'created', 4, NULL, NULL, '{\"attributes\":{\"key\":\"next_day\",\"value\":\"1\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(78, 'Config', 'created', 'App\\Models\\Config', 'created', 5, NULL, NULL, '{\"attributes\":{\"key\":\"sub_city\",\"value\":\"1\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(79, 'Config', 'created', 'App\\Models\\Config', 'created', 6, NULL, NULL, '{\"attributes\":{\"key\":\"outside_City\",\"value\":\"1\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(80, 'Packaging', 'created', 'App\\Models\\Backend\\Packaging', 'created', 1, NULL, NULL, '{\"attributes\":{\"name\":\"Poly\",\"price\":\"10.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(81, 'Packaging', 'created', 'App\\Models\\Backend\\Packaging', 'created', 2, NULL, NULL, '{\"attributes\":{\"name\":\"Bubble Poly\",\"price\":\"20.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(82, 'Packaging', 'created', 'App\\Models\\Backend\\Packaging', 'created', 3, NULL, NULL, '{\"attributes\":{\"name\":\"Box\",\"price\":\"30.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(83, 'Packaging', 'created', 'App\\Models\\Backend\\Packaging', 'created', 4, NULL, NULL, '{\"attributes\":{\"name\":\"Box Poly\",\"price\":\"40.00\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(84, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 1, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 0\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(85, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 2, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 1\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(86, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 3, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 2\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(87, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 4, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 3\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(88, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 5, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 4\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(89, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 6, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 5\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(90, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 7, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 6\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(91, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 8, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 7\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(92, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 9, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 8\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(93, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 10, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 9\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(94, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 11, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 10\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(95, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 12, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 11\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(96, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 13, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 12\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(97, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 14, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 13\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(98, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 15, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 14\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(99, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 16, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 15\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(100, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 17, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 16\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(101, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 18, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 17\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(102, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 19, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 18\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(103, 'parcel', 'created', 'App\\Models\\Backend\\Parcel', 'created', 20, NULL, NULL, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(104, 'smsSettings', 'created', 'App\\Models\\Backend\\SmsSetting', 'created', 1, NULL, NULL, '{\"attributes\":{\"api_key\":\"a7e4166cc9967d80\",\"secret_key\":\"e863dd2f\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(105, 'SmsSendSetting', 'created', 'App\\Models\\Backend\\SmsSendSetting', 'created', 1, NULL, NULL, '{\"attributes\":{\"sms_send_status\":1}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(106, 'SmsSendSetting', 'created', 'App\\Models\\Backend\\SmsSendSetting', 'created', 2, NULL, NULL, '{\"attributes\":{\"sms_send_status\":2}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(107, 'SmsSendSetting', 'created', 'App\\Models\\Backend\\SmsSendSetting', 'created', 3, NULL, NULL, '{\"attributes\":{\"sms_send_status\":3}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(108, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 8, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user8.png\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(109, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 9, NULL, NULL, '{\"attributes\":{\"original\":\"uploads\\/users\\/user9.png\"}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(110, 'General Settings', 'created', 'App\\Models\\Backend\\GeneralSettings', 'created', 1, NULL, NULL, '{\"attributes\":{\"phone\":\"20022002\",\"name\":\"WE COURIER\",\"tracking_id\":null,\"details\":null}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(111, 'Salary Generate', 'created', 'App\\Models\\Backend\\Payroll\\SalaryGenerate', 'created', 1, NULL, NULL, '{\"attributes\":{\"user.name\":\"Wemaxit\",\"month\":\"2022-09\",\"amount\":\"7000.00\",\"due\":\"0.00\",\"advance\":\"0.00\",\"note\":null}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(112, 'Salary Generate', 'created', 'App\\Models\\Backend\\Payroll\\SalaryGenerate', 'created', 2, NULL, NULL, '{\"attributes\":{\"user.name\":\"User\",\"month\":\"2022-09\",\"amount\":\"9000.00\",\"due\":\"0.00\",\"advance\":\"0.00\",\"note\":null}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(113, 'Salary Generate', 'created', 'App\\Models\\Backend\\Payroll\\SalaryGenerate', 'created', 3, NULL, NULL, '{\"attributes\":{\"user.name\":\"User 2\",\"month\":\"2022-09\",\"amount\":\"7000.00\",\"due\":\"0.00\",\"advance\":\"0.00\",\"note\":null}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(114, 'Salary Generate', 'created', 'App\\Models\\Backend\\Payroll\\SalaryGenerate', 'created', 4, NULL, NULL, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"month\":\"2022-09\",\"amount\":\"7000.00\",\"due\":\"0.00\",\"advance\":\"0.00\",\"note\":null}}', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(115, 'parcel', 'updated', 'App\\Models\\Backend\\Parcel', 'updated', 20, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"},\"old\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 07:01:53', '2022-09-24 07:01:53'),
(116, 'DeliveryMan', 'updated', 'App\\Models\\Backend\\DeliveryMan', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"20.00\"},\"old\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"0.00\"}}', NULL, '2022-09-24 07:02:00', '2022-09-24 07:02:00'),
(117, 'parcel', 'updated', 'App\\Models\\Backend\\Parcel', 'updated', 20, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"},\"old\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 07:02:00', '2022-09-24 07:02:00'),
(118, 'parcel', 'updated', 'App\\Models\\Backend\\Parcel', 'updated', 20, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"},\"old\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 07:02:05', '2022-09-24 07:02:05'),
(119, 'DeliveryMan', 'updated', 'App\\Models\\Backend\\DeliveryMan', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"50.00\"},\"old\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"20.00\"}}', NULL, '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(120, 'DeliveryMan', 'updated', 'App\\Models\\Backend\\DeliveryMan', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"-450.00\"},\"old\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"50.00\"}}', NULL, '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(121, 'Merchant', 'updated', 'App\\Models\\Backend\\Merchant', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"500.00\"},\"old\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"0.00\"}}', NULL, '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(122, 'Merchant', 'updated', 'App\\Models\\Backend\\Merchant', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"384.00\"},\"old\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"500.00\"}}', NULL, '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(123, 'parcel', 'updated', 'App\\Models\\Backend\\Parcel', 'updated', 20, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"},\"old\":{\"merchant.business_name\":\"Wemaxit\",\"pickup_address\":\"Mirpur-02\",\"pickup_phone\":\"01478523698\",\"customer_name\":\"Abdullah 19\",\"customer_phone\":\"01478523655\",\"customer_address\":\"Mirpur-10\",\"invoice_no\":\"123654\",\"cash_collection\":\"500.00\",\"selling_price\":\"500.00\",\"delivery_charge\":\"50.00\",\"total_delivery_amount\":\"110.50\",\"current_payable\":\"389.50\"}}', NULL, '2022-09-24 07:02:09', '2022-09-24 07:02:09'),
(124, 'Merchant', 'updated', 'App\\Models\\Backend\\Merchant', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"564.00\"},\"old\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"384.00\"}}', NULL, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(125, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 10, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(126, 'Income', 'created', 'App\\Models\\Backend\\Income', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"parcel.tracking_id\":null,\"account.account_no\":\"987456321\",\"amount\":\"180.00\",\"date\":\"2022-09-24\",\"receipt\":10,\"note\":\"\"}}', NULL, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(127, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(128, 'DeliveryMan', 'updated', 'App\\Models\\Backend\\DeliveryMan', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"-350.00\"},\"old\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"-450.00\"}}', NULL, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(129, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 11, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(130, 'Income', 'created', 'App\\Models\\Backend\\Income', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":null,\"parcel.tracking_id\":null,\"account.account_no\":\"123654789\",\"amount\":\"100.00\",\"date\":\"2022-09-24\",\"receipt\":11,\"note\":\"\"}}', NULL, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(131, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(132, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 12, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22'),
(133, 'Expense', 'created', 'App\\Models\\Backend\\Expense', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":null,\"deliveryman.user.name\":\"Delivery Man\",\"parcel.tracking_id\":null,\"account.account_no\":\"987456321\",\"amount\":\"100.00\",\"date\":\"2022-09-24\",\"receipt\":12,\"note\":\"\"}}', NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22'),
(134, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22');
INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(135, 'DeliveryMan', 'updated', 'App\\Models\\Backend\\DeliveryMan', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"-450.00\"},\"old\":{\"user.name\":\"Delivery Man\",\"current_balance\":\"-350.00\"}}', NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22'),
(136, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 13, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 10:12:47', '2022-09-24 10:12:47'),
(137, 'News Offer', 'created', 'App\\Models\\Backend\\NewsOffer', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"What is Lorem Ipsum?\",\"description\":\"Lorem Ipsum&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:12:47', '2022-09-24 10:12:47'),
(138, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 14, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 10:13:32', '2022-09-24 10:13:32'),
(139, 'News Offer', 'created', 'App\\Models\\Backend\\NewsOffer', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Why do we use it?\",\"description\":\"It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:13:32', '2022-09-24 10:13:32'),
(140, 'Fraud', 'created', 'App\\Models\\Backend\\Fraud', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"createdby.name\":\"Wemaxit\",\"phone\":\"016566546546\",\"name\":\"Abdur rahman\",\"tracking_id\":\"123456\",\"details\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\"}}', NULL, '2022-09-24 10:14:24', '2022-09-24 10:14:24'),
(144, 'HubPayment', 'created', 'App\\Models\\Backend\\HubPayment', 'created', 4, 'App\\Models\\User', 1, '{\"attributes\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":null,\"fromPayment.account_no\":null,\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:25:49', '2022-09-24 10:25:49'),
(145, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 10:26:06', '2022-09-24 10:26:06'),
(146, 'HubPayment', 'updated', 'App\\Models\\Backend\\HubPayment', 'updated', 4, 'App\\Models\\User', 1, '{\"attributes\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":\"trans132154\",\"fromPayment.account_no\":\"123654789\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"},\"old\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":null,\"fromPayment.account_no\":null,\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:26:06', '2022-09-24 10:26:06'),
(149, 'HubPayment', 'created', 'App\\Models\\Backend\\HubPayment', 'created', 7, 'App\\Models\\User', 1, '{\"attributes\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":null,\"fromPayment.account_no\":null,\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:28:54', '2022-09-24 10:28:54'),
(151, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 10:29:53', '2022-09-24 10:29:53'),
(152, 'HubPayment', 'updated', 'App\\Models\\Backend\\HubPayment', 'updated', 7, 'App\\Models\\User', 1, '{\"attributes\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":\"trans321\",\"fromPayment.account_no\":\"123654789\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"},\"old\":{\"hub.name\":\"Mirpur-10\",\"amount\":\"5.00\",\"transaction_id\":null,\"fromPayment.account_no\":null,\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:29:53', '2022-09-24 10:29:53'),
(153, 'ToDo', 'created', 'App\\Models\\Backend\\To_do', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Todo list 1\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\",\"user.name\":\"User\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:30:28', '2022-09-24 10:30:28'),
(154, 'ToDo', 'created', 'App\\Models\\Backend\\To_do', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Todo list 2\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\",\"user.name\":\"User 2\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:30:44', '2022-09-24 10:30:44'),
(155, 'Support', 'created', 'App\\Models\\Backend\\Support', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Wemaxit\",\"department.title\":\"Operations\",\"service\":\"delivery\",\"priority\":\"medium\",\"subject\":\"Pickup Problem support\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:31:23', '2022-09-24 10:31:23'),
(156, 'Support', 'created', 'App\\Models\\Backend\\Support', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Wemaxit\",\"department.title\":\"Sales\",\"service\":\"delivery\",\"priority\":\"medium\",\"subject\":\"Delivery problem support\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\",\"date\":\"2022-09-24\"}}', NULL, '2022-09-24 10:32:00', '2022-09-24 10:32:00'),
(157, 'Asset Category', 'created', 'App\\Models\\Backend\\Assetcategory', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Category 1\",\"position\":\"\"}}', NULL, '2022-09-24 10:32:31', '2022-09-24 10:32:31'),
(158, 'Asset Category', 'created', 'App\\Models\\Backend\\Assetcategory', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Category 2\",\"position\":\"\"}}', NULL, '2022-09-24 10:32:40', '2022-09-24 10:32:40'),
(159, 'Asset Category', 'created', 'App\\Models\\Backend\\Assetcategory', 'created', 3, 'App\\Models\\User', 1, '{\"attributes\":{\"title\":\"Category 3\",\"position\":\"\"}}', NULL, '2022-09-24 10:32:49', '2022-09-24 10:32:49'),
(160, 'Asset', 'created', 'App\\Models\\Backend\\Asset', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Wemaxit\",\"name\":\"Asset 1`\",\"assetcategorys.title\":\"Category 1\",\"hubs.name\":\"Mirpur-10\",\"supplyer_name\":\"Imam\",\"quantity\":\"2\",\"warranty\":\"1\",\"invoice_no\":\"\",\"amount\":\"300.00\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:33:33', '2022-09-24 10:33:33'),
(161, 'Asset', 'created', 'App\\Models\\Backend\\Asset', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Wemaxit\",\"name\":\"Asset 2\",\"assetcategorys.title\":\"Category 2\",\"hubs.name\":\"Jatrabari\",\"supplyer_name\":\"Rony\",\"quantity\":\"4\",\"warranty\":\"4\",\"invoice_no\":\"\",\"amount\":\"1000.00\",\"description\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:34:03', '2022-09-24 10:34:03'),
(162, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 10:34:24', '2022-09-24 10:34:24'),
(163, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 10:34:24', '2022-09-24 10:34:24'),
(164, 'FundTransfer', 'created', 'App\\Models\\Backend\\FundTransfer', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"fromAccount.account_no\":\"123654789\",\"toAccount.account_no\":\"987456321\"}}', NULL, '2022-09-24 10:34:24', '2022-09-24 10:34:24'),
(165, 'Merchant Payment', 'created', 'App\\Models\\Backend\\Payment', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"merchant.business_name\":\"Wemaxit\",\"amount\":\"20.00\",\"merchantAccount.holder_name\":\"Marchant\",\"transaction_id\":\"trans\",\"frompayment.account_no\":\"987456321\",\"description\":\"Merchant monthly payments\"}}', NULL, '2022-09-24 10:35:30', '2022-09-24 10:35:30'),
(166, 'Merchant', 'updated', 'App\\Models\\Backend\\Merchant', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"544.00\"},\"old\":{\"user.name\":\"Merchant\",\"business_name\":\"Wemaxit\",\"current_balance\":\"564.00\"}}', NULL, '2022-09-24 10:35:30', '2022-09-24 10:35:30'),
(167, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 10:35:30', '2022-09-24 10:35:30'),
(168, 'Salary', 'created', 'App\\Models\\Backend\\Salary', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"User\",\"month\":\"2022-09\",\"account\":{\"id\":1,\"type\":1,\"user_id\":2,\"gateway\":2,\"balance\":\"60.00\",\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"bank\":1,\"branch_name\":\"Dhaka\",\"opening_balance\":\"0.00\",\"mobile\":null,\"account_type\":null,\"status\":1,\"created_at\":\"2022-09-24T06:53:28.000000Z\",\"updated_at\":\"2022-09-24T10:34:24.000000Z\"},\"amount\":\"60.00\",\"date\":\"2022-09-24\",\"note\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:35:54', '2022-09-24 10:35:54'),
(169, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User\",\"account_no\":\"123654789\",\"gateway\":2}}', NULL, '2022-09-24 10:35:54', '2022-09-24 10:35:54'),
(170, 'Salary', 'created', 'App\\Models\\Backend\\Salary', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"user.name\":\"User 2\",\"month\":\"2022-09\",\"account\":{\"id\":2,\"type\":1,\"user_id\":3,\"gateway\":2,\"balance\":\"90.00\",\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"bank\":2,\"branch_name\":\"Mirpur\",\"opening_balance\":\"0.00\",\"mobile\":null,\"account_type\":null,\"status\":1,\"created_at\":\"2022-09-24T06:53:28.000000Z\",\"updated_at\":\"2022-09-24T10:35:30.000000Z\"},\"amount\":\"20.00\",\"date\":\"2022-09-24\",\"note\":\"\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\"}}', NULL, '2022-09-24 10:36:16', '2022-09-24 10:36:16'),
(171, 'Account', 'updated', 'App\\Models\\Backend\\Account', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2},\"old\":{\"account_holder_name\":\"User2\",\"account_no\":\"987456321\",\"gateway\":2}}', NULL, '2022-09-24 10:36:16', '2022-09-24 10:36:16'),
(172, 'General Settings', 'updated', 'App\\Models\\Backend\\GeneralSettings', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"phone\":\"20022002\",\"name\":\"WE COURIER\",\"tracking_id\":null,\"details\":null},\"old\":{\"phone\":\"20022002\",\"name\":\"WE COURIER\",\"tracking_id\":null,\"details\":null}}', NULL, '2022-09-24 10:37:44', '2022-09-24 10:37:44'),
(173, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 15, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 10:39:27', '2022-09-24 10:39:27'),
(174, 'Upload', 'created', 'App\\Models\\Backend\\Upload', 'created', 16, 'App\\Models\\User', 1, '{\"attributes\":{\"original\":\"\"}}', NULL, '2022-09-24 10:40:18', '2022-09-24 10:40:18'),
(175, 'User', 'created', 'App\\Models\\User', 'created', 6, 'App\\Models\\User', 1, '{\"attributes\":{\"name\":\"User 3\",\"email\":\"user3@gmail.com\"}}', NULL, '2022-09-24 10:40:18', '2022-09-24 10:40:18'),
(176, 'Account', 'created', 'App\\Models\\Backend\\Account', 'created', 3, 'App\\Models\\User', 1, '{\"attributes\":{\"account_holder_name\":null,\"account_no\":null,\"gateway\":1}}', NULL, '2022-09-24 10:56:19', '2022-09-24 10:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `assetcategories`
--

CREATE TABLE `assetcategories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assetcategories`
--

INSERT INTO `assetcategories` (`id`, `title`, `position`, `created_at`, `updated_at`) VALUES
(1, 'Category 1', '', '2022-09-24 10:32:31', '2022-09-24 10:32:31'),
(2, 'Category 2', '', '2022-09-24 10:32:40', '2022-09-24 10:32:40'),
(3, 'Category 3', '', '2022-09-24 10:32:49', '2022-09-24 10:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `author` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assetcategory_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplyer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warranty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(13,2) DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `author`, `name`, `assetcategory_id`, `hub_id`, `supplyer_name`, `quantity`, `warranty`, `invoice_no`, `amount`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Asset 1`', 1, 1, 'Imam', '2', '1', '', '300.00', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24 10:33:33', '2022-09-24 10:33:33'),
(2, 1, 'Asset 2', 2, 5, 'Rony', '4', '4', '', '1000.00', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24 10:34:03', '2022-09-24 10:34:03');

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_type` tinyint(3) UNSIGNED DEFAULT 1 COMMENT '1=Admin,5=hub',
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expense_id` bigint(20) DEFAULT NULL,
  `fund_transfer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1, expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_transactions`
--

INSERT INTO `bank_transactions` (`id`, `user_type`, `hub_id`, `expense_id`, `fund_transfer_id`, `account_id`, `type`, `amount`, `date`, `note`, `income_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, 2, 1, '180.00', '2022-09-24', 'Payment received from Merchant', 1, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(2, 1, NULL, NULL, NULL, 1, 1, '100.00', '2022-09-24', 'Cash received from delivery man', 2, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(3, 1, NULL, 1, NULL, 2, 2, '100.00', '2022-09-24', 'Commission paid to delivery man', NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22'),
(4, 1, NULL, NULL, NULL, 1, 2, '5.00', '2022-09-24', 'Hub payment withdrawal', NULL, '2022-09-24 10:26:06', '2022-09-24 10:26:06'),
(5, 1, NULL, NULL, NULL, 1, 2, '5.00', '2022-09-24', 'Hub payment withdrawal', NULL, '2022-09-24 10:29:53', '2022-09-24 10:29:53'),
(6, 1, NULL, NULL, 1, 1, 2, '30.00', '2022-09-24', 'Fund transfer expense.', NULL, '2022-09-24 10:34:24', '2022-09-24 10:34:24'),
(7, 1, NULL, NULL, 1, 2, 1, '30.00', '2022-09-24', 'Fund transfer income.', NULL, '2022-09-24 10:34:24', '2022-09-24 10:34:24'),
(8, 1, NULL, NULL, NULL, 2, 2, '20.00', '2022-09-24', 'Merchant Payment Withdrawal', NULL, '2022-09-24 10:35:30', '2022-09-24 10:35:30'),
(9, 1, NULL, NULL, NULL, 1, 2, '60.00', '2022-09-24', 'User salary expense', NULL, '2022-09-24 10:35:54', '2022-09-24 10:35:54'),
(10, 1, NULL, NULL, NULL, 2, 2, '20.00', '2022-09-24', 'User salary expense', NULL, '2022-09-24 10:36:16', '2022-09-24 10:36:16'),
(11, 1, NULL, NULL, NULL, 3, 1, '50000.00', '2022-09-24', 'Account Openning balance', NULL, '2022-09-24 10:56:19', '2022-09-24 10:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `cash_received_from_deliverymen`
--

CREATE TABLE `cash_received_from_deliverymen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `receipt` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categorys`
--

CREATE TABLE `categorys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `configs`
--

CREATE TABLE `configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configs`
--

INSERT INTO `configs` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'fragile_liquid_status', '1', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'fragile_liquid_charge', '20', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'same_day', '1', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'next_day', '1', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'sub_city', '1', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'outside_City', '1', '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `courier_statements`
--

CREATE TABLE `courier_statements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `income_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expense_id` bigint(20) DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courier_statements`
--

INSERT INTO `courier_statements` (`id`, `income_id`, `expense_id`, `parcel_id`, `delivery_man_id`, `type`, `amount`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 20, 1, 2, '20.00', '2022-09-24', 'Parcel Received Warehouse To Courier Expense', '2022-09-24 07:02:00', '2022-09-24 07:02:00'),
(2, NULL, NULL, 20, 1, 2, '30.00', '2022-09-24', 'Parcel Delivered To Deliveryman Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(3, NULL, NULL, 20, 1, 1, '110.50', '2022-09-24', 'Parcel Delivered  To Merchant Courier Expense ', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(4, 1, NULL, NULL, NULL, 1, '180.00', '2022-09-24', 'Payment received from Merchant', '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(5, 2, NULL, NULL, NULL, 1, '100.00', '2022-09-24', 'Cash received from delivery man', '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(6, NULL, 1, NULL, 1, 2, '100.00', '2022-09-24', 'Commission paid to delivery man', '2022-09-24 07:03:22', '2022-09-24 07:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `deliverycategories`
--

CREATE TABLE `deliverycategories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `position` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliverycategories`
--

INSERT INTO `deliverycategories` (`id`, `title`, `status`, `position`, `created_at`, `updated_at`) VALUES
(1, 'KG', 1, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Mobile', 1, 2, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Laptop', 1, 3, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Tabs', 1, 4, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Gaming Kybord', 1, 5, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'Cosmetices', 1, 6, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `deliveryman_statements`
--

CREATE TABLE `deliveryman_statements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_id` bigint(20) DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `cash_collection` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'true=1,false=0',
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveryman_statements`
--

INSERT INTO `deliveryman_statements` (`id`, `expense_id`, `parcel_id`, `delivery_man_id`, `hub_id`, `type`, `amount`, `cash_collection`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, NULL, 20, 1, NULL, 1, '20.00', 0, '2022-09-24', 'Parcel Received Warehouse To Deliveryman Income', '2022-09-24 07:02:00', '2022-09-24 07:02:00'),
(2, NULL, 20, 1, NULL, 1, '30.00', 0, '2022-09-24', 'Parcel Delivered To Deliveryman Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(3, NULL, 20, 1, NULL, 2, '500.00', 1, '2022-09-24', 'Parcel Delivered To Deliveryman Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(4, NULL, NULL, 1, NULL, 1, '100.00', 0, '2022-09-24', 'Cash received from delivery man', '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(5, 1, NULL, 1, NULL, 1, '100.00', 0, '2022-09-24', 'Commission paid to delivery man', '2022-09-24 07:03:22', '2022-09-24 07:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_charges`
--

CREATE TABLE `delivery_charges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weight` tinyint(4) NOT NULL DEFAULT 0,
  `same_day` decimal(16,2) NOT NULL DEFAULT 0.00,
  `next_day` decimal(16,2) NOT NULL DEFAULT 0.00,
  `sub_city` decimal(16,2) NOT NULL DEFAULT 0.00,
  `outside_city` decimal(16,2) NOT NULL DEFAULT 0.00,
  `position` int(11) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_charges`
--

INSERT INTO `delivery_charges` (`id`, `category_id`, `weight`, `same_day`, `next_day`, `sub_city`, `outside_city`, `position`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '50.00', '60.00', '70.00', '80.00', 1, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 1, 2, '90.00', '100.00', '110.00', '120.00', 2, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 1, 3, '130.00', '140.00', '150.00', '160.00', 3, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 1, 4, '170.00', '180.00', '190.00', '200.00', 4, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 1, 5, '210.00', '220.00', '230.00', '240.00', 5, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 1, 6, '250.00', '260.00', '270.00', '280.00', 6, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 1, 7, '290.00', '300.00', '310.00', '320.00', 7, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(8, 1, 8, '340.00', '350.00', '360.00', '370.00', 8, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(9, 1, 9, '380.00', '390.00', '400.00', '410.00', 9, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(10, 1, 10, '420.00', '430.00', '440.00', '450.00', 10, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_man`
--

CREATE TABLE `delivery_man` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `delivery_charge` decimal(13,2) NOT NULL DEFAULT 0.00,
  `pickup_charge` decimal(13,2) NOT NULL DEFAULT 0.00,
  `return_charge` decimal(13,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(13,2) NOT NULL DEFAULT 0.00,
  `opening_balance` decimal(13,2) NOT NULL DEFAULT 0.00,
  `driving_license_image_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_man`
--

INSERT INTO `delivery_man` (`id`, `user_id`, `status`, `delivery_charge`, `pickup_charge`, `return_charge`, `current_balance`, `opening_balance`, `driving_license_image_id`, `created_at`, `updated_at`) VALUES
(1, 4, 1, '30.00', '20.00', '10.00', '-450.00', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 07:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `title`, `status`, `created_at`, `updated_at`) VALUES
(1, 'General Management', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Marketing', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Operations', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Finance', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Sales', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'Human Resource', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 'Purchase', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `title`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Chief Executive Officer (CEO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Chief Operating Officer (COO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Chief Financial Officer (CFO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Chief Technology Officer (CTO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Chief Legal Officer (CLO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'Chief Marketing Officer (CMO)', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_head_id` bigint(20) UNSIGNED DEFAULT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `receipt` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `account_head_id`, `merchant_id`, `delivery_man_id`, `user_id`, `parcel_id`, `account_id`, `amount`, `date`, `receipt`, `note`, `title`, `created_at`, `updated_at`) VALUES
(1, 5, NULL, 1, NULL, NULL, 2, '100.00', '2022-09-24', 12, '', '', '2022-09-24 07:03:22', '2022-09-24 07:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frauds`
--

CREATE TABLE `frauds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `frauds`
--

INSERT INTO `frauds` (`id`, `created_by`, `phone`, `name`, `tracking_id`, `details`, `created_at`, `updated_at`) VALUES
(1, 1, '016566546546', 'Abdur rahman', '123456', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', '2022-09-24 10:14:24', '2022-09-24 10:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `fund_transfers`
--

CREATE TABLE `fund_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_account` bigint(20) UNSIGNED DEFAULT NULL,
  `to_account` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fund_transfers`
--

INSERT INTO `fund_transfers` (`id`, `from_account`, `to_account`, `amount`, `date`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '30.00', '2022-09-24', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24 10:34:24', '2022-09-24 10:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `general_settings`
--

CREATE TABLE `general_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `copyright` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` int(11) DEFAULT NULL,
  `favicon` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `general_settings`
--

INSERT INTO `general_settings` (`id`, `name`, `phone`, `email`, `currency`, `copyright`, `logo`, `favicon`, `created_at`, `updated_at`) VALUES
(1, 'WE COURIER', '20022002', 'info@wecourier.com', '৳', 'All rights reserved. Development by Wemax IT.', 8, 9, '2022-09-24 06:53:29', '2022-09-24 10:37:44');

-- --------------------------------------------------------

--
-- Table structure for table `hubs`
--

CREATE TABLE `hubs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` decimal(16,2) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hubs`
--

INSERT INTO `hubs` (`id`, `name`, `phone`, `address`, `current_balance`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mirpur-10', '01000000001', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Uttara', '01000000002', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Dhanmundi', '01000000003', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Old Dhaka', '01000000004', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Jatrabari', '01000000005', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'Badda', '01000000006', 'Dhaka, Bangladesh', '0.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `hub_incharges`
--

CREATE TABLE `hub_incharges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `hub_id` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hub_incharges`
--

INSERT INTO `hub_incharges` (`id`, `user_id`, `hub_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `hub_payments`
--

CREATE TABLE `hub_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_account` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_file` bigint(20) UNSIGNED DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT '1=admin,4=incharge',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT '1= Reject,2=Approved , 3= Pending,4=Process, ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hub_payments`
--

INSERT INTO `hub_payments` (`id`, `hub_id`, `amount`, `transaction_id`, `from_account`, `reference_file`, `description`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
(4, 1, '5.00', 'trans132154', 1, NULL, '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 1, 4, '2022-09-24 10:25:49', '2022-09-24 10:26:06'),
(7, 1, '5.00', 'trans321', 1, NULL, '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 1, 4, '2022-09-24 10:28:54', '2022-09-24 10:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `hub_statements`
--

CREATE TABLE `hub_statements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incomes`
--

CREATE TABLE `incomes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_head_id` bigint(20) UNSIGNED DEFAULT NULL,
  `from` tinyint(3) UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_user_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `receipt` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incomes`
--

INSERT INTO `incomes` (`id`, `account_head_id`, `from`, `title`, `user_id`, `merchant_id`, `delivery_man_id`, `parcel_id`, `account_id`, `hub_id`, `hub_user_id`, `hub_user_account_id`, `amount`, `date`, `receipt`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, 1, NULL, NULL, 2, NULL, NULL, NULL, '180.00', '2022-09-24', 10, '', '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(2, 2, 2, NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, NULL, '100.00', '2022-09-24', 11, '', '2022-09-24 07:03:02', '2022-09-24 07:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `merchants`
--

CREATE TABLE `merchants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_unique_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `opening_balance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(16,2) NOT NULL DEFAULT 0.00,
  `cod_charges` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nid_id` bigint(20) UNSIGNED DEFAULT NULL,
  `trade_license` bigint(20) UNSIGNED DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `address` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `merchants`
--

INSERT INTO `merchants` (`id`, `user_id`, `business_name`, `merchant_unique_id`, `current_balance`, `opening_balance`, `vat`, `cod_charges`, `nid_id`, `trade_license`, `status`, `address`, `created_at`, `updated_at`) VALUES
(1, 5, 'Wemaxit', '251111', '544.00', '0.00', '0.00', '{\"inside_city\":\"1\",\"sub_city\":\"2\",\"outside_city\":\"3\"}', 4, 5, 1, 'Dhaka', '2022-09-24 06:53:28', '2022-09-24 10:35:30');

-- --------------------------------------------------------

--
-- Table structure for table `merchant_delivery_charges`
--

CREATE TABLE `merchant_delivery_charges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_charge_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weight` bigint(20) DEFAULT NULL,
  `category_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `same_day` decimal(16,2) DEFAULT NULL,
  `next_day` decimal(16,2) DEFAULT NULL,
  `sub_city` decimal(16,2) DEFAULT NULL,
  `outside_city` decimal(16,2) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `merchant_delivery_charges`
--

INSERT INTO `merchant_delivery_charges` (`id`, `merchant_id`, `delivery_charge_id`, `weight`, `category_id`, `same_day`, `next_day`, `sub_city`, `outside_city`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '50.00', '60.00', '70.00', '80.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 1, 2, 2, 1, '90.00', '100.00', '110.00', '120.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 1, 3, 3, 1, '130.00', '140.00', '150.00', '160.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 1, 4, 4, 1, '170.00', '180.00', '190.00', '200.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 1, 5, 5, 1, '210.00', '220.00', '230.00', '240.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 1, 6, 6, 1, '250.00', '260.00', '270.00', '280.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 1, 7, 7, 1, '290.00', '300.00', '310.00', '320.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(8, 1, 8, 8, 1, '340.00', '350.00', '360.00', '370.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(9, 1, 9, 9, 1, '380.00', '390.00', '400.00', '410.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(10, 1, 10, 10, 1, '420.00', '430.00', '440.00', '450.00', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `merchant_payments`
--

CREATE TABLE `merchant_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `merchant_payments`
--

INSERT INTO `merchant_payments` (`id`, `merchant_id`, `payment_method`, `bank_name`, `holder_name`, `account_no`, `branch_name`, `routing_no`, `mobile_company`, `mobile_no`, `account_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'bank', 'NRB Commercial Bank Ltd.', 'Marchant', '123456', 'Dhaka branch', '123456', NULL, NULL, NULL, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 1, 'mobile', NULL, 'Marchant', NULL, NULL, NULL, 'Bkash', '01300000000', 'Personal', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 1, 'mobile', NULL, 'Marchant', NULL, NULL, NULL, 'Nagad', '01300000000', 'Personal', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 1, 'mobile', NULL, 'Marchant', NULL, NULL, NULL, 'Rocket', '01300000000', 'Personal', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `merchant_shops`
--

CREATE TABLE `merchant_shops` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `default_shop` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `merchant_shops`
--

INSERT INTO `merchant_shops` (`id`, `merchant_id`, `name`, `contact_no`, `address`, `status`, `default_shop`, `created_at`, `updated_at`) VALUES
(1, 1, 'Shop 1', '+88013000000', 'wemaxit,Dhaka', 1, 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 1, 'Shop 2', '+88013000000', 'wemaxit,Dhaka', 1, 0, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 1, 'Shop 3', '+88013000000', 'wemaxit,Dhaka', 1, 0, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 1, 'Shop 4', '+88013000000', 'wemaxit,Dhaka', 1, 0, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 1, 'Shop 5', '+88013000000', 'wemaxit,Dhaka', 1, 0, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `merchant_statements`
--

CREATE TABLE `merchant_statements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_id` bigint(20) DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `merchant_statements`
--

INSERT INTO `merchant_statements` (`id`, `expense_id`, `parcel_id`, `merchant_id`, `delivery_man_id`, `type`, `amount`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, NULL, 20, NULL, 1, 1, '500.00', '2022-09-24', 'Parcel Delivered To Merchant Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(2, NULL, 20, NULL, 1, 2, '110.50', '2022-09-24', 'Parcel Delivered To Merchant Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(3, NULL, 20, NULL, 1, 2, '5.50', '2022-09-24', 'Parcel Delivered To Merchant Income', '2022-09-24 07:02:08', '2022-09-24 07:02:08'),
(4, NULL, NULL, 1, NULL, 1, '180.00', '2022-09-24', 'Payment received from Merchant', '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(5, NULL, NULL, 1, NULL, 2, '20.00', '2022-09-24', 'Payment Withdrawal', '2022-09-24 10:35:30', '2022-09-24 10:35:30');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_09_12_000000_create_hubs_table', 1),
(2, '2014_09_12_000000_create_uploads_table', 1),
(3, '2014_10_10_040240_create_roles_table', 1),
(4, '2014_10_11_000000_create_deliverycategories_table', 1),
(5, '2014_10_11_000000_create_departments_table', 1),
(6, '2014_10_11_000000_create_designations_table', 1),
(7, '2014_10_11_000000_create_packagings_table', 1),
(8, '2014_10_11_000000_create_users_table', 1),
(9, '2014_10_11_000001_create_merchants_table', 1),
(10, '2014_10_12_100000_create_password_resets_table', 1),
(11, '2019_08_19_000000_create_failed_jobs_table', 1),
(12, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(13, '2022_03_20_060512_create_phonebooks_table', 1),
(14, '2022_03_20_060621_create_categories_table', 1),
(15, '2022_03_24_042455_create_activity_log_table', 1),
(16, '2022_03_24_042456_add_event_column_to_activity_log_table', 1),
(17, '2022_03_24_042457_add_batch_uuid_column_to_activity_log_table', 1),
(18, '2022_04_04_142330_create_delivery_man_table', 1),
(19, '2022_04_04_142330_create_hub_incharges_table', 1),
(20, '2022_04_04_142330_create_parcels_table', 1),
(21, '2022_04_09_101126_create_delivery_charges_table', 1),
(22, '2022_04_09_101126_create_merchant_delivery_charges_table', 1),
(23, '2022_04_10_050353_create_merchant_shops_table', 1),
(24, '2022_04_13_034848_create_merchant_payments_table', 1),
(25, '2022_04_13_054047_create_accounts_table', 1),
(26, '2022_04_14_045839_create_fund_transfers_table', 1),
(27, '2022_04_14_063624_create_payments_table', 1),
(28, '2022_04_17_061311_create_payment_accounts_table', 1),
(29, '2022_04_19_035758_create_configs_table', 1),
(30, '2022_04_20_053011_create_sessions_table', 1),
(31, '2022_04_23_032024_create_permissions_table', 1),
(32, '2022_04_24_045606_create_parcel_logs_table', 1),
(33, '2022_04_27_123343_create_parcel_events_table', 1),
(34, '2022_05_14_112714_create_account_heads_table', 1),
(35, '2022_05_14_112715_create_expenses_table', 1),
(36, '2022_05_14_112717_create_deliveryman_statements_table', 1),
(37, '2022_05_15_102801_create_merchant_statements_table', 1),
(38, '2022_05_17_124213_create_incomes_table', 1),
(39, '2022_05_17_132716_create_courier_statements_table', 1),
(40, '2022_05_18_113259_create_to_dos_table', 1),
(41, '2022_05_23_111055_create_supports_table', 1),
(42, '2022_05_23_122723_create_sms_send_settings_table', 1),
(43, '2022_05_23_122723_create_sms_settings_table', 1),
(44, '2022_05_24_141546_create_vat_statements_table', 1),
(45, '2022_05_26_093710_create_bank_transactions_table', 1),
(46, '2022_05_31_094551_create_general_settings_table', 1),
(47, '2022_05_31_122026_create_assets_table', 1),
(48, '2022_05_31_122655_create_assetcategories_table', 1),
(49, '2022_05_31_150039_create_salaries_table', 1),
(50, '2022_05_6_063624_create_hub_payments_table', 1),
(51, '2022_06_01_144229_create_news_offers_table', 1),
(52, '2022_06_02_125218_create_support_chats_table', 1),
(53, '2022_06_04_104751_create_hub_statements_table', 1),
(54, '2022_06_05_093107_create_frauds_table', 1),
(55, '2022_06_05_140650_create_cash_received_from_deliverymen_table', 1),
(56, '2022_06_12_111844_create_salary_generates_table', 1),
(57, '2022_08_17_145916_create_subscribes_table', 1),
(58, '2022_09_08_102027_create_pickup_requests_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `news_offers`
--

CREATE TABLE `news_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `author` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'active       = 1,\r\n                inactive      = 0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_offers`
--

INSERT INTO `news_offers` (`id`, `author`, `title`, `description`, `file`, `date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'What is Lorem Ipsum?', 'Lorem Ipsum&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', 13, '2022-09-24', 1, '2022-09-24 10:12:47', '2022-09-24 10:12:47'),
(2, 1, 'Why do we use it?', 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).', 14, '2022-09-24', 1, '2022-09-24 10:13:32', '2022-09-24 10:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `packagings`
--

CREATE TABLE `packagings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(16,2) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packagings`
--

INSERT INTO `packagings` (`id`, `name`, `price`, `status`, `position`, `photo`, `created_at`, `updated_at`) VALUES
(1, 'Poly', '10.00', 1, '1', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Bubble Poly', '20.00', 1, '2', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'Box', '30.00', 1, '3', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Box Poly', '40.00', 1, '4', NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `merchant_shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pickup_address` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `weight` bigint(20) DEFAULT 0,
  `delivery_type_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `packaging_id` bigint(20) DEFAULT NULL,
  `first_hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transfer_hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cash_collection` decimal(13,2) DEFAULT NULL,
  `old_cash_collection` decimal(13,2) DEFAULT NULL,
  `selling_price` decimal(13,2) DEFAULT NULL,
  `liquid_fragile_amount` decimal(13,2) DEFAULT NULL,
  `packaging_amount` decimal(13,2) DEFAULT NULL,
  `delivery_charge` decimal(13,2) DEFAULT NULL,
  `cod_charge` bigint(20) DEFAULT NULL,
  `cod_amount` decimal(13,2) DEFAULT NULL,
  `vat` bigint(20) DEFAULT NULL,
  `vat_amount` decimal(13,2) DEFAULT NULL,
  `total_delivery_amount` decimal(13,2) DEFAULT NULL,
  `current_payable` decimal(13,2) DEFAULT NULL,
  `tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `partial_delivered` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'no=0,yes=1',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'pending             = 1,\r\n                 picup_assign        = 2,\r\n                 received_warehouse  = 3,\r\n                 delivery_man_assign = 4,\r\n                 deliver             = 5,\r\n                 delivered           = 6,\r\n                 return_warehouse    = 7,\r\n                 assign_merchant     = 8,\r\n                 returned_merchant   = 9',
  `parcel_bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `merchant_id`, `merchant_shop_id`, `pickup_address`, `pickup_phone`, `customer_name`, `customer_phone`, `customer_address`, `invoice_no`, `category_id`, `weight`, `delivery_type_id`, `packaging_id`, `first_hub_id`, `hub_id`, `transfer_hub_id`, `cash_collection`, `old_cash_collection`, `selling_price`, `liquid_fragile_amount`, `packaging_amount`, `delivery_charge`, `cod_charge`, `cod_amount`, `vat`, `vat_amount`, `total_delivery_amount`, `current_payable`, `tracking_id`, `note`, `partial_delivered`, `status`, `parcel_bank`, `pickup_date`, `delivery_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 0', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C10', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 1', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C11', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 2', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C12', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 3', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C13', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 4', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C14', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 5', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C15', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 6', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C16', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(8, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 7', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C17', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(9, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 8', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C18', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(10, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 9', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C19', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(11, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 10', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C110', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(12, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 11', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C111', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(13, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 12', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002408C112', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(14, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 13', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C113', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(15, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 14', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C114', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(16, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 15', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C115', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(17, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 16', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C116', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(18, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 17', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C117', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(19, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 18', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, NULL, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C118', 'Test parcel', 0, 1, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(20, 1, 1, 'Mirpur-02', '01478523698', 'Abdullah 19', '01478523655', 'Mirpur-10', '123654', 1, 5, 1, 3, NULL, 1, NULL, '500.00', NULL, '500.00', '20.00', '30.00', '50.00', 1, '5.00', 10, '5.50', '110.50', '389.50', 'RX664002409C119', 'Test parcel', 0, 9, NULL, '2022-09-24', '2022-09-24', '2022-09-24 06:53:29', '2022-09-24 07:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_events`
--

CREATE TABLE `parcel_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parcel_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pickup_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transfer_delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parcel_status` tinyint(4) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parcel_events`
--

INSERT INTO `parcel_events` (`id`, `parcel_id`, `delivery_man_id`, `pickup_man_id`, `hub_id`, `transfer_delivery_man_id`, `note`, `parcel_status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 'Pickup Assign', 2, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(2, 1, 1, 1, 1, 1, 'Pickup Re-Schedule', 3, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(3, 1, 1, 1, 1, 1, 'Received By Pickup Man', 4, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(4, 1, 1, 1, 1, 1, 'Received Warehouse', 5, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(5, 1, 1, 1, 1, 1, 'Transfer to hub', 6, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(6, 1, 1, 1, 1, 1, 'Received by hub', 19, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(7, 1, 1, 1, 1, 1, 'Delivery Man Assign', 7, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(8, 1, 1, 1, 1, 1, 'Delivery Re-Schedule', 8, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(9, 1, 1, 1, 1, 1, 'Return to Courier', 24, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(10, 1, 1, 1, 1, 1, 'Partial Delivered', 32, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(11, 1, 1, 1, 1, 1, 'Delivered', 9, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(12, 1, 1, 1, 1, 1, 'Return assign to merchant', 26, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(13, 1, 1, 1, 1, 1, 'Return assign to merchant Re-Schedule ', 27, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(14, 1, 1, 1, 1, 1, 'Return received by merchant', 30, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(15, 1, 1, 1, 1, 1, 'Return Warehouse', 11, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(16, 1, 1, 1, 1, 1, 'Assign Merchant', 12, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(17, 1, 1, 1, 1, 1, 'Returned Merchant', 13, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(18, 2, 1, 1, 1, 1, 'Pickup Assign', 2, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(19, 2, 1, 1, 1, 1, 'Pickup Re-Schedule', 3, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(20, 2, 1, 1, 1, 1, 'Received By Pickup Man', 4, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(21, 2, 1, 1, 1, 1, 'Received Warehouse', 5, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(22, 2, 1, 1, 1, 1, 'Transfer to hub', 6, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(23, 2, 1, 1, 1, 1, 'Received by hub', 19, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(24, 2, 1, 1, 1, 1, 'Delivery Man Assign', 7, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(25, 2, 1, 1, 1, 1, 'Delivery Re-Schedule', 8, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(26, 2, 1, 1, 1, 1, 'Return to Courier', 24, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(27, 2, 1, 1, 1, 1, 'Partial Delivered', 32, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(28, 2, 1, 1, 1, 1, 'Delivered', 9, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(29, 2, 1, 1, 1, 1, 'Return assign to merchant', 26, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(30, 2, 1, 1, 1, 1, 'Return assign to merchant Re-Schedule ', 27, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(31, 2, 1, 1, 1, 1, 'Return received by merchant', 30, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(32, 2, 1, 1, 1, 1, 'Return Warehouse', 11, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(33, 2, 1, 1, 1, 1, 'Assign Merchant', 12, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(34, 2, 1, 1, 1, 1, 'Returned Merchant', 13, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(35, 3, 1, 1, 1, 1, 'Pickup Assign', 2, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(36, 3, 1, 1, 1, 1, 'Pickup Re-Schedule', 3, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(37, 3, 1, 1, 1, 1, 'Received By Pickup Man', 4, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(38, 3, 1, 1, 1, 1, 'Received Warehouse', 5, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(39, 3, 1, 1, 1, 1, 'Transfer to hub', 6, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(40, 3, 1, 1, 1, 1, 'Received by hub', 19, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(41, 3, 1, 1, 1, 1, 'Delivery Man Assign', 7, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(42, 3, 1, 1, 1, 1, 'Delivery Re-Schedule', 8, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(43, 3, 1, 1, 1, 1, 'Return to Courier', 24, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(44, 3, 1, 1, 1, 1, 'Partial Delivered', 32, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(45, 3, 1, 1, 1, 1, 'Delivered', 9, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(46, 3, 1, 1, 1, 1, 'Return assign to merchant', 26, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(47, 3, 1, 1, 1, 1, 'Return assign to merchant Re-Schedule ', 27, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(48, 3, 1, 1, 1, 1, 'Return received by merchant', 30, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(49, 3, 1, 1, 1, 1, 'Return Warehouse', 11, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(50, 3, 1, 1, 1, 1, 'Assign Merchant', 12, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(51, 3, 1, 1, 1, 1, 'Returned Merchant', 13, 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(52, 20, NULL, 1, NULL, NULL, '', 2, 1, '2022-09-24 07:01:53', '2022-09-24 07:01:53'),
(53, 20, NULL, NULL, 1, NULL, '', 5, 1, '2022-09-24 07:02:00', '2022-09-24 07:02:00'),
(54, 20, 1, NULL, NULL, NULL, '', 7, 1, '2022-09-24 07:02:05', '2022-09-24 07:02:05'),
(55, 20, NULL, NULL, NULL, NULL, 'asdf', 9, 1, '2022-09-24 07:02:08', '2022-09-24 07:02:08');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_logs`
--

CREATE TABLE `parcel_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_man_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parcel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pickup_address` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash_collection` decimal(13,2) DEFAULT NULL,
  `selling_price` decimal(13,2) DEFAULT NULL,
  `total_delivery_amount` decimal(13,2) DEFAULT NULL,
  `current_payable` decimal(13,2) DEFAULT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `merchant_account` tinyint(4) DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_account` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_file` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT '1=admin,2=merchant',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT '1= Reject,2=Approved , 3= Pending,4=Process, ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `merchant_id`, `amount`, `merchant_account`, `transaction_id`, `from_account`, `reference_file`, `description`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '20.00', 2, 'trans', 2, NULL, 'Merchant monthly payments', 1, 4, '2022-09-24 10:35:30', '2022-09-24 10:35:30');

-- --------------------------------------------------------

--
-- Table structure for table `payment_accounts`
--

CREATE TABLE `payment_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attribute` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `attribute`, `keywords`, `created_at`, `updated_at`) VALUES
(1, 'dashboard', '{\"read\":\"dashboard_read\",\"calendar\":\"calendar_read\",\"total_Parcel\":\"total_parcel\",\"total_user\":\"total_user\",\"total_merchant\":\"total_merchant\",\"total_delivery_man\":\"total_delivery_man\",\"total_hubs\":\"total_hubs\",\"total_accounts\":\"total_accounts\",\"total_parcels_pending\":\"total_parcels_pending\",\"total_pickup_assigned\":\"total_pickup_assigned\",\"total_received_warehouse\":\"total_received_warehouse\",\"total_deliveryman_assigned\":\"total_deliveryman_assigned\",\"total_partial_deliverd\":\"total_partial_deliverd\",\"total_parcels_deliverd\":\"total_parcels_deliverd\",\"recent_accounts\":\"recent_accounts\",\"recent_salary\":\"recent_salary\",\"recent_hub\":\"recent_hub\",\"all_statements\":\"all_statements\",\"income_expense_charts\":\"income_expense_charts\",\"merchant_revenue_charts\":\"merchant_revenue_charts\",\"deliveryman_revenue_charts\":\"deliveryman_revenue_charts\",\"courier_revenue_charts\":\"courier_revenue_charts\",\"recent_parcels\":\"recent_parcels\",\"bank_transaction\":\"bank_transaction\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(2, 'logs', '{\"read\":\"log_read\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(3, 'hubs', '{\"read\":\"hub_read\",\"create\":\"hub_create\",\"update\":\"hub_update\",\"delete\":\"hub_delete\",\"incharge_read\":\"hub_incharge_read\",\"incharge_create\":\"hub_incharge_create\",\"incharge_update\":\"hub_incharge_update\",\"incharge_delete\":\"hub_incharge_delete\",\"incharge_assigned\":\"hub_incharge_assigned\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(4, 'accounts', '{\"read\":\"account_read\",\"create\":\"account_create\",\"update\":\"account_update\",\"delete\":\"account_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(5, 'income', '{\"read\":\"income_read\",\"create\":\"income_create\",\"update\":\"income_update\",\"delete\":\"income_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(6, 'expense', '{\"read\":\"expense_read\",\"create\":\"expense_create\",\"update\":\"expense_update\",\"delete\":\"expense_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(7, 'todo', '{\"read\":\"todo_read\",\"create\":\"todo_create\",\"update\":\"todo_update\",\"delete\":\"todo_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(8, 'fund_transfer', '{\"read\":\"fund_transfer_read\",\"create\":\"fund_transfer_create\",\"update\":\"fund_transfer_update\",\"delete\":\"fund_transfer_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(9, 'roles', '{\"read\":\"role_read\",\"create\":\"role_create\",\"update\":\"role_update\",\"delete\":\"role_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(10, 'designations', '{\"read\":\"designation_read\",\"create\":\"designation_create\",\"update\":\"designation_update\",\"delete\":\"designation_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(11, 'departments', '{\"read\":\"department_read\",\"create\":\"department_create\",\"update\":\"department_update\",\"delete\":\"department_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(12, 'users', '{\"read\":\"user_read\",\"create\":\"user_create\",\"update\":\"user_update\",\"delete\":\"user_delete\",\"permission_update\":\"permission_update\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(13, 'merchant', '{\"read\":\"merchant_read\",\"create\":\"merchant_create\",\"update\":\"merchant_update\",\"delete\":\"merchant_delete\",\"view\":\"merchant_view\",\"delivery_charge_read\":\"merchant_delivery_charge_read\",\"delivery_charge_create\":\"merchant_delivery_charge_create\",\"delivery_charge_update\":\"merchant_delivery_charge_update\",\"delivery_charge_delete\":\"merchant_delivery_charge_delete\",\"shop_read\":\"merchant_shop_read\",\"shop_create\":\"merchant_shop_create\",\"shop_update\":\"merchant_shop_update\",\"shop_delete\":\"merchant_shop_delete\",\"payment_read\":\"merchant_payment_read\",\"payment_create\":\"merchant_payment_create\",\"payment_update\":\"merchant_payment_update\",\"payment_delete\":\"merchant_payment_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(14, 'payments', '{\"read\":\"payment_read\",\"create\":\"payment_create\",\"update\":\"payment_update\",\"delete\":\"payment_delete\",\"reject\":\"payment_reject\",\"process\":\"payment_process\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(15, 'hub_payments', '{\"read\":\"hub_payment_read\",\"create\":\"hub_payment_create\",\"update\":\"hub_payment_update\",\"delete\":\"hub_payment_delete\",\"reject\":\"hub_payment_reject\",\"process\":\"hub_payment_process\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(16, 'hub_payments_request', '{\"read\":\"hub_payment_request_read\",\"create\":\"hub_payment_request_create\",\"update\":\"hub_payment_request_update\",\"delete\":\"hub_payment_request_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(17, 'liquid_fragile', '{\"read\":\"liquid_fragile_read\",\"update\":\"liquid_fragile_update\",\"status_change\":\"liquid_status_change\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(18, 'database_backup', '{\"read\":\"database_backup_read\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(19, 'sms_settings', '{\"read\":\"sms_settings_read\",\"create\":\"sms_settings_create\",\"update\":\"sms_settings_update\",\"delete\":\"sms_settings_delete\",\"status_change\":\"sms_settings_status_change\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(20, 'sms_send_settings', '{\"read\":\"sms_send_settings_read\",\"create\":\"sms_send_settings_create\",\"update\":\"sms_send_settings_update\",\"delete\":\"sms_send_settings_delete\",\"status_change\":\"sms_send_settings_status_change\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(21, 'general_settings', '{\"read\":\"general_settings_read\",\"update\":\"general_settings_update\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(22, 'parcel', '{\"read\":\"parcel_read\",\"create\":\"parcel_create\",\"update\":\"parcel_update\",\"delete\":\"parcel_delete\",\"status_update\":\"parcel_status_update\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(23, 'delivery_man', '{\"read\":\"delivery_man_read\",\"create\":\"delivery_man_create\",\"update\":\"delivery_man_update\",\"delete\":\"delivery_man_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(24, 'delivery_category', '{\"read\":\"delivery_category_read\",\"create\":\"delivery_category_create\",\"update\":\"delivery_category_update\",\"delete\":\"delivery_category_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(25, 'delivery_charge', '{\"read\":\"delivery_charge_read\",\"create\":\"delivery_charge_create\",\"update\":\"delivery_charge_update\",\"delete\":\"delivery_charge_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(26, 'delivery_type', '{\"read\":\"delivery_type_read\",\"status_change\":\"delivery_type_status_change\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(27, 'packaging', '{\"read\":\"packaging_read\",\"create\":\"packaging_create\",\"update\":\"packaging_update\",\"delete\":\"packaging_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(28, 'category', '{\"read\":\"category_read\",\"create\":\"category_create\",\"update\":\"category_update\",\"delete\":\"category_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(29, 'account_heads', '{\"read\":\"account_heads_read\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(30, 'salary', '{\"read\":\"salary_read\",\"create\":\"salary_create\",\"update\":\"salary_update\",\"delete\":\"salary_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(31, 'support', '{\"read\":\"support_read\",\"create\":\"support_create\",\"update\":\"support_update\",\"delete\":\"support_delete\",\"reply\":\"support_reply\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(32, 'asset_category', '{\"read\":\"asset_category_read\",\"create\":\"asset_category_create\",\"update\":\"asset_category_update\",\"delete\":\"asset_category_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(33, 'assets', '{\"read\":\"assets_read\",\"create\":\"assets_create\",\"update\":\"assets_update\",\"delete\":\"assets_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(34, 'news_offer', '{\"read\":\"news_offer_read\",\"create\":\"news_offer_create\",\"update\":\"news_offer_update\",\"delete\":\"news_offer_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(35, 'bank_transaction', '{\"read\":\"bank_transaction_read\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(36, 'cash_received_from_delivery_man', '{\"read\":\"cash_received_from_delivery_man_read\",\"create\":\"cash_received_from_delivery_man_create\",\"update\":\"cash_received_from_delivery_man_update\",\"delete\":\"cash_received_from_delivery_man_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(37, 'reports', '{\"parcel_status_reports\":\"parcel_status_reports\",\"parcel_wise_profit\":\"parcel_wise_profit\",\"parcel_total_summery\":\"parcel_total_summery\",\"salary_reports\":\"salary_reports\",\"merchant_hub_deliveryman\":\"merchant_hub_deliveryman\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(38, 'salary_generate', '{\"read\":\"salary_generate_read\",\"create\":\"salary_generate_create\",\"update\":\"salary_generate_update\",\"delete\":\"salary_generate_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(39, 'fraud', '{\"read\":\"fraud_read\",\"create\":\"fraud_create\",\"update\":\"fraud_update\",\"delete\":\"fraud_delete\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(40, 'subscribe', '{\"read\":\"subscribe_read\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(41, 'pickup_request', '{\"regular\":\"pickup_request_regular\",\"express\":\"pickup_request_express\"}', '2022-09-24 06:53:29', '2022-09-24 06:53:29');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phonebooks`
--

CREATE TABLE `phonebooks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catagoryID` tinyint(4) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pickup_requests`
--

CREATE TABLE `pickup_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'regular = 1,',
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parcel_quantity` bigint(20) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cod_amount` decimal(16,2) DEFAULT 0.00,
  `invoice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` bigint(20) DEFAULT 0,
  `exchange` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'yes = 1, no = 0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pickup_requests`
--

INSERT INTO `pickup_requests` (`id`, `request_type`, `merchant_id`, `address`, `note`, `parcel_quantity`, `name`, `phone`, `cod_amount`, `invoice`, `weight`, `exchange`, `created_at`, `updated_at`) VALUES
(1, '1', 1, 'Dhaka', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 20, NULL, NULL, '0.00', NULL, 0, 0, '2022-09-24 10:21:30', '2022-09-24 10:21:30'),
(2, '1', 1, 'Dhaka', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 10, NULL, NULL, '0.00', NULL, 0, 0, '2022-09-24 10:21:38', '2022-09-24 10:21:38'),
(3, '2', 1, 'Mirpur 2', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 0, 'Sheik Riaz uddin', '01820064106', '1.00', '', 20, 1, '2022-09-24 10:22:24', '2022-09-24 10:22:24'),
(4, '2', 1, 'Mirpur 10', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 0, 'Abdur Rahman', '01826546545', '1.00', '', 20, 1, '2022-09-24 10:23:00', '2022-09-24 10:23:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `permissions`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'super-admin', '[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'Admin', 'admin', '[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"income_read\",\"expense_read\",\"todo_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"general_settings_read\",\"account_heads_read\",\"salary_read\",\"support_read\",\"fund_transfer_read\",\"role_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"sms_settings_status_change\",\"sms_send_settings_status_change\",\"bank_transaction_read\",\"database_backup_read\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]', 1, '2022-09-24 06:53:28', '2022-09-24 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(16,2) NOT NULL DEFAULT 0.00,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`id`, `user_id`, `month`, `account_id`, `amount`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, 2, '2022-09', 1, '60.00', '2022-09-24', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24 10:35:54', '2022-09-24 10:35:54'),
(2, 3, '2022-09', 2, '20.00', '2022-09-24', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24 10:36:16', '2022-09-24 10:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `salary_generates`
--

CREATE TABLE `salary_generates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unpaid=0,Paid=1,Partial Paid=2',
  `due` decimal(16,2) NOT NULL DEFAULT 0.00,
  `advance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salary_generates`
--

INSERT INTO `salary_generates` (`id`, `user_id`, `month`, `amount`, `status`, `due`, `advance`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, '2022-09', '7000.00', 0, '0.00', '0.00', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(2, 2, '2022-09', '9000.00', 0, '0.00', '0.00', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(3, 3, '2022-09', '7000.00', 0, '0.00', '0.00', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(4, 4, '2022-09', '7000.00', 0, '0.00', '0.00', NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_send_settings`
--

CREATE TABLE `sms_send_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sms_send_status` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Parcel Create, 2=Delivered Cancel Customer, 3=Delivered Cancel Merchant',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_send_settings`
--

INSERT INTO `sms_send_settings` (`id`, `sms_send_status`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(2, 2, 0, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(3, 3, 0, '2022-09-24 06:53:29', '2022-09-24 06:53:29');

-- --------------------------------------------------------

--
-- Table structure for table `sms_settings`
--

CREATE TABLE `sms_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gateway` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_url` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_settings`
--

INSERT INTO `sms_settings` (`id`, `gateway`, `api_key`, `secret_key`, `username`, `user_password`, `api_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'REVE SMS', 'a7e4166cc9967d80', 'e863dd2f', '', '', 'http://smpp.ajuratech.com:7788/sendtext', 1, '2022-09-24 06:53:29', '2022-09-24 06:53:29');

-- --------------------------------------------------------

--
-- Table structure for table `subscribes`
--

CREATE TABLE `subscribes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscribes`
--

INSERT INTO `subscribes` (`id`, `email`, `created_at`, `updated_at`) VALUES
(1, 'polashm616@gmail.com', '2022-09-04 10:51:28', '2022-09-04 10:51:28'),
(2, 'admin.wemaxit@gmail.com', '2022-09-04 11:31:27', '2022-09-04 11:31:27'),
(3, 'info@rxcourier.com.bd', '2022-09-04 11:41:16', '2022-09-04 11:41:16'),
(4, 'aa@gmail.com', '2022-09-04 15:35:28', '2022-09-04 15:35:28'),
(5, 'j@gmail.com', '2022-09-04 15:35:46', '2022-09-04 15:35:46'),
(6, 'shop@gmail.com', '2022-09-04 17:29:40', '2022-09-04 17:29:40'),
(7, 'mni.jibon@gmail.com', '2022-09-04 18:05:06', '2022-09-04 18:05:06'),
(8, 'sakilrock@gmail.com', '2022-09-05 02:27:25', '2022-09-05 02:27:25'),
(9, 'sojolst07@gmail.com', '2022-09-11 14:14:07', '2022-09-11 14:14:07');

-- --------------------------------------------------------

--
-- Table structure for table `supports`
--

CREATE TABLE `supports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `attached_file` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supports`
--

INSERT INTO `supports` (`id`, `user_id`, `department_id`, `service`, `priority`, `subject`, `description`, `date`, `attached_file`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'delivery', 'medium', 'Pickup Problem support', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24', NULL, '2022-09-24 10:31:23', '2022-09-24 10:31:23'),
(2, 1, 5, 'delivery', 'medium', 'Delivery problem support', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', '2022-09-24', NULL, '2022-09-24 10:32:00', '2022-09-24 10:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `support_chats`
--

CREATE TABLE `support_chats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `support_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `attached_file` bigint(20) UNSIGNED DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `to_dos`
--

CREATE TABLE `to_dos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date` date DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'pending       = 1,\r\n                procesing      = 2,\r\n                complete       = 3',
  `note` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `to_dos`
--

INSERT INTO `to_dos` (`id`, `title`, `description`, `user_id`, `date`, `status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Todo list 1', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 2, '2022-09-24', 1, NULL, '2022-09-24 10:30:28', '2022-09-24 10:30:28'),
(2, 'Todo list 2', '\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\"', 3, '2022-09-24', 1, NULL, '2022-09-24 10:30:44', '2022-09-24 10:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `three` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `original`, `one`, `two`, `three`, `created_at`, `updated_at`) VALUES
(1, 'uploads/users/user.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'uploads/users/user2.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'uploads/users/user3.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'uploads/users/user4.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'uploads/users/user5.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'uploads/users/user6.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(7, 'uploads/users/user7.png', NULL, NULL, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(8, 'uploads/users/user8.png', NULL, NULL, NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(9, 'uploads/users/user9.png', NULL, NULL, NULL, '2022-09-24 06:53:29', '2022-09-24 06:53:29'),
(10, '', NULL, NULL, NULL, '2022-09-24 07:02:47', '2022-09-24 07:02:47'),
(11, '', NULL, NULL, NULL, '2022-09-24 07:03:02', '2022-09-24 07:03:02'),
(12, '', NULL, NULL, NULL, '2022-09-24 07:03:22', '2022-09-24 07:03:22'),
(13, '', NULL, NULL, NULL, '2022-09-24 10:12:47', '2022-09-24 10:12:47'),
(14, '', NULL, NULL, NULL, '2022-09-24 10:13:32', '2022-09-24 10:13:32'),
(15, '', NULL, NULL, NULL, '2022-09-24 10:39:27', '2022-09-24 10:39:27'),
(16, '', NULL, NULL, NULL, '2022-09-24 10:40:18', '2022-09-24 10:40:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nid_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hub_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type` tinyint(3) UNSIGNED DEFAULT 1 COMMENT '1=Admin, 2=Merchant, 3=DeliveryMan, 4=In-Charge',
  `image_id` bigint(20) UNSIGNED DEFAULT NULL,
  `joining_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `permissions` text COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `otp` int(11) DEFAULT NULL,
  `salary` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `verification_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `mobile`, `nid_number`, `designation_id`, `department_id`, `hub_id`, `user_type`, `image_id`, `joining_date`, `address`, `role_id`, `permissions`, `otp`, `salary`, `status`, `verification_status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Wemaxit', 'admin@wemaxit.com', NULL, '$2y$10$oRHHkzWxGeIN.4EslxdrrujPM6Y05p8jEcTErhA4iF3p8YL/VFyaK', '01912938002', '12345678912', NULL, NULL, NULL, 1, 1, '2022-01-01', 'Mirpur-10, Dhaka-1216', 1, '[\"dashboard_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"database_backup_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"account_heads_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"parcel_status_update\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"sms_settings_status_change\",\"sms_send_settings_status_change\",\"bank_transaction_read\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]', NULL, '7000.00', 1, 1, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(2, 'User', 'user@gmail.com', NULL, '$2y$10$.5sckqXAZn7hiW4s4eMZ9OzRmi3RKsB45XYcI9RT8vzqFz7JbXQju', '01478523690', '12345678910', 2, 2, 2, 1, 6, '2022-04-20', 'Mirpur-10, Dhaka-1216', 2, '[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"database_backup_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"expense_read\",\"todo_read\",\"account_heads_read\",\"fund_transfer_read\",\"role_read\",\"salary_read\",\"support_read\",\"general_settings_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"cash_received_from_delivery_man_read\",\"cash_received_from_delivery_man_create\",\"cash_received_from_delivery_man_update\",\"cash_received_from_delivery_man_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]', NULL, '9000.00', 1, 1, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(3, 'User 2', 'user2@gmail.com', NULL, '$2y$10$jEHSLDfEi6/ZgGp8eFhNAeJM4sIerRibLkXglLGG/BACebZyaT6/m', '01478523691', '12345678911', 3, 3, 3, 1, 7, '2022-05-08', 'Mirpur-10, Dhaka-1216', 2, '[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"database_backup_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"expense_read\",\"todo_read\",\"account_heads_read\",\"fund_transfer_read\",\"role_read\",\"salary_read\",\"support_read\",\"general_settings_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"cash_received_from_delivery_man_read\",\"cash_received_from_delivery_man_create\",\"cash_received_from_delivery_man_update\",\"cash_received_from_delivery_man_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\"]', NULL, '7000.00', 1, 1, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(4, 'Delivery Man', 'deliveryman@wemaxit.com', NULL, '$2y$10$K45SPq2zeCJZ0fz/lMOp7eMlnvBp1E6qQMEZS8QOoHe2SsCbJznxW', '01912938004', NULL, NULL, NULL, 1, 3, 3, NULL, 'Mirpur-2,Dhaka', NULL, '[]', NULL, '7000.00', 1, 1, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(5, 'Merchant', 'merchant@wemaxit.com', NULL, '$2y$10$8REo/oQuNf8kcZlCt6wLkeHnHWEcaDcI8DPAwLSxWChF/DS.XPYlu', '01912938003', NULL, NULL, NULL, 4, 2, 2, NULL, 'Mirpur-2,Dhaka', NULL, '[]', NULL, '0.00', 1, 1, NULL, '2022-09-24 06:53:28', '2022-09-24 06:53:28'),
(6, 'User 3', 'user3@gmail.com', NULL, '$2y$10$haaCP1LmV1FqwQf2a.pXTe5waI4bteCfURYxF0eblnGxqZ5ZD9NwW', '01820064123', '', 1, 4, 1, 1, 16, '2022-09-24', 'Mirpur 2 , Dhaka', 1, '[\"dashboard_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"cash_received_from_delivery_man_read\",\"cash_received_from_delivery_man_create\",\"cash_received_from_delivery_man_update\"]', NULL, '9000.00', 1, 1, NULL, '2022-09-24 10:40:18', '2022-09-24 10:40:18');

-- --------------------------------------------------------

--
-- Table structure for table `vat_statements`
--

CREATE TABLE `vat_statements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parcel_id` bigint(20) UNSIGNED NOT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vat_statements`
--

INSERT INTO `vat_statements` (`id`, `parcel_id`, `type`, `amount`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, 20, 1, '5.50', '2022-09-24 13:02:08', 'Parcel successfully delivered.', '2022-09-24 07:02:08', '2022-09-24 07:02:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accounts_user_id_foreign` (`user_id`);

--
-- Indexes for table `account_heads`
--
ALTER TABLE `account_heads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `assetcategories`
--
ALTER TABLE `assetcategories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assets_author_foreign` (`author`),
  ADD KEY `assets_hub_id_foreign` (`hub_id`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_transactions_hub_id_foreign` (`hub_id`),
  ADD KEY `bank_transactions_fund_transfer_id_foreign` (`fund_transfer_id`),
  ADD KEY `bank_transactions_account_id_foreign` (`account_id`),
  ADD KEY `bank_transactions_income_id_foreign` (`income_id`);

--
-- Indexes for table `cash_received_from_deliverymen`
--
ALTER TABLE `cash_received_from_deliverymen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_received_from_deliverymen_user_id_foreign` (`user_id`),
  ADD KEY `cash_received_from_deliverymen_hub_id_foreign` (`hub_id`),
  ADD KEY `cash_received_from_deliverymen_account_id_foreign` (`account_id`),
  ADD KEY `cash_received_from_deliverymen_delivery_man_id_foreign` (`delivery_man_id`),
  ADD KEY `cash_received_from_deliverymen_receipt_foreign` (`receipt`);

--
-- Indexes for table `categorys`
--
ALTER TABLE `categorys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `configs`
--
ALTER TABLE `configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courier_statements`
--
ALTER TABLE `courier_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `courier_statements_income_id_foreign` (`income_id`),
  ADD KEY `courier_statements_parcel_id_foreign` (`parcel_id`),
  ADD KEY `courier_statements_delivery_man_id_foreign` (`delivery_man_id`);

--
-- Indexes for table `deliverycategories`
--
ALTER TABLE `deliverycategories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliveryman_statements`
--
ALTER TABLE `deliveryman_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deliveryman_statements_parcel_id_foreign` (`parcel_id`),
  ADD KEY `deliveryman_statements_delivery_man_id_foreign` (`delivery_man_id`);

--
-- Indexes for table `delivery_charges`
--
ALTER TABLE `delivery_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_charges_category_id_foreign` (`category_id`);

--
-- Indexes for table `delivery_man`
--
ALTER TABLE `delivery_man`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_man_user_id_foreign` (`user_id`),
  ADD KEY `delivery_man_driving_license_image_id_foreign` (`driving_license_image_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expenses_account_head_id_foreign` (`account_head_id`),
  ADD KEY `expenses_merchant_id_foreign` (`merchant_id`),
  ADD KEY `expenses_delivery_man_id_foreign` (`delivery_man_id`),
  ADD KEY `expenses_user_id_foreign` (`user_id`),
  ADD KEY `expenses_parcel_id_foreign` (`parcel_id`),
  ADD KEY `expenses_account_id_foreign` (`account_id`),
  ADD KEY `expenses_receipt_foreign` (`receipt`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `frauds`
--
ALTER TABLE `frauds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `frauds_created_by_foreign` (`created_by`);

--
-- Indexes for table `fund_transfers`
--
ALTER TABLE `fund_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fund_transfers_from_account_foreign` (`from_account`),
  ADD KEY `fund_transfers_to_account_foreign` (`to_account`);

--
-- Indexes for table `general_settings`
--
ALTER TABLE `general_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hubs`
--
ALTER TABLE `hubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hub_incharges`
--
ALTER TABLE `hub_incharges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hub_payments`
--
ALTER TABLE `hub_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hub_payments_hub_id_foreign` (`hub_id`),
  ADD KEY `hub_payments_from_account_foreign` (`from_account`),
  ADD KEY `hub_payments_reference_file_foreign` (`reference_file`);

--
-- Indexes for table `hub_statements`
--
ALTER TABLE `hub_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hub_statements_user_id_foreign` (`user_id`),
  ADD KEY `hub_statements_hub_id_foreign` (`hub_id`),
  ADD KEY `hub_statements_account_id_foreign` (`account_id`),
  ADD KEY `hub_statements_delivery_man_id_foreign` (`delivery_man_id`);

--
-- Indexes for table `incomes`
--
ALTER TABLE `incomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incomes_account_head_id_foreign` (`account_head_id`),
  ADD KEY `incomes_user_id_foreign` (`user_id`),
  ADD KEY `incomes_merchant_id_foreign` (`merchant_id`),
  ADD KEY `incomes_delivery_man_id_foreign` (`delivery_man_id`),
  ADD KEY `incomes_parcel_id_foreign` (`parcel_id`),
  ADD KEY `incomes_account_id_foreign` (`account_id`),
  ADD KEY `incomes_hub_id_foreign` (`hub_id`),
  ADD KEY `incomes_hub_user_id_foreign` (`hub_user_id`),
  ADD KEY `incomes_hub_user_account_id_foreign` (`hub_user_account_id`),
  ADD KEY `incomes_receipt_foreign` (`receipt`);

--
-- Indexes for table `merchants`
--
ALTER TABLE `merchants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchants_user_id_foreign` (`user_id`),
  ADD KEY `merchants_nid_id_foreign` (`nid_id`),
  ADD KEY `merchants_trade_license_foreign` (`trade_license`);

--
-- Indexes for table `merchant_delivery_charges`
--
ALTER TABLE `merchant_delivery_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_delivery_charges_merchant_id_foreign` (`merchant_id`),
  ADD KEY `merchant_delivery_charges_delivery_charge_id_foreign` (`delivery_charge_id`);

--
-- Indexes for table `merchant_payments`
--
ALTER TABLE `merchant_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_payments_merchant_id_foreign` (`merchant_id`);

--
-- Indexes for table `merchant_shops`
--
ALTER TABLE `merchant_shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_shops_merchant_id_foreign` (`merchant_id`);

--
-- Indexes for table `merchant_statements`
--
ALTER TABLE `merchant_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_statements_parcel_id_foreign` (`parcel_id`),
  ADD KEY `merchant_statements_merchant_id_foreign` (`merchant_id`),
  ADD KEY `merchant_statements_delivery_man_id_foreign` (`delivery_man_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_offers`
--
ALTER TABLE `news_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_offers_author_foreign` (`author`),
  ADD KEY `news_offers_file_foreign` (`file`);

--
-- Indexes for table `packagings`
--
ALTER TABLE `packagings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcels_merchant_id_foreign` (`merchant_id`),
  ADD KEY `parcels_hub_id_foreign` (`hub_id`),
  ADD KEY `parcels_transfer_hub_id_foreign` (`transfer_hub_id`);

--
-- Indexes for table `parcel_events`
--
ALTER TABLE `parcel_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcel_events_parcel_id_foreign` (`parcel_id`),
  ADD KEY `parcel_events_delivery_man_id_foreign` (`delivery_man_id`),
  ADD KEY `parcel_events_pickup_man_id_foreign` (`pickup_man_id`),
  ADD KEY `parcel_events_hub_id_foreign` (`hub_id`),
  ADD KEY `parcel_events_transfer_delivery_man_id_foreign` (`transfer_delivery_man_id`),
  ADD KEY `parcel_events_created_by_foreign` (`created_by`);

--
-- Indexes for table `parcel_logs`
--
ALTER TABLE `parcel_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcel_logs_merchant_id_foreign` (`merchant_id`),
  ADD KEY `parcel_logs_hub_id_foreign` (`hub_id`),
  ADD KEY `parcel_logs_delivery_man_id_foreign` (`delivery_man_id`),
  ADD KEY `parcel_logs_parcel_id_foreign` (`parcel_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_merchant_id_foreign` (`merchant_id`),
  ADD KEY `payments_from_account_foreign` (`from_account`),
  ADD KEY `payments_reference_file_foreign` (`reference_file`);

--
-- Indexes for table `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_accounts_merchant_id_foreign` (`merchant_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `phonebooks`
--
ALTER TABLE `phonebooks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pickup_requests_merchant_id_foreign` (`merchant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salaries_user_id_foreign` (`user_id`),
  ADD KEY `salaries_account_id_foreign` (`account_id`);

--
-- Indexes for table `salary_generates`
--
ALTER TABLE `salary_generates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salary_generates_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `sms_send_settings`
--
ALTER TABLE `sms_send_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscribes`
--
ALTER TABLE `subscribes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supports`
--
ALTER TABLE `supports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supports_user_id_foreign` (`user_id`),
  ADD KEY `supports_department_id_foreign` (`department_id`);

--
-- Indexes for table `support_chats`
--
ALTER TABLE `support_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `support_chats_support_id_foreign` (`support_id`),
  ADD KEY `support_chats_user_id_foreign` (`user_id`);

--
-- Indexes for table `to_dos`
--
ALTER TABLE `to_dos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to_dos_user_id_foreign` (`user_id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_designation_id_foreign` (`designation_id`),
  ADD KEY `users_department_id_foreign` (`department_id`),
  ADD KEY `users_hub_id_foreign` (`hub_id`),
  ADD KEY `users_image_id_foreign` (`image_id`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indexes for table `vat_statements`
--
ALTER TABLE `vat_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vat_statements_parcel_id_foreign` (`parcel_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `account_heads`
--
ALTER TABLE `account_heads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `assetcategories`
--
ALTER TABLE `assetcategories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cash_received_from_deliverymen`
--
ALTER TABLE `cash_received_from_deliverymen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categorys`
--
ALTER TABLE `categorys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `configs`
--
ALTER TABLE `configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `courier_statements`
--
ALTER TABLE `courier_statements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deliverycategories`
--
ALTER TABLE `deliverycategories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deliveryman_statements`
--
ALTER TABLE `deliveryman_statements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `delivery_charges`
--
ALTER TABLE `delivery_charges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `delivery_man`
--
ALTER TABLE `delivery_man`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `frauds`
--
ALTER TABLE `frauds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fund_transfers`
--
ALTER TABLE `fund_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `general_settings`
--
ALTER TABLE `general_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hubs`
--
ALTER TABLE `hubs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hub_incharges`
--
ALTER TABLE `hub_incharges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hub_payments`
--
ALTER TABLE `hub_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `hub_statements`
--
ALTER TABLE `hub_statements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incomes`
--
ALTER TABLE `incomes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `merchants`
--
ALTER TABLE `merchants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `merchant_delivery_charges`
--
ALTER TABLE `merchant_delivery_charges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `merchant_payments`
--
ALTER TABLE `merchant_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `merchant_shops`
--
ALTER TABLE `merchant_shops`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `merchant_statements`
--
ALTER TABLE `merchant_statements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `news_offers`
--
ALTER TABLE `news_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `packagings`
--
ALTER TABLE `packagings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `parcel_events`
--
ALTER TABLE `parcel_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `parcel_logs`
--
ALTER TABLE `parcel_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_accounts`
--
ALTER TABLE `payment_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phonebooks`
--
ALTER TABLE `phonebooks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salary_generates`
--
ALTER TABLE `salary_generates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sms_send_settings`
--
ALTER TABLE `sms_send_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sms_settings`
--
ALTER TABLE `sms_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscribes`
--
ALTER TABLE `subscribes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `supports`
--
ALTER TABLE `supports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `support_chats`
--
ALTER TABLE `support_chats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `to_dos`
--
ALTER TABLE `to_dos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vat_statements`
--
ALTER TABLE `vat_statements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_author_foreign` FOREIGN KEY (`author`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assets_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`);

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_fund_transfer_id_foreign` FOREIGN KEY (`fund_transfer_id`) REFERENCES `fund_transfers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_income_id_foreign` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cash_received_from_deliverymen`
--
ALTER TABLE `cash_received_from_deliverymen`
  ADD CONSTRAINT `cash_received_from_deliverymen_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_received_from_deliverymen_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_received_from_deliverymen_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_received_from_deliverymen_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_received_from_deliverymen_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courier_statements`
--
ALTER TABLE `courier_statements`
  ADD CONSTRAINT `courier_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courier_statements_income_id_foreign` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courier_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveryman_statements`
--
ALTER TABLE `deliveryman_statements`
  ADD CONSTRAINT `deliveryman_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveryman_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_charges`
--
ALTER TABLE `delivery_charges`
  ADD CONSTRAINT `delivery_charges_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `deliverycategories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_man`
--
ALTER TABLE `delivery_man`
  ADD CONSTRAINT `delivery_man_driving_license_image_id_foreign` FOREIGN KEY (`driving_license_image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_man_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_account_head_id_foreign` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `frauds`
--
ALTER TABLE `frauds`
  ADD CONSTRAINT `frauds_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fund_transfers`
--
ALTER TABLE `fund_transfers`
  ADD CONSTRAINT `fund_transfers_from_account_foreign` FOREIGN KEY (`from_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fund_transfers_to_account_foreign` FOREIGN KEY (`to_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hub_payments`
--
ALTER TABLE `hub_payments`
  ADD CONSTRAINT `hub_payments_from_account_foreign` FOREIGN KEY (`from_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hub_payments_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hub_payments_reference_file_foreign` FOREIGN KEY (`reference_file`) REFERENCES `uploads` (`id`);

--
-- Constraints for table `hub_statements`
--
ALTER TABLE `hub_statements`
  ADD CONSTRAINT `hub_statements_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hub_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hub_statements_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hub_statements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incomes`
--
ALTER TABLE `incomes`
  ADD CONSTRAINT `incomes_account_head_id_foreign` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_hub_user_account_id_foreign` FOREIGN KEY (`hub_user_account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_hub_user_id_foreign` FOREIGN KEY (`hub_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchants`
--
ALTER TABLE `merchants`
  ADD CONSTRAINT `merchants_nid_id_foreign` FOREIGN KEY (`nid_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merchants_trade_license_foreign` FOREIGN KEY (`trade_license`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merchants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_delivery_charges`
--
ALTER TABLE `merchant_delivery_charges`
  ADD CONSTRAINT `merchant_delivery_charges_delivery_charge_id_foreign` FOREIGN KEY (`delivery_charge_id`) REFERENCES `delivery_charges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merchant_delivery_charges_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_payments`
--
ALTER TABLE `merchant_payments`
  ADD CONSTRAINT `merchant_payments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_shops`
--
ALTER TABLE `merchant_shops`
  ADD CONSTRAINT `merchant_shops_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_statements`
--
ALTER TABLE `merchant_statements`
  ADD CONSTRAINT `merchant_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merchant_statements_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merchant_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news_offers`
--
ALTER TABLE `news_offers`
  ADD CONSTRAINT `news_offers_author_foreign` FOREIGN KEY (`author`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_offers_file_foreign` FOREIGN KEY (`file`) REFERENCES `uploads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `parcels_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`),
  ADD CONSTRAINT `parcels_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcels_transfer_hub_id_foreign` FOREIGN KEY (`transfer_hub_id`) REFERENCES `hubs` (`id`);

--
-- Constraints for table `parcel_events`
--
ALTER TABLE `parcel_events`
  ADD CONSTRAINT `parcel_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_events_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_events_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_events_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_events_pickup_man_id_foreign` FOREIGN KEY (`pickup_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_events_transfer_delivery_man_id_foreign` FOREIGN KEY (`transfer_delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parcel_logs`
--
ALTER TABLE `parcel_logs`
  ADD CONSTRAINT `parcel_logs_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_logs_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_logs_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parcel_logs_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_from_account_foreign` FOREIGN KEY (`from_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_reference_file_foreign` FOREIGN KEY (`reference_file`) REFERENCES `uploads` (`id`);

--
-- Constraints for table `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD CONSTRAINT `payment_accounts_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD CONSTRAINT `pickup_requests_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_generates`
--
ALTER TABLE `salary_generates`
  ADD CONSTRAINT `salary_generates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supports`
--
ALTER TABLE `supports`
  ADD CONSTRAINT `supports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_chats`
--
ALTER TABLE `support_chats`
  ADD CONSTRAINT `support_chats_support_id_foreign` FOREIGN KEY (`support_id`) REFERENCES `supports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_chats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `to_dos`
--
ALTER TABLE `to_dos`
  ADD CONSTRAINT `to_dos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vat_statements`
--
ALTER TABLE `vat_statements`
  ADD CONSTRAINT `vat_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
