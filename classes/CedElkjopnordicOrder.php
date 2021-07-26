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

include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';

require _PS_MODULE_DIR_ . 'cedelkjopnordic/library/mirakl-sdk/vendor/autoload.php';

use Mirakl\Core\Domain\Collection\DocumentCollection;
use Mirakl\Core\Domain\Document;
use Mirakl\MMP\Shop\Client\ShopApiClient;
use Mirakl\MMP\Shop\Request\Order\Document\UploadOrdersDocumentsRequest;

class CedElkjopnordicOrder
{

    public function sendInvoice($id_order)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `order_id` = " . $id_order . "";
        $db = Db::getInstance();
        $ids_to_accept = $db->getRow($sql);
        $ps = $ids_to_accept['order_id'];
        $mpid = $ids_to_accept['elkjopnordic_order_id'];
        if (!empty($mpid) && !empty($ps)) {
            $order = new Order((int)$ps);
            $order_invoice_list = $order->getInvoicesCollection();
            $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);
            $res = $pdf->render(false);
            $feedDir = _PS_MODULE_DIR_ . 'cedelkjopnordic/pdf/invoice';
            if (!is_dir($feedDir)) {
                mkdir($feedDir, 0777, true);
            }
            $pdfFile = $feedDir . "/$ps.pdf";
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }
            $fp = fopen($pdfFile, 'w');
            fwrite($fp, $res);
            fclose($fp);
            $res = $this->sendDocument($mpid, $pdfFile);
            return $res;
        }
        return array(
          'success' => false,
          'message' => 'Empty Marketplace order id or invalid ps id'
        );

    }

    public function sendDocument($order_id, $file)
    {
        $n = basename($file);
        $jsonContent = "{
        \"order_documents\": [
                  {
        \"file_name\": \"$n\",
        \"type_code\": \"CUSTOMER_INVOICE\"
                  }
        ]}";

        $tempJsonFile = tmpfile();
        fwrite($tempJsonFile, $jsonContent);
        $user = Configuration::get('ELKJOPNORDIC_API_KEY');
        $curl = curl_init();
        $url = Configuration::get('ELKJOPNORDIC_API_URL') . "orders/$order_id/documents";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                "files" => new CURLFile("$file", "application/pdf", $n),
                "order_documents" => new CURLFile(stream_get_meta_data($tempJsonFile)['uri'], "application/json")
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $user
            )
        ]);

        $result = curl_exec($curl);

        curl_close($curl);
        fclose($tempJsonFile);
        $res = json_decode($result,true);
        if(isset($res['errors_count']) && $res['errors_count'] == 0) {

            return array('success' => true, 'message' => '');
        } else {
            return array('success' => false, 'message' => $result);
        }
    }

        function buildMultiPartRequest($ch, $boundary, $fields, $files)
        {
            $delimiter = '-------------' . $boundary;
            $data = '';
            foreach ($fields as $name => $content) {
                $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
                    . $content . "\r\n";
            }
            foreach ($files as $name => $content) {
                $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . basename($content) . '"' . "\r\n\r\n"
                    . $content . "\r\n";
            }
            $data .= "--" . $delimiter . "--\r\n";

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: " . Configuration::get('ELKJOPNORDIC_API_KEY'),
                    'Content-Type: multipart/form-data; boundary=' . $delimiter,
                    'Content-Length: ' . strlen($data)
                ],
                CURLOPT_POSTFIELDS => $data
            ]);
            echo '<pre>';
            print_r(array(
                "Authorization: " . Configuration::get('ELKJOPNORDIC_API_KEY'),
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            ));
            print_r($data);
            return $ch;
        }


        /**
         * @param $params
         * @param $url
         * @param $order
         * @param bool $is_query
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function fetchDocumentsFile($params, $url, $order, $is_query = true)
        {


            $url = Configuration::get('ELKJOPNORDIC_API_URL') . $url;
            if (!empty($params)) {
                $url = $url . '?' . http_build_query($params);
            }
            $headers = array();
            $headers[] = "Authorization: " . Configuration::get('ELKJOPNORDIC_API_KEY');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $file = curl_exec($ch);

            curl_close($ch);
            $file_array = explode("\n\r", $file, 2);
            $header_array = explode("\n", $file_array[0]);
            foreach ($header_array as $header_value) {
                $header_pieces = explode(':', $header_value);
                if (count($header_pieces) == 2) {
                    $headers[$header_pieces[0]] = trim($header_pieces[1]);
                }
            }
            if (isset($file_array[1]))
                $file = substr($file_array[1], 1);
            else
                $file = false;
            return $file;
        }

        public function fetchOrder($params, $url, $order, $is_query = false, $shop_id=0)
        {
            $cedElkjopnordicHelper = new CedElkjopnordicHelper();
            $order['max'] = '100';
            if ($is_query) {
                $response = $cedElkjopnordicHelper->WGetRequest($url, $params, 'json');
            } else {
                $queryString = empty($order) ? '' : '?' . http_build_query($order);
                $response = $cedElkjopnordicHelper->WGetRequest($url . $queryString, $params, 'json');
            }
            $nextCursor = '';
           // var_dump($params, $url, $order);
           // print_r($response);die;
            if ($response) {
                if (isset($response['success'], $response['response']) && $response['success']) {
                    $message = $response['response'];
                    
                    $response = json_decode($message, true);
                    $order_ids = array();
                    if (is_array($response) && isset($response['orders'])) {
                        if (isset($response['total_count']) && $response['total_count']) {
                            $fetchedOrders = $response['orders'];
                            if (is_array($fetchedOrders) && !empty($fetchedOrders)) {
                                foreach ($fetchedOrders as $value) {
                                    if (isset($value['order_id']) && $value['order_id']) {
                                        $purchase_order_id = $value['order_id'];
                                        $already_exist = $this->isElkjopnordicOrderIdExist($purchase_order_id);
                                        if ($already_exist) {
                                            continue;
                                        } else {
                                            try {
                                                $res = $this->createElkjopnordicOrder($value, $shop_id);

                                                $acceptResponse = $this->checkAcceptOrder($value);

                                                $cedElkjopnordicHelper->log(
                                                    __METHOD__,
                                                    'Order Accept',
                                                    'Accept Response' . $res,
                                                    json_encode(
                                                        array(
                                                            'Response' => $acceptResponse
                                                        )
                                                    ),
                                                    true,
                                                    $shop_id
                                                );
                                                if ($res) {
                                                    $order_ids[] = $res;
                                                }
                                            } catch (Exception $e) {
                                                $cedElkjopnordicHelper->log(
                                                    __METHOD__,
                                                    'Exception',
                                                    'Order Fetch',
                                                    json_encode(
                                                        array(
                                                            'Response' => $e->getMessage()
                                                        )
                                                    ),
                                                    true,
                                                    $shop_id
                                                );
                                                continue;
                                            }


                                        }
                                    }
                                }
                                if ($nextCursor) {
                                    $this->fetchOrder($params, $url . $nextCursor, $order, true);
                                }
                                if (count($order_ids)) {
                                    return array('success' => true, 'message' => $order_ids);
                                } else {
                                    return array('success' => true, 'message' => 'No new Order Found.');
                                }
                            } else {
                                return array('success' => true, 'message' => 'No new Order Found.');
                            }
                        } else {
                            return array('success' => true, 'message' => 'No new Order Found.');
                        }
                    }
                }
            } else {
                return array('success' => false, 'message' => $response['message']);
            }
        }

        /**
         * @param $order_id
         * @return bool
         * @throws PrestaShopDatabaseException
         */
        public function isElkjopnordicOrderIdExist($order_id,$shop_id=0)
        {
            $db = Db::getInstance();
            $isExist = false;
            if ($order_id) {
                $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` 
            where `elkjopnordic_order_id` LIKE '" . pSQL($order_id) . "' AND import_shop_id = '".(int)$shop_id."'";

                $result = $db->ExecuteS($sql);
                if (is_array($result) && !empty($result)) {
                    $isExist = true;
                }
            }
            return $isExist;
        }

        /**
         * @param $value
         * @return bool|int|string
         * @throws PrestaShopDatabaseException
         */
        public function createElkjopnordicOrder($value, $shop_id=0)
        {
            $elkjopnordic_order_id = $value['order_id'];
            $status = $value['order_state'];
            $order_place_date = $value['created_date'];
            $db = Db::getInstance();
            $result = $db->insert(
                'cedelkjopnordic_order',
                array(
                    'order_place_date' => pSQL($order_place_date),
                    'status' => pSQL($status),
                    'elkjopnordic_order_id' => pSQL($elkjopnordic_order_id),
                    'order_data' => pSQL(json_encode($value)),
                    'import_shop_id' => (int)$shop_id,
                )
            );
            
            if ($result && $db->Insert_ID()) {
                $order_id = $db->Insert_ID();
                return $order_id;
            }
            return false;
        }

        /**
         * @param $elkjopnordic_order_id
         * @param $status
         */
        public
        function updateElkjopnordicOrderStatus($elkjopnordic_order_id, $status)
        {
            $db = Db::getInstance();
            $r = $db->update(
                'cedelkjopnordic_order',
                array(
                    'status' => $status
                ),
                'elkjopnordic_order_id="' . pSQL($elkjopnordic_order_id) . '"'
            );
        }

        /**
         * @param $orderData
         * @return array
         * @throws PrestaShopDatabaseException
         * @throws PrestaShopException
         */
        public
        function checkAcceptOrder($orderData)
        {
            // $res = false;
            $order_id = $orderData['order_id'];
            // $order_state = $orderData['order_state'];
            $orderLine = array();
            //  $cedElkjopnordicHelper = new CedElkjopnordicHelper();
            foreach ($orderData['order_lines'] as $lineItem) {
                $sku = $lineItem['offer_sku'];
                $line_id = $lineItem['order_line_id'];
                $id_product = $this->getProductIdByReference($sku);
                if (!$id_product) {
                    $id_product = $this->getVariantProductIdByReference($sku);
                }
                if (!$id_product) {
                    $id_product = $this->getVariantProductIdByReference($sku);
                }
                $qty = isset($lineItem['quantity']) ? $lineItem['quantity'] : '0';
                $id_lang = ((int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')) ?
                    (int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE') :
                    (int)Configuration::get('PS_LANG_DEFAULT');
                $context = Context::getContext();
                $context->cart = new Cart();
                $producToAdd = new Product((int)($id_product), true, (int)($id_lang), null, $context);
                if ((!$producToAdd->id)) {
                    if (Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS')) {
                        $orderLine[] = array(
                            'accepted' => true,
                            'id' => $line_id
                        );
                    }
                    $this->orderErrorInformation(
                        $sku,
                        $order_id,
                        $orderData,
                        "PRODUCT ID" . $id_product . " DOES NOT EXIST"
                    );
                    //continue;
                }
                if (!$producToAdd->active) {
                    if (Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS')) {
                        $orderLine[] = array(
                            'accepted' => true,
                            'id' => $line_id
                        );
                    }
                    $this->orderErrorInformation(
                        $sku,
                        $order_id,
                        $orderData,
                        "PRODUCT STATUS IS DISABLED WITH ID " . $id_product . ""
                    );
                    // continue;
                }
                if (!$producToAdd->checkQty((int)$qty)) {
                    if (Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS')) {
                        $orderLine[] = array(
                            'accepted' => true,
                            'id' => $line_id
                        );
                    }
                    $this->orderErrorInformation(
                        $sku,
                        $order_id,
                        $orderData,
                        "REQUESTED QUANTITY " . $qty . " FOR PRODUCT ID " . $id_product . " IS NOT AVAILABLE"
                    );
                    // continue;
                }
                $orderLine[] = array(
                    'accepted' => true,
                    'id' => $line_id
                );
            }
            $acceptResponse = $this->acceptOrderLine($order_id, $orderLine);

            if (isset($acceptResponse['success']) && $acceptResponse['success']) {
                //  $order_state = Configuration::get('ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE');
                Db::getInstance()->update(
                    'cedelkjopnordic_order',
                    array(
                        'status' => 'Accepted'
                    ),
                    'elkjopnordic_order_id LIKE "' . pSQL($order_id) . '"'
                );

                // $this->updateOrderStatus($order_id, (int)$order_state);
                return array(
                    'success' => true,
                    'message' => 'Order ' . $order_id . ' accepted successfully'
                );
            }
            $message = "Failed to accept Order " . $order_id;
            if (isset($acceptResponse['message']) && ($acceptResponse['message'])) {
                $message = $acceptResponse['message'];
            }
            return array(
                'success' => false,
                'message' => $message
            );
        }

        /**
         * @param $merchant_sku
         * @return false|null|string
         */
        public static function getProductIdByReference($merchant_sku)
        {
            $db = Db::getInstance();
            return $db->getValue(
                'Select `id_product` FROM `' . _DB_PREFIX_ . 'product` WHERE `reference` LIKE "' .
                pSQL($merchant_sku) . '"'
            );
        }

        public static function getVariantProductIdByReference($merchant_sku)
        {
            $db = Db::getInstance();
            return $db->getValue(
                'Select `id_product` FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE `reference` LIKE "' .
                pSQL($merchant_sku) . '"'
            );
        }

        /**
         * @param $sku
         * @param $elkjopnordic_order_id
         * @param $worder_data
         * @param $errormessage
         * @throws PrestaShopDatabaseException
         */
        public function orderErrorInformation($sku, $elkjopnordic_order_id, $worder_data, $errormessage)
        {
            $db = Db::getInstance();
            $sql_check_already_exists = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order_error` WHERE `merchant_sku`='" .
                $sku . "' AND `elkjopnordic_order_id`='" . pSQL($elkjopnordic_order_id) . "'";
            $Execute_check_already_exists = $db->ExecuteS($sql_check_already_exists);
            $id_shop =  $context = Context::getContext()->shop->id;
            if(!$id_shop)
                $id_shop = 0;
            if (empty($Execute_check_already_exists)) {
                $sql_insert = "INSERT INTO `" . _DB_PREFIX_ .
                    "cedelkjopnordic_order_error` (`merchant_sku`,`elkjopnordic_order_id`,`order_data`,`reason`,`created_at`,`shop_id`)VALUES('" .
                    pSQL($sku) . "','" . $elkjopnordic_order_id . "','" . pSQL(json_encode($worder_data)) .
                    "','" . $errormessage . "','". date('Y-m-d H:i:s')."','".$id_shop ."')";
                $db->Execute($sql_insert);
            }
        }

        /**
         * @param null $orderId
         * @param array $orderLine
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function acceptOrderLine($orderId = null, $orderLine = array())
        {
            $cedelkjopnordicHelper = new CedElkjopnordicHelper();
            $cedelkjopnordicHelper->log(
                __METHOD__,
                'Info',
                'acceptOrder' . $orderId,
                json_encode(
                    array(
                        'Response' => $orderLine
                    )
                ),
                true
            );
            if (!empty($orderId) && !empty($orderLine)) {
                $params = array();
                $params['order_lines'] = $orderLine;
                $method = 'orders/' . trim($orderId) . '/accept';
                $cedElkjopnordicHelper = new CedElkjopnordicHelper();
                $response = $cedElkjopnordicHelper->WPutRequest($method, $params);
                return $response;
            }
        }

        /**
         * @param $order_id
         * @return bool
         * @throws PrestaShopDatabaseException
         */
        public
        function isOrderAlreadyCreated($order_id)
        {
            $db = Db::getInstance();
            $isExist = false;
            if ($order_id) {

                $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `elkjopnordic_order_id` LIKE '" .
                    pSQL($order_id) . "'";

                $result = $db->getValue($sql);

                if ($result) {
                    $isExist = true;
                }
            }
            return $isExist;
        }

        /**
         * @param $data
         * @return bool|int
         * @throws PrestaShopDatabaseException
         */
        public function createPrestashopOrder($data, $shop_id=0)
        {
            // $message = '';

            $context = Context::getContext()->cloneContext();
            $cedElkjopnordicHelper = new CedElkjopnordicHelper();
            try {
                $elkjopnordic_order_id = $data['order_id'];
                $id_lang = $context->language->id;

                $firstname = isset($data['customer']['shipping_address']['firstname']) ?
                    $data['customer']['shipping_address']['firstname'] : 'elkjopnordic';
                $lastname = isset($data['customer']['shipping_address']['lastname']) ?
                    $data['customer']['shipping_address']['lastname'] : 'elkjopnordic';
                $email = $data['order_id'] . '@elkjopnordiccustomer.com';
 $validityPattern = Tools::cleanNonUnicodeSupport(
                            '/^(?:[^0-9!<>,;?=+()\/\\@#"°*`{}_^$%:¤\[\]|\.。]|[\.。](?:\s|$))*$/u'
                        );
                $isValid = preg_match($validityPattern, $firstname);
                if(!$isValid){
                        $firstname = preg_replace('/[^a-zA-z .]/', '', $firstname);
                
                } 
                
                $isValid = preg_match($validityPattern, $lastname);
                if(!$isValid){
                       
                        $lastname = preg_replace('/[^a-zA-z .]/', '', $lastname);
                } 
            
            $firstname = substr($firstname,0,31);
            $lastname = substr($lastname,0,31);
                if ((int)Configuration::get('ELKJOPNORDIC_CUSTOMER_ID')) {
                    $id_customer = Configuration::get('ELKJOPNORDIC_CUSTOMER_ID');
                } else {
                    $id_customer = Customer::customerExists($email, true);
                    if (!$id_customer) {
                        $new_customer = new Customer();
                        $new_customer->email = $email;
                        $new_customer->lastname = $lastname;
                        $new_customer->firstname = $firstname;
                        $new_customer->passwd = 'elkjopnordic';
                        $new_customer->add();
                        $id_customer = (int)$new_customer->id;
                    }

                }

                $context->customer = new Customer((int)$id_customer);
                $state = isset($data['customer']['shipping_address']['state']) ?
                    $data['customer']['shipping_address']['state'] : '';
                $country_code = isset($data['customer']['shipping_address']['country_iso_code']) ?
                    $data['customer']['shipping_address']['country_iso_code'] : '';

                $country_name = isset($data['customer']['shipping_address']['country']) ?
                    $data['customer']['shipping_address']['country'] : '';

                $country_code_2 = '';
                if (isset($data['customer']['shipping_address']['country_iso_code']) && $data['customer']['shipping_address']['country_iso_code']) {
                    $country_code_2 = $this->getISOCode2ByISOCode3($data['customer']['shipping_address']['country_iso_code']);
                } else {
                 if($country_name=='Sweden')
                 $country_code_2 = 'SE';
                 if($country_name=='Norway')
                 $country_code_2 = 'NO';
                 if($country_name=='Denmark')
                 $country_code_2 = 'DK';
                 if($country_name=='Finland')
                 $country_code_2 = 'FI';
                 else
                 $country_code_2 = 'SE';
                 
                }
         
                $getLocalizationDeatails = $this->getLocalizationDeatails($state, $country_code_2, $country_name);
                $id_country = $getLocalizationDeatails['country_id'];
                $id_state = $getLocalizationDeatails['zone_id'];

                $address_shipping = new Address();
                $address_shipping->id_customer = $id_customer;
                $address_shipping->id_country = $id_country;
                $address_shipping->alias = 'Eligenten';
                $address_shipping->firstname = $firstname;
                $address_shipping->lastname = $lastname;
                $address_shipping->id_state = $id_state;

                $address_shipping->address1 = isset($data['customer']['shipping_address']['street_1']) ?
                    $data['customer']['shipping_address']['street_1'] : '';

                $address_shipping->address2 = isset($data['customer']['shipping_address']['street_2']) ?
                    $data['customer']['shipping_address']['street_2'] : '';

                $address_shipping->postcode = isset($data['customer']['shipping_address']['zip_code']) ?
                    str_replace(' ', '', $data['customer']['shipping_address']['zip_code']) : '';

                $address_shipping->city = isset($data['customer']['shipping_address']['city']) ?
                    $data['customer']['shipping_address']['city'] : '';

                $address_shipping->phone = isset($data['customer']['shipping_address']['phone']) ?
                    $data['customer']['shipping_address']['phone'] : '';

                $address_shipping->phone_mobile = isset($data['customer']['shipping_address']['phone']) ?
                    $data['customer']['shipping_address']['phone'] : '';

                $address_shipping->add();
                $address_id_shipping = $address_shipping->id;


                $state = isset($data['customer']['billing_address']['state']) ?
                    $data['customer']['billing_address']['state'] : '';
                $country = isset($data['customer']['billing_address']['country_iso_code']) ?
                    $data['customer']['billing_address']['country_iso_code'] : '';

                $country_name = isset($data['customer']['shipping_address']['country']) ?
                    $data['customer']['shipping_address']['country'] : '';


                //$country_code_2 = $this->getISOCode2ByISOCode3($country);
                $getLocalizationDeatails = $this->getLocalizationDeatails($state, $country_code_2, $country_name);
                $id_country = $getLocalizationDeatails['country_id'];
                $id_state = $getLocalizationDeatails['zone_id'];


                $address_invoice = new Address();
                $address_invoice->id_customer = $id_customer;
                $address_invoice->id_country = $id_country;
                $address_invoice->alias = $firstname;
                $address_invoice->firstname = $firstname;
                $address_invoice->lastname = $lastname;
                $address_invoice->id_state = $id_state;
                $address_invoice->address1 = isset($data['customer']['billing_address']['street_1']) ?
                    $data['customer']['billing_address']['street_1'] : '';

                $address_invoice->address2 = isset($data['customer']['billing_address']['street_2']) ?
                    $data['customer']['billing_address']['street_2'] : '';

                $address_invoice->postcode = isset($data['customer']['billing_address']['zip_code']) ?
                    $data['customer']['billing_address']['zip_code'] : '';

                $address_invoice->city = isset($data['customer']['billing_address']['city']) ?
                    $data['customer']['billing_address']['city'] : '';

                $address_invoice->phone = isset($data['customer']['billing_address']['phone']) ?
                    $data['customer']['billing_address']['phone'] : '';

                $address_invoice->phone_mobile = isset($data['customer']['billing_address']['phone']) ?
                    $data['customer']['billing_address']['phone'] : '';

                $address_invoice->add();
                $address_id_invoice = $address_invoice->id;


                $payment_module = 'Elgiganten';//Configuration::get('ELKJOPNORDIC_ORDER_PAYMENT');
                $module_id = 0;
                $modules_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    'SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position` FROM `' . _DB_PREFIX_ .
                    'module` m LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module` LEFT JOIN `' .
                    _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook` GROUP BY hm.id_hook, hm.id_module 
                ORDER BY hm.`position`, m.`name` DESC'
                );
                foreach ($modules_list as $module) {
                    $module_obj = Module::getInstanceById($module['id_module']);
                    if ($module_obj->name == $payment_module) {
//                $payment = $module_obj->displayName;
                        //  $module_id = $module['id_module'];
                        ///  break;
                    }
                }
                $id_shop = $shop_id?(int)$shop_id:$context->shop->id;
                $shop = new Shop($id_shop);
                if($context->currency->id){
                    $id_currency = $context->currency->id;
                } else if (Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $id_currency = Configuration::get('PS_CURRENCY_DEFAULT');
                }

                if (!$id_currency) {
                    $currency_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_currency` FROM `' .
                        _DB_PREFIX_ . 'module_currency` WHERE `id_module` = ' . (int)$module_id);
                    $id_currency = isset($currency_id['0']['id_currency']) ? $currency_id['0']['id_currency'] : 0;
                }
                $cart = new Cart();
                $cart->id_customer = $id_customer;
                $cart->id_address_delivery = $address_id_shipping;
                $cart->id_address_invoice = $address_id_invoice;
                $cart->id_currency = (int)$id_currency;
                $cart->id_carrier = (int)Configuration::get('ELKJOPNORDIC_CARRIER_ID');
                $cart->recyclable = 0;
                $cart->gift = 0;
                $cart->add();
                $cart_id = (int)($cart->id);
                $context->cart = new Cart($cart_id);
                $order_total = 0;
                $order_total_wt = 0;
                $shippingCost = 0;
                $final_item_cost = 0;
                $shippingCost = isset($data['shipping_price']) ? (float)$data['shipping_price'] : 0;
                $order_total += $shippingCost;
                $productArray = array();

                if (isset($data['order_lines']) && !empty($data['order_lines'])) {
                    $order_items = $data['order_lines'];
                    $final_item_cost_wt = 0;
                    $final_item_cost = 0;
                    foreach ($order_items as $item) {
                        // $cancelQty = 0;
                        $sku = isset($item['offer_sku']) ? $item['offer_sku'] : '';
                        if ($sku == '')
                            $sku = isset($item['product_sku']) ? $item['product_sku'] : $sku;

                        //  $order_line_id = isset($item['order_line_id']) ? $item['order_line_id'] : '';
                        $id_product = $this->getVariantProductIdByReference($sku);
                        if (!$id_product) {
                            $id_product = $this->getProductIdByReference($sku);
                        }
                        $id_product_attribute = $this->getProductAttributeIdByReference($sku);

                        $qty = isset($item['quantity']) ? $item['quantity'] : '0';
                        $producToAdd = new Product((int)($id_product), true, (int)($id_lang));
                        if ((!$producToAdd->id)) {
                            $this->orderErrorInformation(
                                $sku,
                                $elkjopnordic_order_id,
                                $data,
                                "PRODUCT ID" . $id_product . " DOES NOT EXIST"
                            );
                            continue;
                        }
                        if(Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS')){
                            if (!$producToAdd->active) {
                                $this->orderErrorInformation(
                                    $sku,
                                    $elkjopnordic_order_id,
                                    $data,
                                    "PRODUCT STATUS IS DISABLED WITH ID " . $id_product . ""
                                );
                                continue;
                            }
                            if (!$producToAdd->checkQty((int)$qty)) {
                                $this->orderErrorInformation(
                                    $sku,
                                    $elkjopnordic_order_id,
                                    $data,
                                    "REQUESTED QUANTITY FOR PRODUCT ID " . $id_product . " IS NOT AVAILABLE"
                                );
                                continue;
                            }
                        }

                        if ($cart && $cart->id) {
                            $cart->updateQty((int)($qty), (int)($id_product), (int)$id_product_attribute, false, 'up', 0, null, true, true);
                            $response = $cart->update();

                            if (!$response) {
                                $this->orderErrorInformation(
                                    $sku,
                                    $elkjopnordic_order_id,
                                    $data,
                                    "Failed to update ",
                                    $shop_id
                                );
                                continue;
                            }

                        }

                        $item_cost = isset($item['price_unit']) ? (float)$item['price_unit'] : 0;
                        $item_taxes = isset($item['taxes']) ? $item['taxes'] : array();
                        $item_vat = 0;

                        foreach ($item_taxes as $key => $item_tax) {
                            if (isset($item_tax['amount']))
                                $item_vat += $item_tax['amount'];
                        }

                        $productArray[$id_product] = array(
                            'id_product' => $id_product,
                            'price_tax_included' => $item_cost,
                            'price_tax_excluded' => (float)$item_cost / ((float)(1 + ((int)Db::getInstance()->getValue("SELECT t.rate FROM `" . _DB_PREFIX_ . "tax_rule` tr JOIN `" . _DB_PREFIX_ . "product` ps ON (tr.id_tax_rules_group = ps.id_tax_rules_group) JOIN " . _DB_PREFIX_ . "tax t ON (t.id_tax = tr.id_tax) WHERE ps.`id_product` ='" . $id_product . "'") / 100))),
                            'quantity' => $qty
                        );
                        $item_vat = $item_cost - (float)$item_cost / ((float)(1 + ((int)Db::getInstance()->getValue("SELECT t.rate FROM `" . _DB_PREFIX_ . "tax_rule` tr JOIN `" . _DB_PREFIX_ . "product` ps ON (tr.id_tax_rules_group = ps.id_tax_rules_group) JOIN " . _DB_PREFIX_ . "tax t ON (t.id_tax = tr.id_tax) WHERE ps.`id_product` ='" . $id_product . "'") / 100)));
                        $total_cost = $item_cost * (int)$qty;
                        $total_vat = $item_vat * (int)$qty;
                        $final_item_cost_wt += $total_cost;
                        $final_item_cost += ($item_cost - $item_vat);
                    }
                    $order_total += $final_item_cost;
                    $order_total_wt += $final_item_cost_wt;
                    $extra_vars = array();
                    $extra_vars['total_paid_inc_tax'] = $order_total_wt;
                    $extra_vars['total_paid_exl_tax'] = $order_total;
                    $extra_vars['total_paid'] = $order_total;
                    $extra_vars['item_shipping_cost'] = $shippingCost;
                    $extra_vars['total_item_cost'] = $final_item_cost;
                    $extra_vars['total_item_cost_wt'] = $final_item_cost_wt;
                    $extra_vars['total_item_tax'] = $final_item_cost_wt - $final_item_cost;
                    $item_shipping_taxes = array();
                    $item_shipping_tax_amount = 0;
                    if (!empty($item_shipping_taxes)) {
                        foreach ($item_shipping_taxes as $item_shipping_tax) {
                            if (isset($item_shipping_tax['amount'])) {
                                $item_shipping_tax_amount += $item_shipping_tax['amount'];
                            }
                        }
                    }

                    $extra_vars['item_shipping_tax'] = $item_shipping_tax_amount;
                    $extra_vars['order_id'] = $elkjopnordic_order_id;
                    $extra_vars['customer_reference_order_id'] = $data['commercial_id'];


                    //    $dont_touch_amount = false;
                    $secure_key = false;

                    $prestashop_order_id = $this->addOrderInPrestashop(
                        $cart_id,
                        $id_customer,
                        $address_id_shipping,
                        $address_id_invoice,
                        (int)Configuration::get('ELKJOPNORDIC_CARRIER_ID'),
                        (int)$id_currency,
                        $extra_vars,
                        $productArray,
                        $secure_key,
                        $context,
                        $shop,
                        Configuration::get('ELKJOPNORDIC_ORDER_PAYMENT'),
                        $country_code_2
                    );
                    if ($prestashop_order_id) {
                        return $prestashop_order_id;
                    }
                    return false;
                }
            } catch (PrestaShopException $e) {
                $cedElkjopnordicHelper->log(
                    __METHOD__,
                    'Create Order Exception',
                    $e->getMessage(),
                    json_encode($e->getTraceAsString()),
                    true,
                    $shop_id
                );
                $this->orderErrorInformation('', $elkjopnordic_order_id, $data, $e->getMessage());
                return false;
            } catch (Exception $e) {
                $cedElkjopnordicHelper->log(
                    __METHOD__,
                    'Create Order Exception',
                    $e->getMessage(),
                    json_encode($e->getTraceAsString()),
                    true,
                    $shop_id
                );
                $this->orderErrorInformation('', $elkjopnordic_order_id, $data, $e->getMessage());
                return false;
            }
        }


        /**
         * @param $merchant_sku
         * @return false|null|string
         */
        public
        static function getProductAttributeIdByReference($merchant_sku)
        {
            $db = Db::getInstance();
            return $db->getValue(
                'Select `id_product_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE `reference` LIKE "' .
                pSQL($merchant_sku) . '"'
            );
        }

        /**
         * @param $id_cart
         * @param $id_customer
         * @param $id_address_delivery
         * @param $id_address_invoice
         * @param $id_carrier
         * @param $id_currency
         * @param $extra_vars
         * @param $products
         * @param $secure_key
         * @param $context
         * @param $shop
         * @param $payment_module
         * @return bool|int
         * @throws PrestaShopDatabaseException
         * @throws PrestaShopException
         */
        public function addOrderInPrestashop(
            $id_cart,
            $id_customer,
            $id_address_delivery,
            $id_address_invoice,
            $id_carrier,
            $id_currency,
            $extra_vars,
            $products,
            $secure_key,
            $context,
            $shop,
            $payment_module,
            $country_code_2 = 'se'
        )
        {
            try{
                $newOrder = new Order();

                $context->cart = new Cart((int)$id_cart);

                $carrier = new Carrier($id_carrier, $context->cart->id_lang);
                $newOrder->id_address_delivery = $id_address_delivery;
                $newOrder->id_address_invoice = $id_address_invoice;
                $newOrder->id_shop_group = $shop->id_shop_group;
                $newOrder->id_shop = $shop->id;
                $newOrder->id_cart = $id_cart;
                $newOrder->id_currency = $id_currency;
                $newOrder->id_lang = $context->language->id;
                $newOrder->id_customer = $id_customer;
                $newOrder->id_carrier = $id_carrier;
                $newOrder->current_state = Configuration::get('ELKJOPNORDIC_ORDER_STATE');
                $newOrder->secure_key = (
                $secure_key ? pSQL($secure_key) : pSQL($context->customer->secure_key)
                );
                if($shop->id==1){
                $newOrder->payment = 'Elgiganten SE';
                $newOrder->module = 'cedelkjopnordic_se';
                } else if($shop->id==2){
                $newOrder->payment = 'Elgiganten DK';
                $newOrder->module = 'cedelkjopnordic_dk';
                } else if($shop->id==6){
                 $newOrder->payment = 'Elgiganten FI';
                $newOrder->module = 'cedelkjopnordic_fi';
                } else if($shop->id==4){
                 $newOrder->payment = 'Elgiganten NO';
                 $newOrder->module = 'cedelkjopnordic_no';
                } else {
                $newOrder->payment = 'Elgiganten SE';
                $newOrder->module = 'cedelkjopnordic_se';
                }
                
                $newOrder->conversion_rate = isset($context->currency->conversion_rate) ?
                    $context->currency->conversion_rate : 1;
                $newOrder->recyclable = $context->cart->recyclable;
                $newOrder->gift = (int)$context->cart->gift;
                $newOrder->gift_message = $context->cart->gift_message;
                $newOrder->mobile_theme = $context->cart->mobile_theme;
                $newOrder->total_discounts = 0;
                $newOrder->total_discounts_tax_incl = 0;
                $newOrder->total_discounts_tax_excl = 0;
                $newOrder->total_paid = (float)number_format($extra_vars['total_paid_inc_tax'],2,'.','');
                $newOrder->total_paid_tax_incl =  (float)number_format($extra_vars['total_paid_inc_tax'],2,'.','');
                $newOrder->total_paid_tax_excl = (float)number_format($extra_vars['total_paid_exl_tax'],2,'.','');
                $newOrder->total_paid_real = (float)number_format($extra_vars['total_paid_inc_tax'],2,'.','');
                $newOrder->total_products = (float)number_format($extra_vars['total_item_cost'],2,'.','');
                $newOrder->total_products_wt = (float)number_format($extra_vars['total_item_cost_wt'],2,'.','');
                $newOrder->total_shipping = (float)number_format($extra_vars['item_shipping_cost'],2,'.','');
                $newOrder->total_shipping_tax_incl = (float)number_format($extra_vars['item_shipping_cost'],2,'.','');
                $newOrder->total_shipping_tax_excl = (float)number_format($extra_vars['item_shipping_cost'] - $extra_vars['item_shipping_tax'],2,'.','');
                if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
                    $newOrder->carrier_tax_rate = $carrier->getTaxesRate(
                        new Address($context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})
                    );
                }
                $newOrder->total_wrapping = 0;
                $newOrder->total_wrapping_tax_incl = 0;
                $newOrder->total_wrapping_tax_excl = 0;
                $newOrder->invoice_date = '0000-00-00 00:00:00';
                $newOrder->delivery_date = '0000-00-00 00:00:00';
                $newOrder->valid = true;
                /*do {
                    $reference = Order::generateReference();
                } while (Order::getByReference($reference)->count());*/
                $newOrder->reference = $extra_vars['order_id'];
                $newOrder->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
                $packageList = $context->cart->getPackageList();
                $orderItems = array();

                foreach ($packageList as $id_address => $packageByAddress) {
                    foreach ($packageByAddress as $id_package => $package) {
                        foreach ($package['product_list'] as &$product) {
                            if (array_key_exists($product['id_product'], $products)) {
                                $product['price'] = $products[$product['id_product']]['price_tax_excluded'];
                                $product['price_wt'] = $products[$product['id_product']]['price_tax_included'];
                                $product['total'] = $products[$product['id_product']]['price_tax_excluded'] *
                                    $products[$product['id_product']]['quantity'];
                                $product['total_wt'] = $products[$product['id_product']]['price_tax_included'] *
                                    $products[$product['id_product']]['quantity'];
                            }
                        }
                        $orderItems = $package['product_list'];
                    }
                }
                $newOrder->product_list = $orderItems;

                try{
                    $res = $newOrder->add(true, false);
                }catch (Exception $e) {
                    echo $e->getMessage();  echo $e->getTraceAsString();die;
                }

                if (!$res) {
                    $newOrder->delete();
                    PrestaShopLogger::addLog(
                        'Order cannot be created',
                        3,
                        null,
                        'Cart',
                        (int)$id_cart,
                        true
                    );

                }
                if ($newOrder->id_carrier) {
                    $newOrderCarrier = new OrderCarrier();
                    $newOrderCarrier->id_order = (int)$newOrder->id;
                    $newOrderCarrier->id_carrier = (int)$newOrder->id_carrier;
                    $newOrderCarrier->weight = (float)$newOrder->getTotalWeight();
                    $newOrderCarrier->shipping_cost_tax_excl = $newOrder->total_shipping_tax_excl;
                    $newOrderCarrier->shipping_cost_tax_incl = $newOrder->total_shipping_tax_incl;
                    $newOrderCarrier->add();
                }
                if (isset($newOrder->product_list) && !empty($newOrder->product_list)) {
                    foreach ($newOrder->product_list as $detproduct) {
                        $order_detail = new OrderDetail();
                        $order_detail->id_order = (int)$newOrder->id;
                        $order_detail->id_order_invoice = $detproduct['id_address_delivery'];
                        $order_detail->product_id = $detproduct['id_product'];
                        $order_detail->id_shop = $detproduct['id_shop'];
                        $order_detail->id_warehouse = $packageList[$id_address][$id_package]['id_warehouse'];
                        $order_detail->product_attribute_id = $detproduct['id_product_attribute'];
                        $order_detail->product_name = htmlentities(Product::getProductName($detproduct['id_product'], $detproduct['id_product_attribute']));
                        $order_detail->product_quantity = $detproduct['cart_quantity'];
                        $order_detail->product_quantity_in_stock = $detproduct['quantity_available'];
                        $order_detail->product_price = (float)number_format($detproduct['price'],2,'.','');
                        $order_detail->unit_price_tax_incl = (float)number_format($detproduct['price_wt'],2,'.','');
                        $order_detail->unit_price_tax_excl = (float)number_format($detproduct['price'],2,'.','');
                        $order_detail->total_price_tax_incl =(float)number_format( $detproduct['total_wt'],2,'.','');
                        $order_detail->total_price_tax_excl = (float)number_format($detproduct['total'],2,'.','');
                        $order_detail->product_ean13 = $detproduct['ean13'];
                        $order_detail->product_upc = $detproduct['upc'];
                        $order_detail->product_reference = $detproduct['reference'];
                        $order_detail->product_supplier_reference = $detproduct['supplier_reference'];
                        $order_detail->product_weight = $detproduct['weight'];
                        $order_detail->ecotax = $detproduct['ecotax'];
                        $order_detail->discount_quantity_applied = $detproduct['quantity_discount_applies'];
                        $o_res = $order_detail->add();

                        if (!$o_res) {
                            $newOrder->delete();
                            PrestaShopLogger::addLog(
                                'Order details cannot be created',
                                3,
                                null,
                                'Cart',
                                (int)$id_cart,
                                true
                            );
                            throw new PrestaShopException('Can\'t add Order details');
                        }
                    }
                    Hook::exec(
                        'actionValidateOrder',
                        array(
                            'cart' => $context->cart,
                            'order' => $newOrder,
                            'customer' => $context->customer,
                            'currency' => $context->currency,
                            'orderStatus' => Configuration::get('ELKJOPNORDIC_ORDER_STATE')
                        )
                    );

                    $order_status = new OrderState(
                        (int)Configuration::get('ELKJOPNORDIC_ORDER_STATE'),
                        (int)$context->language->id
                    );
                    foreach ($context->cart->getProducts() as $cproduct) {
                        if ($order_status->logable) {
                            ProductSale::addProductSale(
                                (int)$cproduct['id_product'],
                                (int)$cproduct['cart_quantity']
                            );
                        }
                    }
            $order_payment = new OrderPayment();
            $order_payment->amount = $newOrder->total_paid;
            $order_payment->payment_method = $newOrder->payment;
            $order_payment->order_reference = $newOrder->reference;
            $order_payment->transaction_id = $newOrder->reference;
            $order_payment->id_currency = $newOrder->id_currency;
            $order_payment->conversion_rate = $newOrder->conversion_rate;
            $order_payment->date_add = date("Y-m-d H:i:s");
            $order_payment->add();
                    // Set the order status
                    $new_history = new OrderHistory();
                    $new_history->id_order = (int)$newOrder->id;
                    $new_history->changeIdOrderState((int)Configuration::get('ELKJOPNORDIC_ORDER_STATE'), $newOrder, true);
                    $new_history->add(true, $extra_vars);

                    // Switch to back order if needed
                    if (Configuration::get('PS_STOCK_MANAGEMENT') && $order_detail->getStockState()) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$newOrder->id;
                        $history->changeIdOrderState(
                            Configuration::get(
                                $newOrder->valid ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'
                            ),
                            $newOrder,
                            true
                        );
                        $history->add();
                    }


                    // Order is reloaded because the status just changed

                    // Send an e-mail to customer (one order = one email)

                    //  updates stock in shops
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_list = $newOrder->getProducts();
                        foreach ($product_list as $product) {
                            // if the available quantities depends on the physical stock
                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                // synchronizes
                                StockAvailable::synchronize($product['product_id'], $newOrder->id_shop);
                            }
                        }
                    }

                    $product_list = $newOrder->getProducts();
                    foreach ($product_list as $product) {
                        $idProd = $product['product_id'];
                        $idProdAttr = $product['product_attribute_id'];
                        $qtyToReduce = (int)$product['product_quantity'] * -1;
                        StockAvailable::updateQuantity($idProd, $idProdAttr, $qtyToReduce, $newOrder->id_shop);
                        Hook::exec(
                            'actionUpdateQuantity',
                            array(
                                'id_product' => $product['product_id'],
//                                'id_order' => $newOrder->id
                            )
                        );
                    }

                    if ($newOrder && $newOrder->id) {
                        return $newOrder->id;
                    } else {
                        $newOrder->delete();
                    }
                } else {
                    $newOrder->delete();
                }
            }catch (Exception $e){
               echo  $e->getMessage();die;
                return false;
            }

        }

        /**
         * @param $Statecode
         * @param $countryCode
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function getLocalizationDeatails($Statecode, $countryCode = '', $cname = '')
        {
      
            $db = Db::getInstance();
            $default_lang = ((int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')) ?
                (int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE') : (int)Configuration::get('PS_LANG_DEFAULT');
            if ($countryCode != '') {
                $sql = "SELECT c.id_country, cl.name FROM `" . _DB_PREFIX_ . "country` c LEFT JOIN `" .
                    _DB_PREFIX_ . "country_lang` cl on (c.id_country =cl.id_country) WHERE `iso_code` LIKE '" .
                    Tools::substr($countryCode, 0, 2) . "' OR cl.name LIKE '%" .
                    $cname . "' LIMIT 1";

            } else if ($cname) {
                $sql = "SELECT c.id_country, cl.name FROM `" . _DB_PREFIX_ . "country` c LEFT JOIN `" .
                    _DB_PREFIX_ . "country_lang` cl on (c.id_country =cl.id_country) WHERE cl.name LIKE '%" .
                    $cname . "' OR `iso_code` LIKE '" .
                    Tools::substr($countryCode, 0, 2) . "' LIMIT 1";

            }
            
            $Execute = $db->ExecuteS($sql);

            if (is_array($Execute) && count($Execute) && isset($Execute['0'])) {
                $country_id = 0;
                $country_name = '';
                if (isset($Execute['0']['id_country']) && $Execute['0']['id_country']) {
                    $country_id = $Execute['0']['id_country'];
                    $country_name = $Execute['0']['name'];
                }
                if ($country_id) {
                    $Execute = $db->ExecuteS("SELECT `id_state`,`name` FROM `" . _DB_PREFIX_ .
                        "state` WHERE `id_country`='" . $country_id . "' AND `name` LIKE '%" . $Statecode . "%'");
                    if (is_array($Execute) && count($Execute)) {
                        if (isset($Execute['0']['id_state']) && isset($Execute['0']['name'])) {
                            return array(
                                'country_id' => $country_id,
                                'zone_id' => $Execute['0']['id_state'],
                                'name' => $Execute['0']['name'],
                                'country_name' => $country_name
                            );
                        };
                    } else {
                        return array(
                            'country_id' => $country_id,
                            'zone_id' => '',
                            'name' => '',
                            'country_name' => $country_name
                        );
                    }
                } else {
                    return array(
                        'country_id' => '',
                        'zone_id' => '',
                        'name' => '',
                        'country_name' => ''
                    );
                }
            } else {
                return array(
                    'country_id' => '',
                    'zone_id' => '',
                    'name' => '',
                    'country_name' => ''
                );
            }
        }

        /**
         * @param $purchaseOrderId
         * @param string $url
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function rejectOrder($purchaseOrderId, $url = 'orders')
        {
            $cedelkjopnordicHelper = new CedElkjopnordicHelper();
            $params = array();

            $response = $cedelkjopnordicHelper->WPutRequest($url . '/' . $purchaseOrderId . '/cancel', $params);
            try {
                if (isset($response['success']) && $response['success']) {
                    return array('success' => true, 'message' => $purchaseOrderId . ': Successfully Rejected');
                } else {
                    $error_message = 'Failed to cancel Order';
                    if (isset($response['message'])) {
                        $error_message = $response['message'];
                    }
                    return array('success' => false, 'message' => $purchaseOrderId . ':' . $error_message);
                }
            } catch (\Exception $e) {
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    'rejectOrder',
                    json_encode(
                        array(
                            'Response' => $response
                        )
                    ),
                    true
                );
                return array('success' => false, 'message' => $purchaseOrderId . ':' . json_encode($response));
            }
        }

        /**
         * @param $elkjopnordic_order_id
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function shipOrder($elkjopnordic_order_id)
        {
            $cedelkjopnordicHelper = new CedElkjopnordicHelper();
            $response = $cedelkjopnordicHelper->WPutRequest('orders/' . $elkjopnordic_order_id . '/ship');
            try {
                if (isset($response['success']) && $response['success']) {
                    if (isset($response['response']) && $response['response']) {
                        return array('success' => true, 'response' => json_encode('Successfully Added Tracking'));
                    } else {
                        return array('success' => false, 'message' => $response['message']);
                    }
                } else {
                    $data = $cedelkjopnordicHelper->xml2array($response['message']);
                    if (isset($data['error'])) {
                        $error_message = $data['error']['message'];
                        return array('success' => false, 'message' => $error_message);
                    }
                }
            } catch (Exception $e) {
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Exception',
                    'rejectOrder',
                    json_encode(
                        array(
                            'Response' => $e->getMessage()
                        )
                    ),
                    true
                );
                return array('success' => false, 'message' => $response);
            }
            return $response;
        }

        /**
         * @param $acceptdata
         * @param $purchaseOrderId
         * @param string $url
         * @return array|bool|mixed
         * @throws PrestaShopDatabaseException
         */
        public
        function acceptOrder($acceptdata, $purchaseOrderId, $url = 'orders')
        {
            $cedelkjopnordicHelper = new CedElkjopnordicHelper();
            $response = $cedelkjopnordicHelper->WPutRequest($url . '/' . $purchaseOrderId . '/accept', $acceptdata);

            try {
                if (isset($response['success']) && $response['success']) {
                    $response = $response['response'];
                    $response = json_decode($response, true);
                    if (isset($response['error'])) {
                        return array('success' => false, 'message' => $response['error']);
                    } elseif ($response == null) {
                        return array('success' => false, 'message' => 'Some Error Try After Some Time.');
                    }
                    $order_state = Configuration::get('ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE');
                    $this->updateOrderStatus($purchaseOrderId, $order_state);
                    return $response;
                } else {
                    $data = $cedelkjopnordicHelper->xml2array($response['message']);
                    if (isset($data['error'])) {
                        $error_message = $data['error']['message'];
                        return array('success' => false, 'message' => $error_message);
                    }
                }
            } catch (Exception $e) {
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    'acceptOrder',
                    json_encode(
                        array(
                            'Response' => $response
                        )
                    ),
                    true
                );
                return false;
            }
        }

        /**
         * @param $order_id
         * @param $id_order_state
         * @throws PrestaShopDatabaseException
         * @throws PrestaShopException
         */
        public
        function updateOrderStatus($order_id, $id_order_state)
        {
            $CedElkjopnordicHelper = new CedElkjopnordicHelper();

            $order_state = new OrderState((int)$id_order_state);
            if ((int)$order_id && !empty($order_state)) {
                try {
                    $order = new Order((int)$order_id);
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = (int)Context::getContext()->employee->id;
                    $use_existings_payment = !$order->hasInvoice();
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    $product_list = $order->getProducts();
                    foreach ($product_list as $product) {
                        $idProd = $product['product_id'];
                        $idProdAttr = $product['product_attribute_id'];
                        $qtyToReduce = (int)$product['product_quantity'] * -1;
                        StockAvailable::updateQuantity($idProd, $idProdAttr, $qtyToReduce, $order->id_shop);
                    }
                } catch (\Exception $e) {
                    $CedElkjopnordicHelper->log(
                        __METHOD__,
                        'Exception',
                        'Update Order State',
                        json_encode(
                            array(
                                'Response' => $e->getMessage()
                            )
                        ),
                        true
                    );
                }
            } else {
                $CedElkjopnordicHelper->log(
                    __METHOD__,
                    'Exception',
                    'Missing Order ID or State in Update Order State',
                    '',
                    true
                );
            }
        }

        /**
         * @param $elkjopnordic_order_id
         * @param string $carrier_code
         * @param string $carrier_name
         * @param string $carrier_url
         * @param string $tracking_number
         * @return array
         * @throws PrestaShopDatabaseException
         */
        public
        function shipCompleteOrder(
            $elkjopnordic_order_id,
            $carrier_code = '',
            $carrier_name = '',
            $carrier_url = '',
            $tracking_number = ''
        )
        {
            $cedelkjopnordicHelper = new CedElkjopnordicHelper();
            $params = array();
            if ($elkjopnordic_order_id) {
                $params['orderId'] = $elkjopnordic_order_id;
            }

            if ($carrier_code) {
                $params['carrier_code'] = $carrier_code;
            }

            if ($carrier_name) {
                $params['carrier_name'] = $carrier_name;
            }

            if ($carrier_url) {
                $params['carrier_url'] = $carrier_url;
            }

            if ($tracking_number) {
                $params['tracking_number'] = $tracking_number;
            } else {
                $params['tracking_number'] = '0';
            }
            if(trim($params['tracking_number'])==0){
                $response = $cedelkjopnordicHelper->WPutRequest('orders/' . $elkjopnordic_order_id . '/ship', array());
            } else {
                $response = $cedelkjopnordicHelper->WPutRequest('orders/' . $elkjopnordic_order_id . '/tracking', $params);
            }

            
            try {
                if (isset($response['success']) && $response['success']) {
                    if (isset($response['response']) && $response['response']) {
                        return array('success' => true, 'response' => json_encode('Successfully Added Tracking'));
                    } else {
                        return array('success' => false, 'message' => $response['message']);
                    }
                } else {
                    $data = $cedelkjopnordicHelper->xml2array($response['message']);
                    if (isset($data['error'])) {
                        $error_message = $data['error']['message'];
                        return array('success' => false, 'message' => $error_message);
                    }
                }
            } catch (Exception $e) {
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Exception',
                    'rejectOrder',
                    json_encode(
                        array(
                            'Response' => $e->getMessage()
                        )
                    ),
                    true
                );
                return array('success' => false, 'message' => $response);
            }
        }

        /**
         * @param $iso_3
         * @return mixed|string
         */
        public
        function getISOCode2ByISOCode3($iso_3)
        {
            $code = '';
            $allCodes = array(
                "AFG" => "AF",
                "ALA" => "AX",
                "ALB" => "AL",
                "DZA" => "DZ",
                "ASM" => "AS",
                "AND" => "AD",
                "AGO" => "AO",
                "AIA" => "AI",
                "ATA" => "AQ",
                "ATG" => "AG",
                "ARG" => "AR",
                "ARM" => "AM",
                "ABW" => "AW",
                "AUS" => "AU",
                "AUT" => "AT",
                "AZE" => "AZ",
                "BHS" => "BS",
                "BHR" => "BH",
                "BGD" => "BD",
                "BRB" => "BB",
                "BLR" => "BY",
                "BEL" => "BE",
                "BLZ" => "BZ",
                "BEN" => "BJ",
                "BMU" => "BM",
                "BTN" => "BT",
                "BOL" => "BO",
                "BES" => "BQ",
                "BIH" => "BA",
                "BWA" => "BW",
                "BVT" => "BV",
                "BRA" => "BR",
                "IOT" => "IO",
                "BRN" => "BN",
                "BGR" => "BG",
                "BFA" => "BF",
                "BDI" => "BI",
                "CPV" => "CV",
                "KHM" => "KH",
                "CMR" => "CM",
                "CAN" => "CA",
                "CYM" => "KY",
                "CAF" => "CF",
                "TCD" => "TD",
                "CHL" => "CL",
                "CHN" => "CN",
                "CXR" => "CX",
                "CCK" => "CC",
                "COL" => "CO",
                "COM" => "KM",
                "COG" => "CG",
                "COD" => "CD",
                "COK" => "CK",
                "CRI" => "CR",
                "CIV" => "CI",
                "HRV" => "HR",
                "CUB" => "CU",
                "CUW" => "CW",
                "CYP" => "CY",
                "CZE" => "CZ",
                "DNK" => "DK",
                "DJI" => "DJ",
                "DMA" => "DM",
                "DOM" => "DO",
                "ECU" => "EC",
                "EGY" => "EG",
                "SLV" => "SV",
                "GNQ" => "GQ",
                "ERI" => "ER",
                "EST" => "EE",
                "ETH" => "ET",
                "FLK" => "FK",
                "FRO" => "FO",
                "FJI" => "FJ",
                "FIN" => "FI",
                "FRA" => "FR",
                "GUF" => "GF",
                "PYF" => "PF",
                "ATF" => "TF",
                "GAB" => "GA",
                "GMB" => "GM",
                "GEO" => "GE",
                "DEU" => "DE",
                "GHA" => "GH",
                "GIB" => "GI",
                "GRC" => "GR",
                "GRL" => "GL",
                "GRD" => "GD",
                "GLP" => "GP",
                "GUM" => "GU",
                "GTM" => "GT",
                "GGY" => "GG",
                "GIN" => "GN",
                "GNB" => "GW",
                "GUY" => "GY",
                "HTI" => "HT",
                "HMD" => "HM",
                "VAT" => "VA",
                "HND" => "HN",
                "HKG" => "HK",
                "HUN" => "HU",
                "ISL" => "IS",
                "IND" => "IN",
                "IDN" => "ID",
                "IRN" => "IR",
                "IRQ" => "IQ",
                "IRL" => "IE",
                "IMN" => "IM",
                "ISR" => "IL",
                "ITA" => "IT",
                "JAM" => "JM",
                "JPN" => "JP",
                "JEY" => "JE",
                "JOR" => "JO",
                "KAZ" => "KZ",
                "KEN" => "KE",
                "KIR" => "KI",
                "PRK" => "KP",
                "KOR" => "KR",
                "KWT" => "KW",
                "KGZ" => "KG",
                "LAO" => "LA",
                "LVA" => "LV",
                "LBN" => "LB",
                "LSO" => "LS",
                "LBR" => "LR",
                "LBY" => "LY",
                "LIE" => "LI",
                "LTU" => "LT",
                "LUX" => "LU",
                "MAC" => "MO",
                "MKD" => "MK",
                "MDG" => "MG",
                "MWI" => "MW",
                "MYS" => "MY",
                "MDV" => "MV",
                "MLI" => "ML",
                "MLT" => "MT",
                "MHL" => "MH",
                "MTQ" => "MQ",
                "MRT" => "MR",
                "MUS" => "MU",
                "MYT" => "YT",
                "MEX" => "MX",
                "FSM" => "FM",
                "MDA" => "MD",
                "MCO" => "MC",
                "MNG" => "MN",
                "MNE" => "ME",
                "MSR" => "MS",
                "MAR" => "MA",
                "MOZ" => "MZ",
                "MMR" => "MM",
                "NAM" => "NA",
                "NRU" => "NR",
                "NPL" => "NP",
                "NLD" => "NL",
                "NCL" => "NC",
                "NZL" => "NZ",
                "NIC" => "NI",
                "NER" => "NE",
                "NGA" => "NG",
                "NIU" => "NU",
                "NFK" => "NF",
                "MNP" => "MP",
                "NOR" => "NO",
                "OMN" => "OM",
                "PAK" => "PK",
                "PLW" => "PW",
                "PSE" => "PS",
                "PAN" => "PA",
                "PNG" => "PG",
                "PRY" => "PY",
                "PER" => "PE",
                "PHL" => "PH",
                "PCN" => "PN",
                "POL" => "PL",
                "PRT" => "PT",
                "PRI" => "PR",
                "QAT" => "QA",
                "REU" => "RE",
                "ROU" => "RO",
                "RUS" => "RU",
                "RWA" => "RW",
                "BLM" => "BL",
                "SHN" => "SH",
                "KNA" => "KN",
                "LCA" => "LC",
                "MAF" => "MF",
                "SPM" => "PM",
                "VCT" => "VC",
                "WSM" => "WS",
                "SMR" => "SM",
                "STP" => "ST",
                "SAU" => "SA",
                "SEN" => "SN",
                "SRB" => "RS",
                "SYC" => "SC",
                "SLE" => "SL",
                "SGP" => "SG",
                "SXM" => "SX",
                "SVK" => "SK",
                "SVN" => "SI",
                "SLB" => "SB",
                "SOM" => "SO",
                "ZAF" => "ZA",
                "SGS" => "GS",
                "SSD" => "SS",
                "ESP" => "ES",
                "LKA" => "LK",
                "SDN" => "SD",
                "SUR" => "SR",
                "SJM" => "SJ",
                "SWZ" => "SZ",
                "SWE" => "SE",
                "CHE" => "CH",
                "SYR" => "SY",
                "TWN" => "TW",
                "TJK" => "TJ",
                "TZA" => "TZ",
                "THA" => "TH",
                "TLS" => "TL",
                "TGO" => "TG",
                "TKL" => "TK",
                "TON" => "TO",
                "TTO" => "TT",
                "TUN" => "TN",
                "TUR" => "TR",
                "TKM" => "TM",
                "TCA" => "TC",
                "TUV" => "TV",
                "UGA" => "UG",
                "UKR" => "UA",
                "ARE" => "AE",
                "GBR" => "GB",
                "USA" => "US",
                "UMI" => "UM",
                "URY" => "UY",
                "UZB" => "UZ",
                "VUT" => "VU",
                "VEN" => "VE",
                "VNM" => "VN",
                "VGB" => "VG",
                "VIR" => "VI",
                "WLF" => "WF",
                "ESH" => "EH",
                "YEM" => "YE",
                "ZMB" => "ZM",
                "ZWE" => "ZW"
            );
            foreach ($allCodes as $key => $code) {
                if ($key == $iso_3) {
                    return $code;
                }
            }
            return $code;
        }
    }
