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

class AdminCedElkjopnordicLogsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'cedelkjopnordic_logs';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->_orderBy = 'id';
        $this->_orderWay = 'DESC';
        $this->addRowAction('deletelog');
        parent::__construct();
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
            'id'       => array(
                'title' => 'ID',
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'method'  => array(
                'title' => 'ACTION',
                'type'  => 'text',
                'align' => 'center',
            ),
            'type'     => array(
                'title' => 'TYPE',
                'type'  => 'text',
                'align' => 'center',
            ),
            'message' => array(
                'title' => 'MESSAGE',
                'type'  => 'text',
                'align' => 'center',
            ),
            'data' => array(
                'title' => 'RESPONSE',
                'type'  => 'text',
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-xs',
                'callback' => 'viewLogResponse',
            ),
            'created_at' => array(
                'title' => 'CREATED AT',
                'type' => 'datetime',
                'align' => 'center',

            ),
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
    }

    /**
     * @param null $token
     * @param null $id
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayDeleteLogLink($token = null, $id = null)
    {
        if ($token) {
            $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        } else {
            $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        }
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = $this->l('Delete', 'Helper');
        }

        $tpl->assign(array(
            'href' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicLogs') . '&deletelog=' . $id .
                '&id=' . $id,
            'action' => self::$cache_lang['Delete'],
            'id' => $id
        ));

        return $tpl->fetch();
    }

    /**
     * @param $data
     * @param $rowData
     * @return string
     * @throws SmartyException
     */
    public function viewLogResponse($data, $rowData)
    {
        $data = $rowData['data'];
        if(!$data){
            $data = json_encode(array("No Log data."));
        }
        $this->context->smarty->assign(array(
            'data' => $data,
            'id' => $rowData['id']
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/logs/log_data.tpl'
        );
    }

    public function initToolbar()
    {
        $this->toolbar_btn['export'] = array(
            'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Export')
        );
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['delete_logs'] = array(
                'href' => $this->context->link->getAdminLink('AdminCedElkjopnordicLogs') . '&delete_logs',
                'desc' => 'Delete All Logs',
                'icon' => 'process-icon-eraser'
            );
        }
        parent::initPageHeaderToolbar();
    }

    /**
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        return parent::renderList();
    }

    /**
     * @return bool|ObjectModel|void
     */
    public function postProcess()
    {
        if (Tools::getIsset('delete_logs')) {
            $result = $this->deleteLogs();
            if (isset($result['success']) && $result['success'] == true) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->errors[] = $result['message'];
            }
        }
        if (Tools::getIsset('deletelog')) {
            $result = $this->deleteLogs(Tools::getValue('id'));
            if (isset($result['success']) && $result['success'] == true) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->errors[] = $result['message'];
            }
        }
        parent::postProcess();
    }

    /**
     * @param string $log_id
     * @return array
     */
    public function deleteLogs($log_id = '')
    {
        $db = Db::getInstance();
        try {
            if (empty($log_id)) {
                $sql = "DELETE FROM  `" . _DB_PREFIX_ . "cedelkjopnordic_logs`";
            } else {
                $sql = "DELETE FROM  `" . _DB_PREFIX_ . "cedelkjopnordic_logs` WHERE `id` = " . (int)$log_id . "";
            }
            $res = $db->execute($sql);
            if ($res) {
                return array(
                    'success' => true,
                    'message' => "Log(s) deleted successfully"
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Failed to delete Log(s)"
                );
            }
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}
