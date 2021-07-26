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

require_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';
require_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicPricefeed.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProduct.php';

class AdminCedElkjopnordicReportController extends ModuleAdminController
{
    public $elkjopnordicOfferFeed;

    public function __construct()
    {
        $this->elkjopnordicOfferFeed = new CedElkjopnordicPricefeed();
        $this->bootstrap = true;
        $this->table = 'cedelkjopnordic_offers_feed';
        $this->identifier = 'import_id';
        $this->list_no_link = true;
        $this->addRowAction('update');
        $this->addRowAction('deleteOfferFeed');
        $this->className = 'CedElkjopnordicPricefeed';
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

            'import_id' => array(
                'title' => $this->l('Import ID'),
                'type' => 'text',
            ),

            'date_created' => array(
                'title' => $this->l('Date Created'),
                'type' => 'text',
            ),

            'lines_in_error' => array(
                'title' => $this->l('Lines in Error'),
                'type' => 'text',
            ),
            'lines_in_success' => array(
                'title' => $this->l('Lines in Success'),
                'type' => 'text',
            ),
            'lines_read' => array(
                'title' => $this->l('Lines Read'),
                'align' => 'text-center',
                'type' => 'text',
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'type' => 'text',
            ),
        );

        $this->fields_list['feed_file'] = array(
            'title' => 'Feed File',
            'align' => 'text-center',
            'search' => false,
            'callback' => 'viewDownloadButton',
        );
        $this->fields_list['error_file'] = array(
            'title' => 'Error File',
            'align' => 'text-left',
            'search' => false,
            'callback' => 'viewErrorButton',
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
     * @param $id
     * @return string
     */
    public function viewErrorButton($id)
    {
        if ($id) {
            return '<span class="btn-group-action">
                <a href = "' . $id . '" download><span class="btn btn-danger">
					Download
                </span> </a>
            </span>';
        } else {
            return '<span class="btn-group-action">
                <span class="btn btn-success">
					No Error File
                </span> 
            </span>';
        }
    }

    /**
     * @param $path
     * @return string
     */
    public function viewDownloadButton($path)
    {
        if ($path) {
            return '<span class="btn-group-action">
                <a href = "' . $path . '" download><i class="icon-download"></i></a>
            </span>';
        }
        return $path;
    }

    /**
     * @param null $token
     * @param $id
     * @param null $name
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayUpdateLink($token = null, $id = null, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Update', self::$cache_lang)) {
            self::$cache_lang['Update'] = $this->l('Update', 'Helper');
        }

        $tpl->assign(array(
            'href' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicReport') .
                '&updateelkjopnordic_offerfeed=' . $id,
            'action' => self::$cache_lang['Update'],
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
     * @throws SmartyException
     */
    public function displayDeleteOfferFeedLink($token = null, $id = null, $name = null)
    {
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = 'Delete';
        }

        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&deleteofferfeed=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Delete'],
            'id' => $id,
            'name' => $name
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/feed/delete_offerfeed_row_action.tpl'
        );
    }

    /**
     * @return bool|ObjectModel|void
     */
    public function postProcess()
    {
        if (Tools::getIsset('deleteofferfeed') && Tools::getValue('deleteofferfeed')) {
            $id = Tools::getValue('deleteofferfeed');
            $res = $this->elkjopnordicOfferFeed->deleteOfferFeed($id);
            if (isset($res['success']) && $res['success']) {
                $this->confirmations[] = "Offer Feed " . $id . " deleted successfully";
            } else {
                if (isset($res['message'])) {
                    $this->errors[] = $res['message'];
                }
                $this->errors[] = "Failed to delete Product Feed " . $id;
            }
        }
        if (Tools::getIsset('updateelkjopnordic_offerfeed')) {
            $results = $this->elkjopnordicOfferFeed->updateFeed(Tools::getvalue('updateelkjopnordic_offerfeed'));
            if (isset($results['success']) && $results['success']) {
                $this->confirmations[] = $results['message'];
            } else {
                $this->errors[] = $results['message'];
            }
        }
        parent::postProcess();
    }

    /**
     *
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
    }

    /**
     * @param $import_id
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function updateFeed($import_id)
    {
        if ($import_id) {
            $db = Db::getInstance();
            $CedElkjopnordicProduct = new CedElkjopnordicProduct;
            $feed = $CedElkjopnordicProduct->getFeeds($import_id, 'offers');
           
            $error_report = _PS_MODULE_DIR_ . 'cedelkjopnordic/library/upload/error_report/offers';
            if (!is_dir($error_report)) {
                mkdir($error_report, 0777, true);
            }
            $error_report_file = $error_report . '/' . $import_id . '.csv';
            $shop_id = Context::getContext()->shop->id;
            if (isset($feed['import_id'])) {
                if (isset($feed['has_error_report']) && $feed['has_error_report']) {
                    $error_report = $CedElkjopnordicProduct->getFeedsErrors($import_id, 'offers');
                    file_put_contents($error_report_file, $error_report);
                    $feed['error_file'] = Context::getContext()->shop->getBaseURL(true) .
                        '/modules/cedelkjopnordic/library/upload/error_report/offers/' . $import_id . '.csv';
                }
                $feed['id_shop'] = $shop_id ;
                try {
                    $columns = array(
                        'date_created',
                        'has_error_report',
                        'lines_in_error',
                        'lines_in_pending',
                        'lines_in_success',
                        'import_id',
                        'status',
                        'lines_read',
                        'mode',
                        'offer_deleted',
                        'offer_inserted',
                        'offer_updated',
                        'type',
                        'error_file',
                        'id_shop',
                        'shop_id'

                    );
                    if (isset($feed['import_id']) && $CedElkjopnordicProduct->getPriceFeedById($feed['import_id'])) {
                        $sql = "UPDATE `" . _DB_PREFIX_ . "cedelkjopnordic_price_feed` SET ";
                        foreach ($feed as $key => $value) {
                            if (!in_array($key, $columns)) {
                                continue;
                            }
                            if ($key == 'date_created') {
                                $value = str_replace('T', ' ', $value);
                                $value = str_replace('Z', '', $value);
                            }
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }
                            $sql .= " `" . $key . "`='" . pSQL($value) . "',";
                        }
                        $sql = rtrim($sql, ',');
                        $sql .= " where `import_id`='" . $feed['import_id'] . "'";
                        $db->Execute($sql);
                    } else {
                        $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedelkjopnordic_price_feed` SET ";
                        foreach ($feed as $key => $value) {
                            if (!in_array($key, $columns)) {
                                continue;
                            }
                            if ($key == 'date_created') {
                                $value = str_replace('T', ' ', $value);
                                $value = str_replace('Z', '', $value);
                            }
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }
                            $sql .= " `" . $key . "`='" . pSQL($value) . "',";
                        }
                        $sql = rtrim($sql, ',');
                        $db->Execute($sql);
                    }
                } catch (Exception $e) {
                    return array('success' => false, 'message' => $e->getMessage());
                }
                return array('success' => true, 'message' => 'Updated Feed ' . $feed['import_id']);
            } else {
                return array('success' => false, 'message' => 'No response From Elkjopnordic.');
            }
        } else {
            return array('success' => false, 'message' => 'Feed Id Not Found.');
        }
    }

    /**
     *
     */
    public function initToolbar()
    {
        $this->toolbar_btn['export'] = array('href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token, 'desc' => $this->l('Export'));# code...
    }
}
