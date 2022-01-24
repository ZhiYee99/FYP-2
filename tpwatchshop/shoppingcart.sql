CREATE DATABASE IF NOT EXISTS `tpwatchshop` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `tpwatchshop`;

CREATE TABLE IF NOT EXISTS `accounts` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `address_street` varchar(255) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_state` varchar(100) NOT NULL,
  `address_zip` varchar(50) NOT NULL,
  `address_country` varchar(100) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `name_based_on_card` varchar(50) NOT NULL,
  `card_type` varchar(50) NOT NULL,
  `expire_month` varchar(50) NOT NULL,
  `expire_year` varchar(50) NOT NULL,
  `CVV` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO `accounts` (`id`, `email`, `password`, `first_name`, `last_name`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `admin`,
`name_based_on_card`, `card_type`, `expire_month`, `expire_year`, `CVV`) VALUES
(1, 'admin@gmail.com', '$2y$10$pEHRAE4Ia0mE9BdLmbS.ueQsv/.WlTUSW7/cqF/T36iW.zDzSkx4y', 'John', 'Wang', '98 Jln Bukit Indah', 'Johor Bahru', 'JB', '81200', 'Malaysia', 1,'John Wang','Visa','08','23','202cb962ac59075b964b07152d234b70'),
(2, 'test@gmail.com', '$2y$10$1O422RLitHxOQeC3GNRhneDdhAQ0JRa3aRInDmMz2QGw5vM4jV5q2', 'Tesla', 'Ho', '123 Jln ABC', 'Johor Bahru', 'JB', '81200', 'Malaysia', 0,'Tesla Ho','Master','12','25','250cf8b51c773f3f8dc8b4be867a9a02');


CREATE TABLE IF NOT EXISTS `categories` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Alba'),
(2, 'Bonia'),
(3, 'Casio'),
(4, 'Seiko'),
(5, 'Tissot');

CREATE TABLE IF NOT EXISTS `discounts` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `category_ids` varchar(50) NOT NULL,
  `product_ids` varchar(50) NOT NULL,
  `discount_code` varchar(50) NOT NULL,
  `discount_type` enum('Percentage','Fixed') NOT NULL,
  `discount_value` decimal(7,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO `discounts` (`id`, `category_ids`, `product_ids`, `discount_code`, `discount_type`, `discount_value`, `start_date`, `end_date`) VALUES
(1, '', '', 'newyear2022', 'Percentage', '5.00', '2022-01-01 00:00:00', '2022-01-31 00:00:00');

CREATE TABLE IF NOT EXISTS `products` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `features` text NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `rrp` decimal(7,2) NOT NULL DEFAULT '0.00',
  `quantity` int(11) NOT NULL,
  `img` text NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `weight` decimal(7,2) NOT NULL DEFAULT '0.00',
  `url_structure` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

INSERT INTO `products` (`id`, `name`, `description`, `features`, `price`, `rrp`, `quantity`, `img`, `date_added`, `weight`, `url_structure`) VALUES
(1, 'Seiko SUMO Men Watch', 
'<p>Unique watch made with stainless steel, ideal for those that prefer interative watches.</p>',
'\r\n<h4>Movement</h4>\r\n
<ul>\r\n
<li>Caliber Number: 6R35</li>
<li>Movement Type: Automatic with manual winding</li>
<li>Accuracy: +25 to -15 seconds per day</li>
<li>Duration: Approx. 70 hours</li></ul>
\r\n<h4>Other Details</h4>\r\n<ul>\r\n<li>Case Material: Stainless steel</li>
<li>Crystal: Sapphire crystal</li><li>Water Resistance: 200m / 660ft divers</li></ul>', 
'1029.00', '0.00', 2, 'spb103.png', '2021-10-25 18:55:22', '0.00', '', 'seikospb103'),

(2, 'Casio Digital Watch', '<p>A tried and true style that always remains in fashion. With its daily alarm, hourly time signal and auto calendar, 
you will never need to worry about missing an appointment again.</p>',
'\r\n<ul>\r\n
<li>EL Backlight</li>
<li>Daily Alarm</li>
<li>Water Resistant</li>
</ul>', 
'299.00', '399.00', 3, 'casiodigital.png', '2021-10-25 18:30:49', '0.00', '', ''),

(3, 'Alba Silver Watch', 'Fashionable men 3-hand analog with a hint of color. ', '
<ul><li>Dial:Silver white dial</li>
<li>Hand:Hour, minute, second</li>
<li>Date:Date</li>
<li>Water Resistant:5 bar water resistant</li>
<li>Material:Stainless steel case and screw case back</li>
<li>Band:Stainless steel side wrapped bracelet</li>
<li>Glass:Dome glass</li>
<li>Case Size:30mm</li></ul>', '399.99', '0.00', 5, 'albasilver.png', '2021-10-25 18:47:56', '0.00', '', ''),

(4, 'Tissot Chronograph', 
'The Tissot Gentleman is a multi-purpose watch, both ergonomic and elegant in any circumstance. It is equally suitable for wearing in a business environment, where conventional dress codes apply, as at the weekend, when it adapts easily to leisure activities. As part of the life of a modern, active man, the Tissot Gentleman becomes the perfect companion for every day, every occasion and every style.', 
'<ul><li>SKU: T1274071104100</li>
<li>Collection: T-Classic</li>
<li>Water resistance: 100 m / 330 ft</li>
<li>Warranty: 2 Years of Warranty</li>
<li>Jewels: 25</li>
<li>Movement: Swiss automatic</li>
<li>Power reserve: power reserve up to 80 hours</li></ul>', '1269.99', '0.00', 5, 'tissot.jpg', '2021-10-25 17:42:0', '0.00', '', '');



CREATE TABLE IF NOT EXISTS `products_categories` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO `products_categories` (`id`, `product_id`, `category_id`) VALUES
(1, 1, 4),
(2, 2, 3),
(3, 3, 1),
(4, 4, 5);

CREATE TABLE IF NOT EXISTS `products_images` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `img` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

INSERT INTO `products_images` (`id`, `product_id`, `img`) VALUES
(1, 1, 'spb103.png'),
(2, 1, 'spb103_1.png'),
(3, 1, 'spb103_2.png'),
(4, 2, 'casiodigital.png'),
(5, 3, 'albasilver.png'),
(6, 4, 'tissot.jpg');

CREATE TABLE IF NOT EXISTS `products_options` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `shipping` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price_from` decimal(7,2) NOT NULL,
  `price_to` decimal(7,2) NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `weight_from` decimal(7,2) NOT NULL DEFAULT '0.00',
  `weight_to` decimal(7,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO `shipping` (`id`, `name`, `price_from`, `price_to`, `price`, `weight_from`, `weight_to`) VALUES
(1, 'Standard', '0.00', '99999.00', '8.99', '0.00', '99999.00'),
(2, 'International', '0.00', '99999.00', '20.99', '0.00', '99999.00');

CREATE TABLE IF NOT EXISTS `transactions` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(255) NOT NULL,
  `payment_amount` decimal(7,2) NOT NULL,
  `payment_status` varchar(30) NOT NULL,
  `created` datetime NOT NULL,
  `payer_email` varchar(255) NOT NULL DEFAULT '',
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `address_street` varchar(255) NOT NULL DEFAULT '',
  `address_city` varchar(100) NOT NULL DEFAULT '',
  `address_state` varchar(100) NOT NULL DEFAULT '',
  `address_zip` varchar(50) NOT NULL DEFAULT '',
  `address_country` varchar(100) NOT NULL DEFAULT '',
  `account_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'website',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `transactions_items` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(255) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_price` decimal(7,2) NOT NULL,
  `item_quantity` int(11) NOT NULL,
  `item_options` varchar(255) NOT NULL,
  `item_shipping_price` decimal(7,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `accounts` ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `products_categories` ADD UNIQUE KEY `product_id` (`product_id`,`category_id`);

ALTER TABLE `products_images` ADD UNIQUE KEY `product_id` (`product_id`,`img`);

ALTER TABLE `transactions` ADD UNIQUE KEY `txn_id` (`txn_id`);

ALTER TABLE `products` ADD `inactive` TINYINT(1) DEFAULT 0;
