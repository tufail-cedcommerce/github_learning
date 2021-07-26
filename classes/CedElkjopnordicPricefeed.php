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

class CedElkjopnordicPricefeed extends ObjectModel
{
    public static $definition = array(
        'table' => 'cedelkjopnordic_offers_feed',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'import_id' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'has_transformation_error_report' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'transform_lines_with_warning' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'date_created' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'itemsProcessing' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'transform_lines_in_error' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'transform_lines_in_success' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'transform_lines_read' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'status' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'has_transformed_file' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'shop_id' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
        ),
    );

    public $id;
    public $import_id;
    public $has_transformation_error_report;
    public $transform_lines_with_warning;
    public $date_created;
    public $itemsProcessing;
    public $transform_lines_in_success;
    public $transform_lines_read;
    public $import_status;
    public $has_transformed_file;
    public $shop_id;

    /**
     * @param $id
     * @return array
     */
    public function deleteOfferFeed($id)
    {
        try {
            $db = Db::getInstance();
            $sql = "SELECT * FROM " . _DB_PREFIX_ . "cedelkjopnordic_offers_feed WHERE import_id='" . pSQL($id) . "' AND id_shop = '".(int)Context::getContext()->shop->id."'";
            $res = $db->executeS($sql);
            if ($res) {
                if (isset($res[0]['feed_file'])) {
                    $feed_file = $res[0]['feed_file'];
                    $feed_file = str_replace(_PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/', _PS_MODULE_DIR_, $feed_file);
                    if ($feed_file && $feed_file != '') {
                        unlink($feed_file);
                    }
                }
                if (isset($res[0]['error_file'])) {
                    $error_file = $res[0]['error_file'];
                    $error_file = str_replace(
                        _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/',
                        _PS_MODULE_DIR_,
                        $error_file
                    );
                    if ($error_file && $error_file != '') {
                        unlink($error_file);
                    }
                }

                $query = "DELETE FROM " . _DB_PREFIX_ . "cedelkjopnordic_offers_feed WHERE import_id='" . pSQL($id) . "' AND id_shop = '".(int)Context::getContext()->shop->id."'";
                $res = $db->execute($query);
                if ($res) {
                    return array('success' => true, 'message' => 'Product Feed ' . $id . ' deleted successfully.');
                } else {
                    return array('success' => false, 'message' => 'Something went wrong.');
                }
            }
        } catch (\Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * @param $id
     * @return array
     */
    public function updateFeed($id)
    {
        try {
            $db = Db::getInstance();
            $sql = "SELECT feed_file from " . _DB_PREFIX_ . "cedelkjopnordic_offers_feed WHERE import_id='" . pSQL($id) . "' AND id_shop = '".(int)Context::getContext()->shop->id."'";
            $res = $db->executeS($sql);
            $feed_file = '';
            if (isset($res[0]['feed_file'])) {
                $feed_file = $res[0]['feed_file'];
            }
            $elkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $elkjopnordicProduct->syncFeed($id, 'offers', $feed_file);
            return $result;
        } catch (\Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
