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

class CedelkjopnordicCreateOrderModuleFrontController extends ModuleFrontController
{
    public function initContent(){
        if (!Tools::getIsset('secure_key') ||
            Tools::getValue('secure_key') != Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')
        ) {
            die('Secure key does not match.');
        }
        $elkjopnordicHelper = new CedElkjopnordicHelper();
        $cedelkjopnordicOrder = new CedElkjopnordicOrder();
        // for multishop comatibility start

        $shop_id = Shop::getContextShopID();

        $elkjopnordicHelper->log(
            'Create Order Cron',
            'Cron',
            'Order Create Cron Started',
            '',
            true,
            $shop_id
        );

           
            $db = Db::getInstance();
            $orderIds = array();

            $status = array("'SHIPPED'", "'CLOSED'", "'REFUSED'", "'CANCELED'");
            $sql = "SELECT `elkjopnordic_order_id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` 
    WHERE `status` NOT IN (" . implode(',', $status) . ") AND `order_id` IS NULL";
            $result = $db->executeS($sql);
            if (isset($result) && count($result)) {
                foreach ($result as $id) {
                    $orderIds[] = $id['elkjopnordic_order_id'];
                }
            }
 
           
            if (!empty($orderIds)) {
                $orderIds = array_unique($orderIds);
                
                
                if(count($orderIds)<=99){
                    $params = array();
                    $method = 'orders';
                    $params['order_ids'] = implode(',', $orderIds);
                    $params['order_state_codes'] = 'SHIPPING';
                    $params['max'] = '100';
                    $queryString = empty($params) ? '' : '?' . http_build_query($params);
                    $url = $method . $queryString;
                    $response = $elkjopnordicHelper->WGetRequest($url, array(), 'json');
                     
                    $elkjopnordicHelper->log(
                        'Create Order Cron',
                        'Cron',
                        'Order Create API Response',
                        json_encode($response),
                        true,
                        $shop_id
                    );
                   
                    if (isset($response['success']) && $response['success'] && isset($response['response'])) {
                        $orders = json_decode($response['response'], true);

                        if (isset($orders['orders']) && !empty($orders['orders'])) {
                            $orders = $orders['orders'];
                            $i = 0;
                            foreach ($orders as $order) {
                                try {


                                $order_id = $order['order_id'];
                                $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `elkjopnordic_order_id` LIKE '" .
                                    pSQL($order_id). "' AND `order_id` IS NOT NULL AND import_shop_id = '".(int)$shop_id."'";

                                $alreadyCreated = $db->getValue($sql);

                                if ($alreadyCreated) {
                                    continue;
                                } else if($cedelkjopnordicOrder->isElkjopnordicOrderIdExist($order_id, $shop_id)){

                                    $prestashopId = $cedelkjopnordicOrder->createPrestashopOrder($order,$shop_id);

                                    if ($prestashopId) {

                                        $response = $db->update(
                                            'cedelkjopnordic_order',
                                            array(
                                                'order_id' => (int)$prestashopId,
                                                'status' => pSQL($order['order_state']),
                                                'order_data' => pSQL(json_encode($order)),
                                                'shipment_data' => pSQL(json_encode($order['customer']))
                                            ),
                                            "elkjopnordic_order_id LIKE '" . pSQL($order_id) . "' AND import_shop_id = '".(int)$shop_id."'"
                                        );
                                        if($response){
                                            $i++;
                                        }

                                    } else {
                                        continue;
                                    }

                                }
                                } catch (\Exception $e) {
                                    $elkjopnordicHelper->log(
                                        'CronCreateOrder',
                                        'Exeception',
                                        '"Create order exception : "',
                                        json_encode(
                                            array(
                                                'url' => $url,
                                                'Request Param' => $params,
                                                'Response' => $e->getMessage()
                                            )
                                        ),
                                        true
                                    );
                                }
                            }
                            print_r('Total ' . $i . ' order created in store');
                        }
                    }
                } else {
                    $orderIdss = array_chunk($orderIds,99);
                    foreach($orderIdss as $orderIds){
                        $params = array();
                        $method = 'orders';
                        $params['order_ids'] = implode(',', $orderIds);
                        $params['order_state_codes'] = 'SHIPPING';
                        $params['max'] = '100';
                        $queryString = empty($params) ? '' : '?' . http_build_query($params);
                        $url = $method . $queryString;
                        $response = $elkjopnordicHelper->WGetRequest($url, array(), 'json');
                        
                        if (isset($response['success']) && $response['success'] && isset($response['response'])) {
                            $orders = json_decode($response['response'], true);
                            if (isset($orders['orders']) && !empty($orders['orders'])) {
                                $orders = $orders['orders'];
                                $i = 0;
                                foreach ($orders as $order) {
                                    try {
                                    $order_id = $order['order_id'];
                                    $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `elkjopnordic_order_id` LIKE '" .
                                        pSQL($order_id). "' AND `order_id` IS NOT NULL";

                                    $alreadyCreated = $db->getValue($sql);

                                    if ($alreadyCreated) {
                                        continue;
                                    } else if($cedelkjopnordicOrder->isElkjopnordicOrderIdExist($order_id)){

                                        $prestashopId = $cedelkjopnordicOrder->createPrestashopOrder($order);

                                        if ($prestashopId) {

                                            $response = $db->update(
                                                'cedelkjopnordic_order',
                                                array(
                                                    'order_id' => (int)$prestashopId,
                                                    'status' => pSQL($order['order_state']),
                                                    'order_data' => pSQL(json_encode($order)),
                                                    'shipment_data' => pSQL(json_encode($order['customer']))
                                                ),
                                                'elkjopnordic_order_id LIKE "' . pSQL($order_id) . '"'
                                            );
                                            if($response){
                                                $i++;
                                            }

                                        } else {
                                            continue;
                                        }

                                    }
                                    } catch (\Exception $e) {
                                        $elkjopnordicHelper->log(
                                            'CronCreateOrder',
                                            'Exeception',
                                            '"Create order exception : "',
                                            json_encode(
                                                array(
                                                    'url' => $url,
                                                    'Request Param' => $params,
                                                    'Response' => $e->getMessage()
                                                )
                                            ),
                                            true
                                        );
                                    }
                                }
                                print_r('Total ' . $i . ' order created in store');
                            }
                        }
                    }
                }

            }
        $elkjopnordicHelper->log(
            'Create Order Cron',
            'Cron',
            'Order Create Cron End',
            '',
            true,
            $shop_id
        );

        $this->setTemplate('module:cedelkjopnordic/views/templates/front/orderstatus.tpl');
    }
}
