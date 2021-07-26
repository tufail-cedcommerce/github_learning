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
require_once _PS_MODULE_DIR_ . '/cedelkjopnordic/classes/CedElkjopnordicOrder.php';

class CedelkjopnordicOrderModuleFrontController extends ModuleFrontController
{
    public function initContent(){

        if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')) {
            die('Secure key does not match');
        }
        // for multishop comatibility start
        $shop_ids = array();
        $shop_ids[] = Context::getContext()->shop->id;
        
        $CedElkjopnordicHelper = new CedElkjopnordicHelper();
        
        $CedElkjopnordicHelper->log(
            'CronOrderFetch',
            'CRON',
            "Cron Order Fetch Start",
            '{"Message":"Started Successfully"}',
            true,
            $shop_ids
        );
        try {

            $status = $CedElkjopnordicHelper->isEnabled();
           
            if ($status) {
                $CedElkjopnordicOrder = new CedElkjopnordicOrder();
                $url = 'orders';
                $params = array();
                $createdStartDate = date('Y-m-d');
                $order = array('from' => $createdStartDate);
                $order['order_state_codes'] = 'WAITING_ACCEPTANCE';
                 
               foreach($shop_ids as $shop_id){
                   $CedElkjopnordicOrder->fetchOrder($params, $url, $order,false, $shop_id);
               }
                
            } else {
                die(Tools::jsonEncode(array(
                    'success' => false,
                    'message' => 'Module is disable'
                )));
            }
        } catch (Exception $e) {
            $CedElkjopnordicHelper->log(
                'CronOrderFetch',
                'CRON',
                "Cron Sync End",
                $e->getMessage(),
                true,
                $shop_ids
            );
        }
        $CedElkjopnordicHelper->log(
            'CronOrderFetch',
            'CRON',
            "Cron Order Fetch End",
            '{"Message":"Executed Successfully"}',
            true,
            $shop_ids
        );
        parent::initContent();
        $this->setTemplate('module:cedelkjopnordic/views/templates/front/orderstatus.tpl');
        die('Executed Successfully.');
    }
}
