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

include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicOrder.php';

class AdminCedElkjopnordicOrderController extends ModuleAdminController
{
    public $toolbar_title;
    public $cedElkjopnordicHelper;
    protected $statuses_array = array();
    protected $bulk_actions;
    public $cedElkjopnordicOrder;

    public function __construct()
    {
        $this->cedElkjopnordicOrder = new CedElkjopnordicOrder();
        
        $this->bootstrap = true;
        $this->table = 'order';
        $this->identifier = 'id_order';
        $this->className = 'Order';
        $this->lang = false;
        $this->addRowAction('view');
        $this->addRowAction('acknowledge');
        $this->addRowAction('reject');
        $this->addRowAction('sendinvoice');

        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
        a.id_currency,
        a.id_order AS id_pdf,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        osl.`name` AS `osname`,
        os.`color`,
        IF((SELECT so.id_order FROM `' . _DB_PREFIX_ . 'orders` so WHERE so.id_customer = a.id_customer AND so.id_order
         < a.id_order LIMIT 1) > 0, 0, 1) as new,
        country_lang.name as cname,
        IF(a.valid, 1, 0) badge_success,wo.`id` as `id`,';

        $this->_join = '
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)
        RIGHT JOIN `' . _DB_PREFIX_ . 'cedelkjopnordic_order` wo ON (wo.`order_id` = a.`id_order`)
        LEFT JOIN `' . _DB_PREFIX_ . 'address` address ON address.id_address = a.id_address_delivery
        LEFT JOIN `' . _DB_PREFIX_ . 'country` country ON address.id_country = country.id_country
        LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` 
        AND country_lang.`id_lang` = ' . (int)$this->context->language->id . ')
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = a.`current_state`)
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` 
        AND osl.`id_lang` = ' . (int)$this->context->language->id . ')';
        // for multishop comatibility start
        $shop_id = Shop::getContextShopID();
        if($shop_id){
            $this->_where = ' AND wo.import_shop_id = '.$shop_id;
        } else {
            $shop_group_id = Shop::getContextShopGroupID();
            if($shop_group_id && !$shop_id){
                $group_shops = Shop::getShops(true,$shop_group_id);
                $group_shop_ids = array_column($group_shops,'id_shop');
                if(!empty($group_shop_ids))
                    $this->_where = ' AND wo.import_shop_id IN ('.implode(",",$group_shop_ids).') ';
            }
        }
        // for multishop comatibility end
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;
//        $this->_select = 'wo.`id` as `id`';
        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = array(
            'id' => array(
                'title' => 'ID',
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ), 
            'id_order' => array(
                'title' => 'Order ID',
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'elkjopnordic_order_id' => array(
                'title' => 'Purchase Order ID',
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'status' => array(
                'title' => 'Elkjopnordic Status',
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'reference' => array(
                'title' => 'Reference'
            ),
            'customer' => array(
                'title' => 'Customer',
                'havingFilter' => true,
            ),
        );

        $this->fields_list = array_merge($this->fields_list, array(
            'total_paid_tax_incl' => array(
                'title' => 'Total',
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true
            ),
            'payment' => array(
                'title' => 'Payment'
            ),
            'osname' => array(
                'title' => 'Status',
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname'
            ),
            'date_add' => array(
                'title' => 'Date',
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            )

        ));
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT DISTINCT c.id_country, cl.`name`
            FROM `' . _DB_PREFIX_ . 'orders` o
            ' . Shop::addSqlAssociation('orders', 'o') . '
            INNER JOIN `' . _DB_PREFIX_ . 'address` a ON a.id_address = o.id_address_delivery
            INNER JOIN `' . _DB_PREFIX_ . 'country` c ON a.id_country = c.id_country
            INNER JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' .
            (int)$this->context->language->id . ')ORDER BY cl.name ASC');

        $country_array = array();
        foreach ($result as $row) {
            $country_array[$row['id_country']] = $row['name'];
        }

        $part1 = array_slice($this->fields_list, 0, 3);
        $part2 = array_slice($this->fields_list, 3);

        $this->fields_list = array_merge($part1, $part2);


        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;

        if (Tools::isSubmit('id_order')) {
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }

        $this->bulk_actions = array(
            'accept' => array('text' => 'Accept Order', 'icon' => 'icon-refresh'),
            'cancel' => array('text' => 'Cancel Order', 'icon' => 'icon-refresh'),
        );

        if (Tools::getIsset('fetchslips')) {
            $cedelkjopnordicOrder = new CedElkjopnordicOrder();
            $url = 'orders/documents/download';
            $sql = "SELECT `elkjopnordic_order_id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `status`= 'SHIPPED' OR `status`= 'SHIPPING'";
            $db = Db::getInstance();
            $ids_to_accept = $db->ExecuteS($sql);
            
            $order_ids = array();
            if(!empty($ids_to_accept)) {
                    foreach($ids_to_accept as $ids_to_ac) {
                    $order_ids[] = trim($ids_to_ac['elkjopnordic_order_id']);
                    }
            }
            //$params = array('order_ids' => implode(',', $order_ids),'document_codes' => 'SHIP_LABEL,SYSTEM_DELIVERY_BILL');
            $order_ids = array_chunk($order_ids,80);
            foreach($order_ids as $order_id){
            $params = array('order_ids' => implode(',', $order_id));
           
            $file = $cedelkjopnordicOrder->fetchDocumentsFile($params, $url, array());
            if($file) {
                file_put_contents(_PS_MODULE_DIR_.'cedelkjopnordic/pdf/order/pdfslips.zip',$file);
                $zip = new ZipArchive;
                $res = $zip->open(_PS_MODULE_DIR_.'cedelkjopnordic/pdf/order/pdfslips.zip');
                if ($res === TRUE) {
                    $zip->extractTo(_PS_MODULE_DIR_.'cedelkjopnordic/pdf/order/');
                    $zip->close();
                    $this->confirmations[] = 'Label Fetched Successfully.';
                } else {
                    $this->errors[] = 'Failed to create label.';
                }
            } else {
                $this->errors[] = 'Failed to create label.';
            }
            }
            
        }
        parent::__construct();
    }

    /**
     * @param array $order_ids
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processBulkAcknowledge($order_ids = array())
    {
        if (count($order_ids)) {
            // $cedelkjopnordicHelper = new CedElkjopnordicHelper;
            $cedelkjopnordicOrder = new CedElkjopnordicOrder;
            $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `id` IN (" .
                implode(',', $order_ids) . ")";
            $db = Db::getInstance();
            $ids_to_accept = $db->ExecuteS($sql);
            if (is_array($ids_to_accept) && count($ids_to_accept)) {
                foreach ($ids_to_accept as $value) {
                    $elkjopnordic_order_id = $value['elkjopnordic_order_id'];
                    $order_data = $value['order_data'];
                    $order_data = json_decode($order_data, true);
                    $result = $cedelkjopnordicOrder->checkAcceptOrder($order_data);
                    if (isset($result['success']) && $result['success']) {
                       // $cedelkjopnordicOrder->updateOrderStatus($elkjopnordic_order_id, (int)Configuration::get('ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE'));
                        $db->update(
                            'cedelkjopnordic_order',
                            array(
                                'status' => 'Accepted'
                            ),
                            'elkjopnordic_order_id="' . $elkjopnordic_order_id . '"'
                        );
                        $this->confirmations[] = 'Order ' . $elkjopnordic_order_id . ' : ' . $result['message'] . '<br>';
                    } else {
                        $this->errors[] = 'Order ' . $elkjopnordic_order_id . ' : ' . $result['message'];
                    }
                }
            }
        }
    }

    /**
     * @param array $order_ids
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processBulkCancel($order_ids = array())
    {
        if (count($order_ids)) {
            $sql = "SELECT `elkjopnordic_order_id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `id` IN (" .
                implode(',', $order_ids) . ")";
            $db = Db::getInstance();
            $ids_to_accept = $db->ExecuteS($sql);
            if (is_array($ids_to_accept) && count($ids_to_accept)) {
                //     $cedelkjopnordicHelper = new CedElkjopnordicHelper;
                $cedelkjopnordicOrder = new CedElkjopnordicOrder();
                foreach ($ids_to_accept as $value) {
                    $elkjopnordic_order_id = $value['elkjopnordic_order_id'];
                    $result = $cedelkjopnordicOrder->rejectOrder($elkjopnordic_order_id, 'orders');
                    if (isset($result['success']) && $result['success']) {
                        $cedelkjopnordicOrder->updateOrderStatus($elkjopnordic_order_id, 'Elkjopnordic_ORDER_STATE_ACKNOWLEDGE');
//                            $cedelkjopnordicOrder->updateelkjopnordicOrderData($value['elkjopnordic_order_id'], $data);
                        $db->update(
                            'cedelkjopnordic_order',
                            array(
                                'status' => 'CANCELED'
                            ),
                            'elkjopnordic_order_id="' . $elkjopnordic_order_id . '"'
                        );
                        $this->confirmations[] = 'Order ' . $elkjopnordic_order_id . ' : ' . $result['message'] . '<br>';
                    } else {
                        $this->errors[] = 'Order ' . $elkjopnordic_order_id . ' : ' . $result['message'];
                    }
                }
            }
        }
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws SmartyException
     */
    public function displayAcknowledgeLink($token = null, $id = null, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Accept', self::$cache_lang)) {
            self::$cache_lang['Accept'] = 'Accept';
        }

        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&acceptorder=' .
                $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Accept'],
            'id' => $id,
            'name' => $name
        ));

        return $tpl->fetch();
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws SmartyException
     */
    public function displaySendinvoiceLink($token = null, $id = null, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Send Invoice', self::$cache_lang)) {
            self::$cache_lang['Send Invoice'] = 'Send Invoice';
        }

        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&send_invoice=' .
                $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Send Invoice'],
            'id' => $id,
            'name' => $name
        ));

        return $tpl->fetch();
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws SmartyException
     */
    public function displayRejectLink($token = null, $id = null, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Cancel', self::$cache_lang)) {
            self::$cache_lang['Cancel'] = 'Cancel';
        }

        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&cancelorder=' .
                $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Cancel'],
            'id' => $id,
            'name' => $name
        ));

        return $tpl->fetch();
    }

    /**
     *
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order'] = array(
                'href' => self::$currentIndex . '&fetchorder&token=' . $this->token,
                'desc' => 'Fetch Order',
                'icon' => 'process-icon-new'
            );
            
             $this->page_header_toolbar_btn['new_slips'] = array(
                'href' => self::$currentIndex . '&fetchslips&token=' . $this->token,
                'desc' => 'Fetch Slips',
                'icon' => 'process-icon-download'
            );
        }
        parent::initPageHeaderToolbar();
    }

    /**
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        $this->toolbar_btn['export'] = array('href' => self::$currentIndex . '&export' . $this->table . '&token=' .
            $this->token, 'desc' => $this->l('Export'));
        if ($this->display == 'view') {
            /** @var Order $order */
            $order = $this->loadObject();
            $customer = $this->context->customer;

            if (!Validate::isLoadedObject($order)) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdmincedelkjopnordicOrder'));
            }
            $firstName = '';
            $LastName = '';
            if (isset($customer, $customer->firstname) && $customer->firstname) {
                $firstName = $customer->firstname;
                $LastName = $customer->lastname;
            }
            $this->toolbar_title[] = sprintf('Order %1$s from %2$s %3$s', $order->reference, $firstName, $LastName);
            $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);
        }
    }

    /**
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderList()
    {
        if (Tools::isSubmit('submitBulkacceptorder' . $this->table)) {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }

            $this->tpl_list_vars['updateOrderStatus_mode'] = true;
            $this->tpl_list_vars['order_statuses'] = $this->statuses_array;
            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $this->tpl_list_vars['POST'] = Tools::getAllValues();
        }
        if (Tools::getIsset('fetchorder')) {
            $cedelkjopnordicOrder = new CedElkjopnordicOrder();
            $cedelkjopnordicHelper = new CedElkjopnordicHelper;
            $status = $cedelkjopnordicHelper->isEnabled();
            if ($status) {
                $url = 'orders';
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    'Exception on Fetch Order:',
                    json_encode(
                        array(
                            'url' => $url,
                            'Request Param' => '',
                            'Response' => ''
                        )
                    ),
                    true
                );

                $params = array();
                $createdStartDate = date('Y-m-d');

                $order = array();
                $order['order_state_codes'] = 'WAITING_ACCEPTANCE';
                $order_data = $cedelkjopnordicOrder->fetchOrder($params, $url, $order);
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    'FetchOrder',
                    json_encode(
                        array(
                            'url' => $url,
                            'Request Param' => $params,
                            'Response' => $order_data
                        )
                    ),
                    true
                );
                $cedelkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    'FetchOrder',
                    json_encode(
                        array(
                            'url' => $url,
                            'Request Param' => $params,
                            'Response' => $order_data
                        )
                    ),
                    true
                );
                if (isset($order_data['success']) && $order_data['success']) {
                    if (is_array($order_data['message'])) {
                        $orderDataMessage = array_filter($order_data['message']);
                    } else {
                        $orderDataMessage = $order_data['message'];
                    }

                    if (is_array($order_data['message']) && empty($orderDataMessage)) {
                        $this->confirmations[] = 'No new Order Found';
                    } else {
                        $this->confirmations[] = json_encode($order_data['message']);
                    }
                } elseif (isset($order_data['message'])) {
                    $fetch_errors = $order_data['message'];
                    $fetch_errors = json_decode($fetch_errors, true);
                    $error_msg = '';
                    if (isset($fetch_errors['errors']) && is_array($fetch_errors['errors']) &&
                        count($fetch_errors['errors'])) {
                        foreach ($fetch_errors['errors'] as $value) {
                            if (isset($value['0']['description'])) {
                                $error_msg .= $value['0']['description'];
                            }
                        }
                    }

                    if (isset($fetch_errors['message'])) {
                        $error_msg .= $fetch_errors['message'];
                    }
                    $this->errors[] = $error_msg;
                } else {
                    $this->errors[] = 'Some Error Occured. ';
                }
            }
        }

        return parent::renderList();
    }

    /**
     * @return bool|ObjectModel|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $cedelkjopnordicHelper = new CedElkjopnordicOrder;

        if (Tools::getIsset('action') && Tools::getValue('action') == 'acceptOrder') {
            $order_line_id = Tools::getValue('order_line_id');
            $order_id = Tools::getValue('order_id');
            $order_lines = array();
            $order_lines['order_lines'] = array();
            $order_lines['order_lines'][] = array('accepted' => true, 'id' => $order_line_id);
            $result = $cedelkjopnordicHelper->acceptOrder($order_lines, $order_id);
            die(json_encode($result));
        }
        if (Tools::getIsset('action') && Tools::getValue('action') == 'cancelOrder') {
            $order_line_id = Tools::getValue('order_line_id');
            $order_id = Tools::getValue('order_id');
            $order_lines = array();
            $order_lines['order_lines'] = array();
            $order_lines['order_lines'][] = array('accepted' => false, 'id' => $order_line_id);
            $result = $cedelkjopnordicHelper->rejectOrder($order_lines, $order_id);
            die(json_encode($result));
        }
        if (Tools::getIsset('action') && Tools::getValue('action') == 'shipOrder') {
            $elkjopnordic_order_id = Tools::getValue('order_id');

            $result = $cedelkjopnordicHelper->shipOrder($elkjopnordic_order_id);
            if (isset($result['success'])) {
                $cedelkjopnordicHelper->updateElkjopnordicOrderStatus($elkjopnordic_order_id, 'SHIPPED');
            }
            die(json_encode($result));
        }
        if (Tools::getIsset('action') && Tools::getValue('action') == 'shipCompleteOrder') {
            $elkjopnordic_order_id = Tools::getValue('order_id');
            $carrier_name = Tools::getValue('carrier_name');
            $carrier_code = Tools::getValue('carrier_code');
            $carrier_url = Tools::getValue('carrier_url');
            $tracking_number = Tools::getValue('tracking_number');
            $carrier_code = trim($carrier_code);
            $tracking_number = trim($tracking_number);
            if (!isset($carrier_code) || empty($carrier_code)) {
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Shipping Carrier is empty'
                )));
            } else {
                $result = $cedelkjopnordicHelper->shipCompleteOrder(
                    $elkjopnordic_order_id,
                    $carrier_code,
                    $carrier_name,
                    $carrier_url,
                    $tracking_number
                );
                if (isset($result['success'])) {
                    $cedelkjopnordicHelper->updateElkjopnordicOrderStatus($elkjopnordic_order_id, 'SHIPPED');
                }
                die(json_encode($result));
            }
        }
        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $cedelkjopnordicHelper = new CedElkjopnordicHelper;
                $this->errors[] = 'The order cannot be found within your database.';
            }
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }

        if (Tools::getIsset('submitBulkacceptorder') && Tools::getIsset('orderBox')) {
            if (count(Tools::getValue('orderBox'))) {
                $result = $this->processBulkAcknowledge(Tools::getValue('orderBox'));
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }
        if (Tools::getIsset('submitBulkcancelorder') && Tools::getIsset('orderBox')) {
            if (count(Tools::getValue('orderBox'))) {
                $result = $this->processBulkCancel(Tools::getValue('orderBox'));
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }
        if (Tools::getIsset('acceptorder') && Tools::getValue('acceptorder')) {
            $acceptorder = array(Tools::getValue('acceptorder'));
            if (count($acceptorder)) {
                $result = $this->processBulkAcknowledge($acceptorder);
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }
        if (Tools::getIsset('cancelorder') && Tools::getValue('cancelorder')) {
            $cancelorder = array(Tools::getValue('cancelorder'));
            if (count($cancelorder)) {
                $result = $this->processBulkCancel($cancelorder);
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }
        if (Tools::getIsset('send_invoice') && Tools::getValue('send_invoice')) {
            $id_order = Tools::getValue('send_invoice');
            if ((int)($id_order)) {
                $cedelkjopnordicHelper = new CedElkjopnordicOrder();
                $result = $cedelkjopnordicHelper->sendInvoice($id_order);
                if(isset($result['success']) && $result['success']) {
                    $this->confirmations[] = "Documents added successfully";
                } else {
                    $this->errors[] = $result['message'];
                }
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }
        parent::postProcess();
    }

    /**
     * @param $echo
     * @param $tr
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setOrderCurrency($echo, $tr)
    {   
        if(isset($tr['id_order'])){
        $order = new Order($tr['id_order']);
        return Tools::displayPrice($echo, (int)$order->id_currency);
        }
        
    }

    public function initContent()
    {
        parent::initContent();
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {

        $order = $this->loadObject();
        $order_data = (array)$order;
        $id_order = 0;
        if (isset($order_data['id']) && $order_data['id']) {
            $id_order = $order_data['id'];
        }
        if ($id_order) {
            $sql = "SELECT `order_data` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_order` where `id` = '" . $id_order . "'";
            $db = Db::getInstance();
            $result = $db->ExecuteS($sql);
            if (is_array($result) && count($result) && isset($result['0']['order_data'])) {
                if ($result['0']['order_data']) {
                    $order_data = json_decode($result['0']['order_data'], true);
                    $this->context->smarty->assign(array('carrierNames' => array()));
                    $this->context->smarty->assign(array('methodCodes' => array()));
                    $cedelkjopnordicHelper = new CedElkjopnordicHelper;
                    $carriers = array();
                    $methodCodes = $cedelkjopnordicHelper->methodCodeArrray();
                    $res = $cedelkjopnordicHelper->getElkjopnordicCarriers();
                    if (isset($res['success']) && $res['success']) {
                        $data = $res['response'];
                        $data = json_decode($data, true);
                        if (isset($data['carriers']) && !empty($data['carriers'])) {
                            $carriers = $data['carriers'];
                        }
                    }
                    $this->context->smarty->assign(array('carriers' => $carriers));
                    if ($methodCodes) {
                        $this->context->smarty->assign(array('methodCodes' => $methodCodes));
                    }
                    $carrierNames = $cedelkjopnordicHelper->carrierNameArray();
                    if ($carrierNames) {
                        $this->context->smarty->assign(array('carrierNames' => $carrierNames));
                    }

                    $this->context->smarty->assign(array('order_info' => array()));
                    if ($order_data) {
                        $shippingInfo = $order_data['customer']['shipping_address'];
                        unset($order_data['customer']['shipping_address']);
                        $shipping_data = array();
                        if (isset($order_data['shipments'])) {
                            $shipping_data = $order_data['shipments'];
                            if (!isset($shipping_data['shipment']['0'])) {
                                $temp_shipping_data = $shipping_data['shipment'];
                                $shipping_data['shipment'] = array();
                                $shipping_data['shipment']['0'] = $temp_shipping_data;
                            }
                            unset($order_data['shipments']);
                        }


                        $this->context->smarty->assign(array('shippingInfo' => $shippingInfo));

                        $orderLines = $order_data['order_lines'];
                        if (isset($order_data['order_lines'])) {
                            $orderLines = $order_data['order_lines'];
                        }
                        unset($order_data['order_lines']);

                        $this->context->smarty->assign(array('orderLines' => $orderLines));

                        $this->context->smarty->assign(array('order_info' => $order_data));

                        $this->context->smarty->assign(array('shipping_info' => $shipping_data));
                    }

                    $this->context->smarty->assign('ship', $this->context->link->
                        getAdminLink('AdminCedElkjopnordicOrder') . '&submitShippingNumber=true');
                    $this->context->smarty->assign('id_order', $id_order);
                    $this->context->smarty->assign('token', $this->token);
                    $parent = $this->context->smarty->fetch(_PS_MODULE_DIR_ .
                        'cedelkjopnordic/views/templates/admin/orders/form.tpl');
                    return $parent;
                }
            }
        } else {
            $this->warnings[] = 'The order is not created yet in store';
        }
        parent::renderView();
    }

    /**
     *
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();

        $this->addJqueryUI('ui.datepicker');
    }
}
