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

include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProfile.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProduct.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';
class AdminCedElkjopnordicBulkController extends ModuleAdminController
{
    public $productHelper;

    /**
     * AdminCedElkjopnordicBulkController constructor.
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->productHelper = new CedElkjopnordicProduct();
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $link = new LinkCore();
            $this->page_header_toolbar_btn['backtolist'] = array(
                'href' => $link->getAdminLink('AdminCedElkjopnordicProducts'),
                'desc' => $this->l('Back To Product List', null, null, false),
                'icon' => 'process-icon-back'
            );
        }
        parent::initPageHeaderToolbar();
    }

    /**
     *
     */
    public function initContent()
    {
        try {
            $db = Db::getInstance();
            $content = null;
            $profileProductsIds = array();
            $id_shop = (int)Context::getContext()->shop->id;

            $sql = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products` WHERE `id_shop_profile`= '".$id_shop."'";
            $result = $db->executeS($sql);
           
            if (isset($result) && is_array($result) && !empty($result)) {
                foreach ($result as $res) {
                    $profileProductsIds[] = $res['id_product'];
                }
            }
            parent::initContent();
            $link = new LinkCore();
            $controllerUrl = $link->getAdminLink('AdminCedElkjopnordicBulk');
            $token = $this->token;
            $this->context->smarty->assign(array('controllerUrl' => $controllerUrl));
            $this->context->smarty->assign(array('token' => $token));

            if (Tools::getIsset('bulk_upload') && Tools::getValue('bulk_upload')) {
                if (Tools::getValue('bulk_upload') == 'redirected') {
                    $productIds = $this->getProductsChunkIds('upload_chunk');
                } else {
                    $productIds = $profileProductsIds;
                }
                $this->context->smarty->assign(array(
                    'upload_array' => addslashes(json_encode($productIds))
                ));
                $content = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/form/bulk_upload_form.tpl'
                );
            }

            if (Tools::getIsset('bulk_update_offers') && Tools::getValue('bulk_update_offers')) {
                if (Tools::getValue('bulk_update_offers') == 'redirected') {
                    $productIds = $this->getProductsChunkIds('upload_chunk');
                } else {
                    $productIds = $profileProductsIds;
                }
                $this->context->smarty->assign(array(
                    'update_array' => addslashes(json_encode($productIds))
                ));
                $content = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/form/bulk_update_offer_form.tpl'
                );
            }
            
            if (Tools::getIsset('bulk_status_sync') && Tools::getValue('bulk_status_sync')) {
                if (Tools::getValue('bulk_status_sync') == 'redirected') {
                    $productIds = $this->getProductsChunkIds('sync_status_chunk');
                } else {
                    $productIds = $profileProductsIds;
                }
                $this->context->smarty->assign(array(
                    'update_array' => addslashes(json_encode($productIds))
                ));
                $content = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/form/bulk_sync_status_form.tpl'
                );
            }

            $this->context->smarty->assign(array(
                'content' => $this->content . $content
            ));
            $db->delete(
                'cedelkjopnordic_products_chunk'
            );
        } catch (\Exception $e) {
            $db->delete(
                'cedelkjopnordic_products_chunk'
            );
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessBulkUpload()
    {
        /*$ids = Tools::getValue('selected');
        $result = $this->productHelper->uploadProducts($ids);
        die(json_encode($result));*/
        $CedElkjopnordicHelper = new CedElkjopnordicHelper();
        if (Tools::getIsset('uplodfeed') && Tools::getIsset('batch_identifier') && Tools::getValue('batch_identifier')) {
            $upload_alldir = _PS_MODULE_DIR_ . 'cedelkjopnordic/product_feed/request/all_products/' . trim(Tools::getValue('batch_identifier'));
            $scanned_directory = array_diff(scandir($upload_alldir), array('..', '.'));
            $feed_path = 'products/imports';
            $uploaded_feeds = array();
            if ($scanned_directory) {
                foreach ($scanned_directory as $key => $value) {
                    $file_path = $upload_alldir . '/' . $value;
                    $feed_url = 'products/imports';

                    $response = $CedElkjopnordicHelper->WPostRequest($feed_url, array('file' => $file_path, 'import_mode' => 'NORMAL'));
                   
                    $file_name = basename($file_path);
                    $feed_final_path = _PS_BASE_URL_ . __PS_BASE_URI__ .
                        "modules/cedelkjopnordic/product_feed/request/all_products/" . trim(Tools::getValue('batch_identifier'))  .'/'.$file_name;
                    if (isset($response['success']) && $response['success']) {
                        $responsed = $response['response'];
                        if (json_decode($responsed, true)) {
                            $responsed = json_decode($responsed, true);
                        }
                        $import_id = isset($responsed['import_id'])?$responsed['import_id']:'';
                        $this->productHelper->syncFeed($import_id, 'products', $feed_final_path);
                        $uploaded_feeds[] = $import_id;
                    }

                }
                die(json_encode(array('success' => true, 'message' => $uploaded_feeds)));
            }
        }

        if (Tools::getIsset('selected')) {
            if (is_array(Tools::getValue('selected')) && !empty(Tools::getValue('selected'))) {
                
                $result = $this->productHelper->uploadAllProducts(Tools::getValue('selected'), (int)Tools::getValue('batch_identifier'));
                die(json_encode($result));
            }
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function ajaxProcessBulkUpdateOffers()
    {
        /*$ids = Tools::getValue('selected');
        $result = $this->productHelper->updateOffers($ids);
        die(json_encode($result));*/
        $CedElkjopnordicHelper = new CedElkjopnordicHelper();
        if (Tools::getIsset('uplodfeed') && Tools::getIsset('batch_identifier') && Tools::getValue('batch_identifier')) {
            $upload_alldir = _PS_MODULE_DIR_ . 'cedelkjopnordic/product_feed/request/all_offers/' . trim(Tools::getValue('batch_identifier'));
            $scanned_directory = array_diff(scandir($upload_alldir), array('..', '.'));
            $feed_path = 'offers/imports';
            $uploaded_feeds = array();
            if ($scanned_directory) {
                foreach ($scanned_directory as $key => $value) {
                    $file_path = $upload_alldir . '/' . $value;
                    $feed_url = 'offers/imports';

                    $response = $CedElkjopnordicHelper->WPostRequest($feed_url, array('file' => $file_path, 'import_mode' => 'NORMAL'));
                   
                    $file_name = basename($file_path);
                    $feed_final_path = _PS_BASE_URL_ . __PS_BASE_URI__ .
                        "modules/cedelkjopnordic/product_feed/request/all_offers/" . trim(Tools::getValue('batch_identifier'))  .'/'. $file_name;
                    if (isset($response['success']) && $response['success']) {
                        $responsed = $response['response'];
                        if (json_decode($responsed, true)) {
                            $responsed = json_decode($responsed, true);
                        }
                        $import_id = isset($responsed['import_id'])?$responsed['import_id']:'';
                        $this->productHelper->syncFeed($import_id, 'offers', $feed_final_path);
                        $uploaded_feeds[] = $import_id;
                    }

                }
                die(json_encode(array('success' => true, 'message' => $uploaded_feeds)));
            }
        }

        if (Tools::getIsset('selected')) {
            if (is_array(Tools::getValue('selected')) && !empty(Tools::getValue('selected'))) {
                
                $result = $this->productHelper->uploadAllOffers(Tools::getValue('selected'), (int)Tools::getValue('batch_identifier'));
                die(json_encode($result));
            }
        }
    }
/**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessBulkStatusSync()
    {
        $ids = Tools::getValue('selected');
        $result = $this->productHelper->syncStatus($ids);
        die(json_encode($result));
    }
    /**
     * @param $chunkType
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getProductsChunkIds($chunkType)
    {
        $ids = array();
        $db = Db::getInstance();
        $sql = "SELECT `values` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products_chunk` 
                    WHERE `key`='" . pSQL($chunkType) . "'";
        $res = $db->executeS($sql);
        if (isset($res[0]['values'])) {
            if (!empty($res[0]['values'])
                && json_decode($res[0]['values'], true)) {
                $ids = json_decode($res[0]['values'], true);
            }
        }
        return $ids;
    }
}
