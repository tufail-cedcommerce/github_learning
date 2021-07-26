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

include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';

class CedelkjopnordicProductModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {

        // for multishop comatibility start
        $shop_ids = array();
        $shop_id = Shop::getContextShopID();
        if($shop_id){
            $shop_ids[] = $shop_id;
        } else {
            $shop_group_id = Shop::getContextShopGroupID();
            if($shop_group_id && !$shop_id){
                $group_shops = Shop::getShops(true,$shop_group_id);
                $group_shop_ids = array_column($group_shops,'id_shop');
                if(!empty($group_shop_ids))
                    $shop_ids = $group_shop_ids;
            }
        }
        // for multishop comatibility end
        if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')) {
            die('Secure key does not match');
        }
        $cvidaxl_lib = new CedElkjopnordicHelper;
        $cvidaxl_lib->log(
            'CronStockSync',
            'CRON',
            "Cron Sync Start",
            '{"Message":"Started Successfully"}',
            true,
            $shop_id
        );
        try {

            $status = $cvidaxl_lib->isEnabled();
            if ($status) {
                $cvidaxl_libp = new CedElkjopnordicProduct();
                $db = Db::getInstance();
                foreach ($shop_ids as $shop_id){
                    $ids = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."cedelkjopnordic_queue` WHERE `key` LIKE 'syncprice' AND `id_shop` = '".(int)$shop_id."' LIMIT 0,5");
                    if(!empty($ids)){
                        foreach($ids as $id){
                            if (isset($id['values']) && $id['values'] && @json_decode($id['values'],true)) {
                                $product_ids_to_process = @json_decode($id['values'],true);

                                if(is_array($product_ids_to_process) && !empty($product_ids_to_process)){
                                    $status = $cvidaxl_libp->updateOffers($product_ids_to_process);
                                    if($status){
                                        Db::getInstance()->execute("DELETE FROM `"._DB_PREFIX_."cedvidaxl_queue` WHERE id='".(int)$id['id']."'");
                                    }
                                }
                            }
                        }

                    } else {
                        $ids = Db::getInstance()->executeS("SELECT `id_product` FROM `"._DB_PREFIX_."cedelkjopnordic_profile_products` cpp JOIN  `"._DB_PREFIX_."cedelkjopnordic_profile` cp ON (cpp.id = cp.id) WHERE cp.id_shop = '".(int)$shop_id."'  
ORDER BY `id`  DESC ");

                        $ids = array_column($ids, 'id_product');

                        $ids = array_chunk($ids, 1000);
                        foreach($ids as $id){
                            $id = array_chunk($id, 250);
                            $sql = "INSERT INTO `"._DB_PREFIX_."cedvidaxl_queue` (`key`, `values`) values ";
                            foreach($id as $i){
                                $sql .= "('syncprice', '".pSQL(json_encode($i))."'),";
                            }

                            $sql = rtrim($sql,', ');
                            $db->execute($sql);
                        }

                    }
                }

                $cvidaxl_lib->log(
                    'CronStockSync',
                    'Cron Log',
                    $shop_id,
                    '',
                    true,
                    $shop_id
                );
            }
        } catch (Exception $e) {
            $cvidaxl_lib->log('Exception on Update Product Status:'.$e);
        }
        $cvidaxl_lib->log(
            'CronStockSync',
            'CRON',
            "Cron Sync End",
            '{"Message":"Executed Successfully"}',
            true,
            $shop_id
        );
        parent::initContent();
        $this->setTemplate('module:cedelkjopnordic/views/templates/front/orderstatus.tpl');
        die('Executed Successfully.');
    }
}
