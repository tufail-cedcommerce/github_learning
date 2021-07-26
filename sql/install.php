<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   CedElkjopnordic
 */

$sql = array();

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_install` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `website` int(11) DEFAULT NULL,
          `store` int(11) DEFAULT NULL,
          `extra` text,
          PRIMARY KEY (`id`)
        ) ;";

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_category_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` text NOT NULL,
  `elkjopnordic_category` longtext NOT NULL,
  `category_attributes` longtext NOT NULL,
  `attribute_mappings` longtext NOT NULL,
  `default_values` longtext NOT NULL,
  `variants_mappings` longtext NOT NULL,
  `id_shop` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ;";

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_order` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `order_place_date` text DEFAULT NULL COMMENT 'Order Place Date',
          `order_id` int(11) DEFAULT NULL COMMENT 'Opencart Order Id',
          `status` text COMMENT 'status',
          `order_data` longtext COMMENT 'Order Data',
          `shipment_data` longtext COMMENT 'Shipping Data',
          `elkjopnordic_order_id` text COMMENT 'Reference Order Id',
          `shipment_request_data` text COMMENT 'Shipment Data send on elkjopnordic',
          `shipment_response_data` text COMMENT 'Shipment Data get from elkjopnordic',
          PRIMARY KEY (`id`)
      ) ;";

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_order_error` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `elkjopnordic_order_id` text NOT NULL COMMENT 'Purchase Order Id',
          `merchant_sku` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Reference_Number',
          `reason` text NOT NULL COMMENT 'Reason',
          `order_data` text NOT NULL COMMENT 'Order Data',
          `viewed_order` int(10) NOT NULL Default 0,
          `id_shop` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_price_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_created` datetime NOT NULL,
  `has_error_report` int(11) NOT NULL,
  `lines_in_error` int(11) NOT NULL,
  `lines_in_pending` int(11) NOT NULL,
  `lines_in_success` int(11) NOT NULL,
  `import_id` bigint(20) NOT NULL,
  `status` text NOT NULL,
  `lines_read` int(11) NOT NULL,
  `mode` text NOT NULL,
  `offer_deleted` int(11) NOT NULL,
  `offer_inserted` int(11) NOT NULL,
  `offer_updated` int(11) NOT NULL,
  `type` text NOT NULL,
  `error_file` text NOT NULL,
  `id_shop` int(11) NOT NULL,
   PRIMARY KEY (`id`)
)";

$sql[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."cedelkjopnordic_logs` (
  `id` int(15) NOT NULL AUTO_INCREMENT,
  `method` text NOT NULL,   
  `type` varchar(150) NOT NULL,
  `message` text NOT NULL,   
  `data` longtext NOT NULL,   
  `created_at` text NOT NULL,   
  `id_shop` int(11) NOT NULL,
 PRIMARY KEY (`id`) 
);";

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,  
  `title` text NOT NULL,   
  `status` int(11) NOT NULL,
  `store_category` longtext NOT NULL,
  `product_manufacturer` text NOT NULL,
  `elkjopnordic_categories` longtext NOT NULL,
  `elkjopnordic_category` longtext NOT NULL,
  `profile_attribute_mapping` longtext NOT NULL,
  `profile_option_mapping` longtext NOT NULL,
  `profile_default_mapping` text NOT NULL,
  `profile_additional_info` text NOT NULL,
  `id_shop` int(11) NOT NULL,
 PRIMARY KEY (`id`) 
)";

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_categories` (
            `id` int(15) NOT NULL auto_increment,
            `category_id` text NOT NULL,
            `category_data` longtext,
            `level` int(5) NOT NULL,
            `id_shop` int(11) NOT NULL,
            PRIMARY KEY (`id`)
              )';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_attributes` (
           `id` int(15) NOT NULL auto_increment,
           `category_id` text NOT NULL,
           `attribute_code` text NOT NULL,
           `attribute_label` text NOT NULL,
           `default_value` text NOT NULL,
           `required` tinyint(1) NOT NULL,
           `is_variant` tinyint(1) NOT NULL,
           `attribute_type` varchar(50) NOT NULL,
           `values_list` varchar(50),
           `values` longtext,
           PRIMARY KEY (`id`)
           )';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_attribute_options` (
           `id` int(15) NOT NULL auto_increment,
           `category_id` text NOT NULL,
           `attribute_code` text NOT NULL,
           `values_list` text NOT NULL,
           `default_value` text NOT NULL,
           `value_code` text NOT NULL,
           `value_label` text NOT NULL,
           PRIMARY KEY (`id`)
           )';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_profile_products` (
           `id` int(11) NOT NULL auto_increment,
           `id_product` int(11) NOT NULL,
           `id_cedelkjopnordic_profile` int(11) NOT NULL,
           PRIMARY KEY (`id`)
           )';

$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedelkjopnordic_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT ,
  `id_product` int(11) NOT NULL,
  `product_feed_status` int(11) NOT NULL,
  `elkjopnordic_status` text NOT NULL,
  `price` float NOT NULL,
  `data` text NOT NULL,
  `error_message` longtext NOT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ;";

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_products_chunk` (
            `id` int(15) NOT NULL auto_increment,
            `key` varchar(255) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `values` longtext,
            PRIMARY KEY (`id`)
              )';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_products_feed` (
            `id` int(10) NOT NULL auto_increment,
            `import_id` text NOT NULL,
            `id_shop` int(11) NOT NULL,
            `has_new_product_report` text NOT NULL,
            `has_transformation_error_report` text NOT NULL,
            `date_created` datetime NOT NULL,
            `transform_lines_read` text NOT NULL,
            `transform_lines_in_error` text NOT NULL,
            `transform_lines_in_success` text NOT NULL,
            `has_error_report` text NOT NULL,
            `import_status` text NOT NULL,
            `has_transformed_file` text NOT NULL,
            `shop_id` int(11) NOT NULL,
            `transform_lines_with_warning` text NOT NULL,
            `error_file` text NOT NULL,
            `feed_file` text NOT NULL,
            PRIMARY KEY (`id`)
              )';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cedelkjopnordic_offers_feed` (
            `id` int(11) NOT NULL auto_increment,
            `date_created` datetime NOT NULL,
            `has_error_report` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `lines_in_error` int(11) NOT NULL,
            `lines_in_pending` int(11) NOT NULL,
            `lines_in_success` int(11) NOT NULL,
            `import_id` bigint(20) NOT NULL,
            `status` text NOT NULL,
            `lines_read` int(11) NOT NULL,
            `mode` text NOT NULL,
            `offer_deleted` int(11) NOT NULL,
            `offer_inserted` int(11) NOT NULL,
            `offer_updated` int(11) NOT NULL,
            `type` text NOT NULL,
            `error_file` text NOT NULL,
            `feed_file` text NOT NULL,
            PRIMARY KEY (`id`)
              )';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
