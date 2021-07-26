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
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicOrder.php';

class AdminCedElkjopnordicRejectedController extends ModuleAdminController
{
    public $cedElkjopnordicOrder;

    public function __construct()
    {
        $this->cedElkjopnordicOrder = new CedElkjopnordicOrder();
        $this->bootstrap = true;
        $this->table = 'cedelkjopnordic_order_error';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->addRowAction('cancel');
        $this->addRowAction('edit');
        $this->addRowAction('deletefailedorder');
       // $this->addRowAction('importorder');
        // for multishop comatibility start
        $shop_id = Shop::getContextShopID();
        if($shop_id){
            $this->_where = ' AND a.id_shop = '.$shop_id;
        } else {
            $shop_group_id = Shop::getContextShopGroupID();
            if($shop_group_id && !$shop_id){
                $group_shops = Shop::getShops(true,$shop_group_id);
                $group_shop_ids = array_column($group_shops,'id_shop');
                if(!empty($group_shop_ids))
                    $this->_where = ' AND a.id_shop IN ('.implode(",",$group_shop_ids).') ';
            }
        }
        // for multishop comatibility end
        $this->fields_list = array(
            'id' => array(
                'title' => 'ID',
                'type' => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'merchant_sku' => array(
                'title' => 'SKU',
                'type' => 'text',
            ),
            'elkjopnordic_order_id' => array(
                'title' => 'Elkjopnordic Order Id',
                'type' => 'text',
            ),
            'reason' => array(
                'title' => 'Reason',
                'type' => 'text',
            ),
            'created_at' => array(
                'title' => 'Date',
                'type' => 'text',
            ),
             'order_data' => array(
                    'title' => 'Edit And Re-Import',
                    'align' => 'text-center',
                    'type'  => 'text',
                    'search' => false,
                    'callback' => 'viewOrderButton',
                )
        );
        // for multishop comatibility start
        if(!$shop_id) {
            $this->fields_list['id_shop'] = array(
                'title' => 'Shop ID',
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            );
        }
        // for multishop comatibility end

        $this->bulk_actions = array(
            'cancal' => array('text' => 'Cancel Order', 'icon' => 'icon-refresh'),
            'delete' => array('text' => 'Delete', 'icon' => 'icon-pencil'),
        );
        if (Tools::isSubmit('submitElkjopnordicOrderSave')) {
            $this->submitElkjopnordicOrderSave();
        }

        if (Tools::getIsset('updated') && Tools::getValue('updated')) {
            $this->confirmations[] = "Order updated successfully";
        }

        if (Tools::getIsset('viewedRejectedOrder') && Tools::getValue('viewedRejectedOrder')) {
            $db = Db::getInstance();
            $db->update(
                'cedelkjopnordic_order_error',
                array(
                    'viewed_order' => 1
                ),
                1
            );
        }
        parent::__construct();
    }
    public function viewOrderButton($id,$data)
    {
        $data['order_data'] =  $id;  
        $data['token'] = $this->token;   
        $this->context->smarty->assign(
            $data
        );
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/failedorder/list/view_and_reimport.tpl'
        );
    }
     public function ajaxProcessresubmitFeed()
    {
        $response = "json data is incorrect";
        $cedelkjopnordicOrder = new CedElkjopnordicOrder();
        if(Tools::getIsset('feed_content') && Tools::getIsset('id') && Tools::getValue('id') && Tools::getValue('feed_content')){
            $id  = Tools::getValue('id');
            $response = 'There is Some Exception';
            $orderArr = Tools::getValue('feed_content');
            $db = Db::getInstance();
             try {
                        $orderData = json_decode($orderArr,true);
                        if ($orderData === null && json_last_error() !== JSON_ERROR_NONE) {
                           $response = "json data is incorrect";
                        } else {
                        $order_id = $orderData['order_id'];

                        $cedamazon_row_data = $db->getRow("SELECT * FROM `"._DB_PREFIX_."cedelkjopnordic_order` WHERE `elkjopnordic_order_id` LIKE '".pSQL($order_id)."'");
                        $import_shop_id = $cedamazon_row_data['import_shop_id'];

                        if (isset($cedamazon_row_data['id']) && $cedamazon_row_data['id'] && ($cedamazon_row_data['order_id']== null || $cedamazon_row_data['order_id']==0)) {
                            $cedamazon_row_id = $cedamazon_row_data['id'];
                           
                            if($cedelkjopnordicOrder->isElkjopnordicOrderIdExist($order_id, $import_shop_id)){
                
                                $prestashopId = $cedelkjopnordicOrder->createPrestashopOrder($orderData, $import_shop_id);
                             
                                if ($prestashopId) {
                                    
                                    $response = $db->update(
                                        'cedelkjopnordic_order',
                                        array(
                                            'order_id' => (int)$prestashopId,
                                            'status' => pSQL($orderData['order_state']),
                                            'order_data' => pSQL(json_encode($orderData)),
                                            'shipment_data' => pSQL(json_encode($orderData['customer']))
                                        ),
                                        'elkjopnordic_order_id LIKE "' . pSQL($order_id) . '"'
                                    );
                                    if($response){
                                        $response= 'Order Created With Id ' . $order_id .
                                            ' Successfully.';
                                        $db->execute("DELETE FROM `"._DB_PREFIX_."cedelkjopnordic_order_error` 
                                        WHERE `id` = '".(int)$id."'");
                                    }
                                    
                                } else {
                                        $response= 'Failed to Create Elkjopnordic Order With Id ' . $order_id .
                                            ' Please check Failed Order Log';
                                    }                  
                            }


                        } else {
                                $response= 'Elkjopnordic Order ' . $order_id .
                                    ' Already created.';
                        }
                        }
                        
                          } catch (\Exception $e) { 
                              $response = $e->getMessage();
                            
                        }
            
        }
       

        die(json_encode(array('message' => $response)));
    }
    /**
     * @param array $order_ids
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function processBulkCancel($order_ids = array())
    {
        $success = array();
        $errors = array();
        if (count($order_ids)) {
            $sql = "SELECT `elkjopnordic_order_id`,`order_data` FROM `" . _DB_PREFIX_ .
                "cedelkjopnordic_order_error` WHERE `id` IN (" . implode(',', $order_ids) . ")";
            $db = Db::getInstance();
            $ids_to_accept = $db->ExecuteS($sql);
            if (is_array($ids_to_accept) && count($ids_to_accept)) {
                $CedElkjopnordicHelper = new CedElkjopnordicOrder;

                foreach ($ids_to_accept as $value) {
                    $elkjopnordic_order_id = $value['elkjopnordic_order_id'];

                    $order_data = $value['order_data'];

                    $order_data = json_decode($order_data, true);
                    $rejectOrder = array();
                    if (isset($order_data['order_lines']) && count($order_data['order_lines'])) {
                        foreach ($order_data['order_lines'] as $order_data_value) {
                            $rejectOrder['order_lines'][] = array('accepted' => false, 'id' =>
                                $order_data_value['order_line_id']);
                        }
                    }
                    $result = $CedElkjopnordicHelper->rejectOrder($elkjopnordic_order_id);
                    if (isset($result['success'])) {
                        if ($result['success']) {
                            $success[] = isset($result['message']) ? $result['message'] : '';
                        } else {
                            $errors[] = isset($result['message']) ? $result['message'] : '';
                        }
                    } else {
                        $errors[] = isset($result['message']) ? $result['message'] : '';
                    }
//                    return $result;
                }
            }
        } else {
            $errors[] = 'Please Select Order(s).';
        }
        return array(
            'success' => $success,
            'error' => $errors
        );
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayCancelLink($token = null, $id = null, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Cancel', self::$cache_lang)) {
            self::$cache_lang['Cancel'] = $this->l('Cancel', 'Helper');
        }
        $tpl->assign(array(
            'href' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicRejected') . '&cancelorder=' .
                $id . '&id=' . $id,
            'action' => self::$cache_lang['Cancel'],
            'id' => $id,
            'token' => $token,
            'name' => $name
        ));

        return $tpl->fetch();
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayImportOrderLink($token = null, $id = null, $name = null)
    {
        if (!array_key_exists('Import', self::$cache_lang)) {
            self::$cache_lang['Import'] = $this->l('Re-Import', 'Helper');
        }

        $this->context->smarty->assign(array(
            'href' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicRejected') . '&importorder=' .
                $id . '&id=' . $id,
            'action' => self::$cache_lang['Import'],
            'id' => $id,
            'token' => $token,
            'name' => $name
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/orders/importorder_row_action.tpl'
        );
    }


    /**
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderList()
    {
        if (Tools::getIsset('cancelorder') && Tools::getValue('cancelorder')) {
            $acceptorder = array(Tools::getValue('cancelorder'));

            if (count($acceptorder)) {
                $result = $this->processBulkCancel($acceptorder);
                if (isset($result['success']) && $result['success']) {
                    $this->confirmations[] = 'Order Cancelled Successfully';
                } else {
                    if (isset($result['message']) && $result['message']) {
                        $this->errors[] =  $result['message'];
                    } else {
                        $this->errors[] = 'Failed to cancel Order.';
                    }
                }
            } else {
                $this->errors[] = 'Please Select Order(s)';
            }
        }

        if (Tools::getIsset('submitBulkcancalcedelkjopnordic_order_error')) {
            if (Tools::getIsset('cedelkjopnordic_order_errorBox') && count(Tools::getValue('cedelkjopnordic_order_errorBox'))) {
                $result = $this->processBulkCancel(Tools::getValue('cedelkjopnordic_order_errorBox'));
                if (isset($result['success'])) {
                    foreach ($result['success'] as $success) {
                        $this->confirmations[] = $success;
                    }
                }
                if (isset($result['error'])) {
                    foreach ($result['error'] as $error) {
                        $this->errors[] =  $error;
                    }
                }
            } else {
                $this->errors[] = 'Please Select Order(s).';
            }
        }

        if (Tools::getIsset('importorder') && Tools::getValue('importorder')) {
            $orderIds = (array)Tools::getValue('importorder');
            $result = $this->reImportOrder($orderIds);
            if (isset($result['success'])) {
                foreach ($result['success'] as $success) {
                    $this->confirmations[] =  $success ;
                }
            }
            if (isset($result['error'])) {
                foreach ($result['error'] as $error) {
                    $this->errors[]=  $error;
                }
            }
        }

        if (Tools::getIsset('submitBulkre-importcedelkjopnordic_order_error')) {
            if (Tools::getIsset('cedelkjopnordic_order_errorBox') && count(Tools::getValue('cedelkjopnordic_order_errorBox'))) {
                $result = $this->reImportOrder(Tools::getValue('cedelkjopnordic_order_errorBox'));
                if (isset($result['success'])) {
                    foreach ($result['success'] as $success) {
                        $this->confirmations[] =  $success;
                    }
                }
                if (isset($result['error'])) {
                    foreach ($result['error'] as $error) {
                        $this->errors[] =  $error;
                    }
                }
            } else {
                $this->errors[]= 'Please Select Order(s).';
            }
        }
        return parent::renderList();
    }

    /**
     *
     */
    public function initToolbar()
    {
        $this->toolbar_btn['export'] = array('href' => self::$currentIndex . '&export' . $this->table . '&token=' .
            $this->token, 'desc' => $this->l('Export'));
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getAllFailedOrders()
    {
        $db = Db::getInstance();
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'cedelkjopnordic_order_error` WHERE 1';
        $result = $db->executeS($sql);
        if (is_array($result)) {
            return $result;
        } else {
            return array();
        }
    }
    public function displaydeletefailedorderLink($token = null, $id = null, $name = null)
    {
        if ($token && $name) {
            $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        } else {
            $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        }
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = $this->l('Delete', 'Helper');
        }

        $tpl->assign(array(
            'href' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicRejected')
                .'&deleteorder='.$id.'&id='.$id,
            'action' => self::$cache_lang['Delete'],
            'id' => $id
        ));

        return $tpl->fetch();
    }

    public function postProcess()
    {
        try {
            $db = Db::getInstance();
           

            if (Tools::getIsset('deletefailedorder')) {
                $res = $db->delete(
                    'cedelkjopnordic_order_error'
                );
                if ($res) {
                    $this->confirmations[] = 'All Failed Orders Deleted Successfully';
                } else {
                    $this->errors[] = "Failed To Delete Failed Orders";
                }
            }
            if (Tools::getIsset('deleteorder') && Tools::getValue('deleteorder')) {
                $res = $db->delete(
                    'cedelkjopnordic_order_error',
                    'id='.(int)Tools::getValue('deleteorder')
                );
                if ($res) {
                    $this->confirmations[] = ' Failed Order Deleted Successfully';
                } else {
                    $this->errors[] = "Failed To Delete Failed Order";
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        parent::postProcess();
    }
    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        parent::renderForm();
        $errors = array();
        $id = (int)Tools::getValue('id');
        if ($id) {
            $db = Db::getInstance();
            $sql = 'Select * from ' . _DB_PREFIX_ . 'cedelkjopnordic_order_error where id=' . (int)$id;
            $failed_order_data = $db->executeS($sql);
            if (isset($failed_order_data[0]) && is_array($failed_order_data[0])) {
                $this->context->smarty->assign(array(
                    'currentToken' => Tools::getAdminTokenLite('AdminCedElkjopnordicRejected'),
                    'controllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicRejected'),
                    'id' => $id,
                    'failedOrderData' => isset($failed_order_data[0]['order_data']) ?
                        json_decode($failed_order_data[0]['order_data'], true) : array(),
                    'reason' => isset($failed_order_data[0]['reason']) ? $failed_order_data[0]['reason'] : '',
                ));
                $profileTemplate = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/orders/edit_failed_order.tpl'
                );
                return $profileTemplate;
            } else {
                $errors[] = 'No data found.';
            }
        }
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCedElkjopnordicRejected') .
            '&redirected=' . $id . '&errors=' . json_encode($errors));
    }

