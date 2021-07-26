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
 * @package   CedAmazon
 */

require_once _PS_MODULE_DIR_ . '/cedelkjopnordic/classes/CedElkjopnordicHelper.php';
require_once _PS_MODULE_DIR_ . '/cedelkjopnordic/classes/CedElkjopnordicProduct.php';
class CedelkjopnordicProductStockModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!Tools::getIsset('secure_key')
            || Tools::getValue('secure_key') != Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')
        ) {
            die('Secure key does not matched');
        }

        $celkjopnordic_lib = new CedElkjopnordicHelper;
        $celkjopnordic_lib->log('Update Product STOCK CRON Run:');
        try {
        $celkjopnordic_lib->log('Update Product STOCK CRON Started:');
            $status = $celkjopnordic_lib->isEnabled();
            if ($status) {
            
                $celkjopnordic_product = new CedElkjopnordicProduct;
                $ids = Db::getInstance()->executeS("SELECT `id_product` FROM `"._DB_PREFIX_."cedelkjopnordic_profile_products`  
        ORDER BY `id`  DESC ");

                $ids = array_column($ids, 'id_product');
$ids = array_unique($ids);
sort($ids);
               
                $order_data = $celkjopnordic_product->updateOffers($ids);
                $celkjopnordic_lib->log(
                    'CronFetchOrder',
                    'Exeception',
                    '"Cron Order Fetch: data "',
                    json_encode(
                        array(
                            'url' => 'Update Product STOCK',
                            'Request Param' => $ids,
                            'Response' => json_encode($order_data)
                        )
                    ),
                    true
                );
                $celkjopnordic_lib->log('Update Product STOCK:');
            die('Executed');
            }
        } catch (Exception $e) {
            $celkjopnordic_lib->log('Exception on Update Product Status:'.$e->getMessage());
            die;
        }
       
        die('Executed Successfully.');
    }
}