    /**
     * @throws PrestaShopException
     */
    public function submitElkjopnordicOrderSave()
    {
        $db = Db::getInstance();
        $id = (int)Tools::getValue('id');
        if ($id) {
            $order_data = Tools::getValue('order_data');
            $json_order_data = json_encode($order_data);
            $res = $db->update(
                'cedelkjopnordic_order_error',
                array(
                    'order_data' => $json_order_data
                ),
                'id=' . $id
            );
            if ($res) {
                $link = new LinkCore();
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicRejected') . '&updated=1';
                Tools::redirectAdmin($controller_link);
                $this->confirmations[] = "Order updated successfully";
            }
        } else {
            $this->errors[] = 'Error in saving data.';
        }
    }

    /**
     * @param $ids
     * @throws PrestaShopDatabaseException
     */
    public function reImportOrder($ids)
    {
        $errors = array();
        $success = array();
        $db = Db::getInstance();
        if (!is_array($ids)) {
            $ids = (array)$ids;
        }
        foreach ($ids as $id) {
            $sql = 'SELECT `order_data` FROM ' . _DB_PREFIX_ . 'cedelkjopnordic_order_error WHERE id=' . $id;
            $order_data = $db->executeS($sql);
            if (isset($order_data[0]['order_data'])) {
                $res = $this->cedElkjopnordicOrder->createPrestashopOrder(json_decode($order_data[0]['order_data'], true));
                if (!$res) {
                    $errors[] = 'Fail to import order Id:' . $id;
                } else {
                    $success[] = $id . 'imported successfully.';
                }
            } else {
                $errors[] = 'Order data not found.';
            }
        }

        return array(
            'success' => $success,
            'error' => $errors
        );
    }
    public function processBulkDelete()
    {
        $db = Db::getInstance();
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $pres_id) {
                $res = $db->delete(
                    'cedelkjopnordic_order_error',
                    'id='.(int)$pres_id
                );
            }
            if ($res) {
                $this->confirmations[] = count($this->boxes).' Failed Orders Deleted Successfully';
            }
        } else {
            $this->errors[] = "No order(s) selected";
        }
    }
}
