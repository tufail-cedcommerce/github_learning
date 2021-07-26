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
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProduct.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProfile.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicCategory.php';

class AdminCedElkjopnordicProductsController extends ModuleAdminController
{
    protected $id_current_category;

    protected $tab_display;

    protected $object;

    protected $product_attributes;
    protected $productHelper;

    protected $position_identifier = 'product_id';

    protected $submitted_tabs;
    protected $id_elkjopnordic_profile;
    public $elkjopnordicProfile;
    public $elkjopnordicCategory;
    public $cedElkjopnordicHelper;
    public $profile_array = array();
    public $elkjopnordic_statuses = array();
    public $product_feed_status_list = array();

    public function __construct()
    {
        $this->productHelper = new CedElkjopnordicProduct();
        $this->elkjopnordicProfile = new CedElkjopnordicProfile();
        $this->elkjopnordicCategory = new CedElkjopnordicCategory();
        $this->cedElkjopnordicHelper = new CedElkjopnordicHelper();
        
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = false;
        $this->list_no_link = true;
        $this->explicitSelect = true;
        $this->_use_found_rows = true;
        $this->addRowAction('upload');
        $this->addRowAction('syncstatus');
         // for multishop comatibility start
        $shop_id = $id_shop = Shop::getContextShopID();
        if($shop_id){
            $shop_ids[] = $shop_id;
        } else {
            $shop_group_id = Shop::getContextShopGroupID();
            if($shop_group_id && !$shop_id){
                $group_shops = Shop::getShops(true,$shop_group_id);
                $group_shop_ids = array_column($group_shops,'id_shop');
                if(!empty($group_shop_ids))
                    $shop_ids[] = $group_shop_ids;
            }
        }
        // for multishop comatibility end
        $this->bulk_actions = array(
            'upload' => array(
                'text' => ('Upload/Update selected'),
                'icon' => 'icon-upload',
            ),
            'updateOffers' => array(
                'text' => ('Update Offers'),
                'icon' => 'icon-refresh',
            ),
            'syncStatus' => array(
                'text' => ('Sync Status'),
                'icon' => 'icon-life-saver',
            ),
            'include' => array(
                'text' => ('Include Item'),
                'icon' => 'icon-life-saver',
            ),
            'exclude' => array(
                'text' => ('Exclude Item'),
                'icon' => 'icon-life-saver',
            ),
            'assign_profile' => array(
                'text' => ('Assign Profile'),
                'icon' => 'icon-upload',
            ),
            'remove_profile' => array(
                'text' => ('Remove Profile'),
                'icon' => 'icon-eraser',
            ),
        );

        $this->elkjopnordic_statuses = array(
            'Uploaded' => 'Uploaded',
            'Not Uploaded' => 'Not Uploaded',
            'Invalid' => 'Invalid',
            'Not Created Yet' => 'Not Created Yet',
            'Live' => 'Live'
        );
        $this->product_feed_status_list = array(

        '1' => 'Include',
            '2' => 'Exclude'

    );
        $dbp = Db::getInstance();
        $sql = "SELECT `id`,`title` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile` WHERE id_shop IN (".implode(',',$shop_ids).")";
        $res = $dbp->executeS($sql);
        if ($res and !empty($res)) {
            foreach ($res as $r) {
                $this->profile_array[$r['id']] = $r['title'];
            }
        }
        
        parent::__construct();
        /* Join categories table */
        if ($id_category = (int)Tools::getValue('productFilter_cl!name')) {
            $this->_category = new Category((int)$id_category);
            $_POST['productFilter_cl!name'] = $this->_category->name[$this->context->language->id];
        } else {
            if ($id_category = (int)Tools::getValue('id_category')) {
                $this->id_current_category = $id_category;
                $this->context->cookie->id_category_products_filter = $id_category;
            } elseif ($id_category = $this->context->cookie->id_category_products_filter) {
                $this->id_current_category = $id_category;
            }
            if ($this->id_current_category) {
                $this->_category = new Category((int)$this->id_current_category);
            } else {
                $this->_category = new Category();
            }
        }
        $this->_join .= '
        LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sav ON (sav.`id_product` = a.`id_product` 
        AND sav.`id_product_attribute` = 0
        ' . StockAvailable::addSqlShopRestriction(null, null, 'sav') . ') ';

        $alias = 'sa';
        $alias_image = 'image_shop';

        $id_shop = Shop::isFeatureActive()
        && Shop::getContext() == Shop::CONTEXT_SHOP ? (int)$this->context->shop->id : 'a.id_shop_default';
        $this->_join .= ' JOIN `' . _DB_PREFIX_ . 'product_shop` sa ON (a.`id_product` = sa.`id_product`
             AND sa.id_shop = ' . $id_shop . ')

                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` b ON (a.`id_product` = b.id_product 
                AND b.id_shop = ' . $id_shop . ' AND b.`id_lang`="' . (int)$this->context->language->id . '")

                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl 
                ON (' . $alias . '.`id_category_default` = cl.`id_category` 
                AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = ' . $id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'shop` shop ON (shop.id_shop = ' . $id_shop . ')
                
                LEFT JOIN `' . _DB_PREFIX_ . 'cedelkjopnordic_profile_products` cbprofile 
                ON (cbprofile.`id_product` = a.`id_product`) AND cbprofile.id_shop_profile = "'.(int)$shop_id.'"
            
                LEFT JOIN `' . _DB_PREFIX_ . 'cedelkjopnordic_products` cbprod 
                ON (cbprod.`id_product` = cbprofile.`id_product`)  AND cbprod.id_shop = "'.(int)$shop_id.'" 
                
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop 
                ON (image_shop.`id_product` = a.`id_product` 
                AND image_shop.`cover` = 1 AND image_shop.id_shop = ' . $id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = image_shop.`id_image`)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_download` pd 
                ON (pd.`id_product` = a.`id_product` 
                AND pd.`active` = 1)';
        $this->_select .= 'shop.`name` AS `shopname`, a.`id_shop_default`, ';

        $this->_select .= 'cbprofile.`id_cedelkjopnordic_profile` AS `id_cedelkjopnordic_profile`, ';
        $this->_select .= 'cbprod.`error_message` AS `error_message`, ';
        $this->_select .= 'cbprod.`elkjopnordic_status` AS `elkjopnordic_status`, ';
        $this->_select .= 'cbprod.`product_feed_status` AS `product_feed_status`, ';

        $this->_select .= $alias_image . '.`id_image` AS `id_image`, a.`id_product` as `id_temp`,
            cl.`name` AS `name_category`, '
            . $alias . '.`price` AS `price_final`, a.`is_virtual`, pd.`nb_downloadable`, 
            sav.`quantity` AS `sav_quantity`, '
            . $alias . '.`active`, IF(sav.`quantity`<=0, 1, 0) AS `badge_danger`';

        $this->_group = 'GROUP BY ' . $alias . '.id_product';
        $this->fields_list = array();
        $this->fields_list['id_product'] = array(
            'title' => ('ID'),
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'type' => 'int'
        );
         $this->fields_list['image'] = array(
            'title' => ('Image'),
            'align' => 'center',
            'image' => 'p',
            'orderby' => false,
            'filter' => false,
            'search' => false
        );
        $this->fields_list['name'] = array(
            'title' => ('Name'),
            'filter_key' => 'b!name',
        );
        $this->fields_list['reference'] = array(
            'title' => ('Sku'),
            'align' => 'text-center',
        );
        $this->fields_list['name_category'] = array(
            'title' => ('Category'),
            'filter_key' => 'cl!name',
        );

        $this->fields_list['price_final'] = array(
            'title' => ('Final price'),
            'type' => 'price',
            'align' => 'text-center',
            'havingFilter' => true,
            'orderby' => false,
            'search' => false
        );
        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->fields_list['sav_quantity'] = array(
                'title' => ('Quantity'),
                'type' => 'int',
                'align' => 'text-center',
                'filter_key' => 'sav!quantity',
                'orderby' => true,
                'badge_danger' => true,
                'hint' => ('This is the quantity available in the current shop/group.'),
            );
        }

        $this->fields_list['active'] = array(
            'title' => ('Status'),
            'active' => 'status',
            'filter_key' => $alias . '!active',
            'align' => 'text-center',
            'type' => 'bool',
            'orderby' => false
        );
		$this->fields_list['id_cedelkjopnordic_profile'] = array(
            'title' => ('Profile'),
            'align' => 'text-center',
            'filter_key' => 'id_cedelkjopnordic_profile',
            'type' => 'select',
            'list' => $this->profile_array,
            'filter_type' => 'int',
            'callback' => 'elkjopnordicProfileFilter'
        );
		$this->fields_list['product_feed_status'] = array(
            'title' => $this->l('In Feed Status'),
            'align' => 'text-left',
            'class' => 'fixed-width-sm',
            'type' => 'select',
            'list' => $this->product_feed_status_list,
            'filter_key' => 'product_feed_status',
            'filter_type' => 'text',
            'callback' => 'elkjopnordicIncludeExclude'
        );
        $this->fields_list['elkjopnordic_status'] = array(
            'title' => $this->l('Elkjopnordic Status'),
            'align' => 'text-left',
            'class' => 'fixed-width-sm',
            'type' => 'select',
            'list' => $this->elkjopnordic_statuses,
            'filter_key' => 'elkjopnordic_status',
            'filter_type' => 'text',
            'callback' => 'elkjopnordicStatus'
        );
        

        $this->fields_list['error_message'] = array(
            'title' => $this->l('Validity'),
            'align' => 'text-left',
            'search' => false,
            'class' => 'fixed-width-sm',
            'callback' => 'validationData'
        );
       
        if ($id_elkjopnordic_profile = Tools::getValue('id_elkjopnordic_profile')) {
            $this->id_elkjopnordic_profile = $id_elkjopnordic_profile;
            $this->context->cookie->id_elkjopnordic_profile = $id_elkjopnordic_profile;
        } else if ($this->context->cookie->id_elkjopnordic_profile) {
            $this->id_elkjopnordic_profile = $this->context->cookie->id_elkjopnordic_profile;
        }
        
        if (Tools::isSubmit('submitElkjopnordicProductSave')) {
            $this->saveProduct();
        }

        if (Tools::getIsset('updated') && Tools::getValue('updated')) {
            $this->confirmations[] = "Product attributes updated successfully";
        }
        // Remove Product Category
        if (Tools::getIsset('productRemoveProfileSuccess') && Tools::getValue('productRemoveProfileSuccess')) {
            $this->confirmations[] = "Profile Removed Successfully";
        }

        // Assign Product Category
        if (Tools::getIsset('productAssignProfileSuccess') && Tools::getValue('productAssignProfileSuccess')) {
            $this->confirmations[] = "Profile Assinged Successfully";
        }

        // Category not selected for assign product category
        if (Tools::getIsset('productAssignProfileError') && Tools::getValue('productAssignProfileError')) {
            $this->errors[] = "Please select Profile";
        }
    }
    public function initContent()
    {
        $page = (int) Tools::getValue('page');
       
        if ($this->id_elkjopnordic_profile) {
            self::$currentIndex .= '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
        }
        parent::initContent();
    }
    public function processBulkAssignProfile()
    {
        $page = (int) Tools::getValue('page');
        if(!$page){
         $page = (int) Tools::getValue('submitFilter'.$this->table);
        }
        
        $link = new LinkCore();
        $ids = $this->boxes;
       
        $vidazlProfile = (int)$this->id_elkjopnordic_profile;
        if($vidazlProfile) {
            $cedelkjopnordicProduct = new CedElkjopnordicProduct();
             
            $result = $cedelkjopnordicProduct->assignProfile($ids,$vidazlProfile);
            if($result) {
                $this->confirmations[] = "Profile Assinged Successfully";
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&productAssignProfileSuccess=1' .'&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
                Tools::redirectAdmin($controller_link);
                
            }
        } else {
        $this->errors[] = "Please select Vidaxl Profile";
            $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&productAssignProfileError=1=' . '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
            Tools::redirectAdmin($controller_link);
            
        }
        $this->context->cookie->id_elkjopnordic_profile = '';
    }

    public function processBulkRemoveProfile()
    {
        $page = (int) Tools::getValue('page');
        if(!$page){
         $page = (int) Tools::getValue('submitFilter'.$this->table);
        }
        $link = new LinkCore();
        $ids = $this->boxes;
        $vidazlProfile = (int)$this->id_elkjopnordic_profile;
        if(count($ids)) {
            $cedelkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $cedelkjopnordicProduct->removeProfile($ids,$vidazlProfile);
            if($result) {
             $this->confirmations[] = "Profile Removed Successfully";
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&productRemoveProfileSuccess=1=' . '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
                Tools::redirectAdmin($controller_link);
               
            }
        } else {
         $this->errors[] = "Please select Product(s)";
            $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&productSelectError=1=' . '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
            Tools::redirectAdmin($controller_link);
           
        }
        $this->context->cookie->id_elkjopnordic_profile = '';
    }
    /**
     * @param array $product_ids
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processBulkStatusSync($product_ids = array())
    {
        if (is_array($product_ids) && count($product_ids)) {
            $cedelkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $cedelkjopnordicProduct->syncStatus($product_ids);
            return $result;
        }
    }
    public function processBulkInclude()
    {
        $product_ids = Tools::getValue('productBox');
       $page = (int) Tools::getValue('page'); $link = new LinkCore();
        if (is_array($product_ids) && !empty($product_ids)) {
            $cedelkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $cedelkjopnordicProduct->makeInclude($product_ids);
            $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
            Tools::redirectAdmin($controller_link);
        }
    }
    public function processBulkExclude()
    {$page = (int) Tools::getValue('page'); $link = new LinkCore();
        $product_ids = Tools::getValue('productBox');
        if (is_array($product_ids) && !empty($product_ids)) {
            $cedelkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $cedelkjopnordicProduct->makeExclude($product_ids);
            $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts'). '&id_elkjopnordic_profile='.$this->id_elkjopnordic_profile.($page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '') ;
            Tools::redirectAdmin($controller_link);
        }
    }
    public function processBulksyncStatus()
    {
        $db = Db::getInstance();
        try {
            $db->delete(
                'cedelkjopnordic_products_chunk'
            );
            $product_id_array = array();
            if (is_array($this->boxes) && !empty($this->boxes)) {
                $product_id_array = $this->boxes;
            }
            if (count($product_id_array) > 10) {
                $db->insert(
                    'cedelkjopnordic_products_chunk',
                    array(
                        'key' => 'sync_status_chunk',
                        'values' => pSQL(json_encode($product_id_array))
                    )
                );
                $link = new Link();
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicBulk') .
                    '&bulk_status_sync=redirected&redirected=1';
                Tools::redirectAdmin($controller_link);
            } elseif (count($product_id_array)) {
                $result = $this->productHelper->syncStatus($product_id_array);
                if (isset($result['success'])) {
                    foreach ($result['success'] as $res) {
                        $this->confirmations[] = $res . '<br>';
                    }
                }
                if (isset($result['error'])) {
                    foreach ($result['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            } else {
                $this->errors[] = 'No product selected';
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
/**
     * @param null $token
     * @param null $id
     * @return string
     * @throws SmartyException
     */
    public function displaySyncStatusLink($token = null, $id = null)
    {
        if (!array_key_exists('Sync', self::$cache_lang)) {
            self::$cache_lang['Sync'] = 'Sync Status';
        }

        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&syncproductstatus=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Sync'],
            'id' => $id
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/sync_product_row_action.tpl'
        );
    }
    /**
     * @param $data
     * @return mixed
     */
    public function elkjopnordicStatus($data)
    {
        if (isset($this->elkjopnordic_statuses[$data])) {
            return $this->elkjopnordic_statuses[$data];
        }
    }
    public function elkjopnordicIncludeExclude($data)
    {
        if (isset($this->product_feed_status_list[$data])) {
            return $this->product_feed_status_list[$data];
        }
    }
    public function elkjopnordicProfileFilter($data)
    {
 
        if (isset($this->profile_array[$data])) {
            return $this->profile_array[$data];
        }
    }
    

    /**
     * @param $data
     * @param $rowData
     * @return string
     * @throws SmartyException
     */
    public function validationData($data, $rowData)
    {
        $productName = isset($rowData['name']) ? $rowData['name'] : '';
        $this->context->smarty->assign(
            array(
                'validationData' => json_decode($data, true),
                'validationJson' => $data,
                'productName' => str_replace("'", "", $productName)
            )
        );
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/validation.tpl'
        );
    }

    /**
     * @param null $token
     * @param null $id
     * @param null $name
     * @return string
     * @throws SmartyException
     */
    public function displayUploadLink($token = null, $id = null, $name = null)
    {
        if ($name) {
            if (!array_key_exists('Upload', self::$cache_lang)) {
                self::$cache_lang['Upload'] = 'Upload';
            }
        } else {
            if (!array_key_exists('Upload', self::$cache_lang)) {
                self::$cache_lang['Upload'] = 'Upload';
            }
        }
        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&uploadproduct=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Upload'],
            'id' => $id
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/upload_row_action.tpl'
        );
    }

    /**
     * @param null $token
     * @param null $id
     * @return string
     * @throws SmartyException
     */
    public function displayUpdateOfferLink($token = null, $id = null)
    {
        if (!array_key_exists('Update', self::$cache_lang)) {
            self::$cache_lang['Update'] = 'Update Offer';
        }

        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&updateoffer=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Update'],
            'id' => $id
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/update_offer_row_action.tpl'
        );
    }

    /**
     * @param null $token
     * @param null $id
     * @return string
     * @throws SmartyException
     */
    public function displayEditProductLink($token = null, $id = null)
    {
        if (!array_key_exists('Edit', self::$cache_lang)) {
            self::$cache_lang['Edit'] = 'Edit';
        }

        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&editproduct=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Edit'],
            'id' => $id
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/edit_product_row_action.tpl'
        );
    }

    /**
     * @return bool|ObjectModel
     */
    public function postProcess()
    {
        try {
            if (Tools::getIsset('uploadproduct') && Tools::getValue('uploadproduct')) {
                $productId = Tools::getValue('uploadproduct');
                $uploadRes = $this->processBulkQtyUpload(array($productId));
                if (isset($uploadRes['success'])) {
                    foreach ($uploadRes['success'] as $res) {
                        $this->confirmations[] = $res;
                    }
                }
                if (isset($uploadRes['error'])) {
                    foreach ($uploadRes['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            }

            if (Tools::getIsset('updateoffer') && Tools::getValue('updateoffer')) {
                $productId = Tools::getValue('updateoffer');
                $uploadRes = $this->processBulkOfferUpdate(array($productId));
                if (isset($uploadRes['success'])) {
                    foreach ($uploadRes['success'] as $res) {
                        $this->confirmations[] = $res;
                    }
                }
                if (isset($uploadRes['error'])) {
                    foreach ($uploadRes['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            }
            if (Tools::getIsset('syncproductstatus') && Tools::getValue('syncproductstatus')) {
                $productId = Tools::getValue('syncproductstatus');
                $uploadRes = $this->processBulkStatusSync(array($productId));
                if (isset($uploadRes['success'])) {
                    foreach ($uploadRes['success'] as $res) {
                        $this->confirmations[] = $res;
                    }
                }
                if (isset($uploadRes['error'])) {
                    foreach ($uploadRes['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return parent::postProcess(); // TODO: Change the autogenerated stub
    }

    /**
     * @param array $product_ids
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkQtyUpload($product_ids = array())
    {
        if (is_array($product_ids) && count($product_ids)) {
            $elkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $elkjopnordicProduct->uploadProducts($product_ids);
            return $result;
        }
    }

    /**
     * @param array $product_ids
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function processBulkOfferUpdate($product_ids = array())
    {
        if (is_array($product_ids) && count($product_ids)) {
            $elkjopnordicProduct = new CedElkjopnordicProduct();
            $result = $elkjopnordicProduct->updateOffers($product_ids);
            return $result;
        }
    }

    /**
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('updateOffer');
        $this->addRowAction('edit');
        $parent = $this->context->smarty->fetch(_PS_MODULE_DIR_ .
            'cedelkjopnordic/views/templates/admin/product/product_list.tpl');
          // for multishop comatibility start
        $shop_id = Shop::getContextShopID();
        if($shop_id){
            $shop_ids[] = $shop_id;
        } else {
            $shop_group_id = Shop::getContextShopGroupID();
            if($shop_group_id && !$shop_id){
                $group_shops = Shop::getShops(true,$shop_group_id);
                $group_shop_ids = array_column($group_shops,'id_shop');
                if(!empty($group_shop_ids))
                    $shop_ids[] = $group_shop_ids;
            }
        }
        // for multishop comatibility end    
        $vprofiles = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE `status`='1' AND id_shop IN (".implode(',',$shop_ids).")");
        if(Tools::getValue('submitFilterproduct')){
            $reurl = $this->context->link->getAdminLink('AdminCedElkjopnordicProducts').'&submitFilterproduct='.Tools::getValue('submitFilterproduct').'#product';
        } else {
            $reurl = $this->context->link->getAdminLink('AdminCedElkjopnordicProducts');
        }
        $this->context->smarty->assign(array(
            'controllerUrl' => $reurl,
            'token' => $this->token,
            'vprofiles' => $vprofiles,
            'idCurrentProfile' => Tools::getValue('id_elkjopnordic_profile')
        ));
        $r = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/list/profile_selector.tpl');
        return $r.$parent . parent::renderList();
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['upload_all'] = array(
                'href' => $this->context->link->getAdminLink('AdminCedElkjopnordicBulk') . '&bulk_upload=all&all=1',
                'desc' => $this->l('Upload All', null, null, false),
                'icon' => 'process-icon-upload'
            );
            $this->page_header_toolbar_btn['update_offers'] = array(
                'href' => $this->context->link->getAdminLink('AdminCedElkjopnordicBulk') . '&bulk_update_offers=all&all=1',
                'desc' => $this->l('Update Offers', null, null, false),
                'icon' => 'process-icon-download'
            );
        }
        parent::initPageHeaderToolbar();
    }

    /**
     *
     */
    public function initToolbar()
    {
        $this->toolbar_btn['export'] = array('href' => self::$currentIndex . '&export' . $this->table . '&token=' .
            $this->token, 'desc' => $this->l('Export'));
        if ($this->display == 'edit' || $this->display == 'add') {
            $this->toolbar_btn['save'] = array(
                'short' => 'Save',
                'href' => '#',
                'desc' => $this->l('Save'),
            );
        }

        $this->context->smarty->assign('toolbar_scroll', 1);
        $this->context->smarty->assign('show_toolbar', 1);
        $this->context->smarty->assign('toolbar_btn', $this->toolbar_btn);
    }

    /**
     *renderForm contains all necessary initialization needed for all tabs
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        try {
            $productId = (int)Tools::getValue('id_product');
            $attr_list = array();
            if ($productId) {
                $attr_list_resp = $this->getAttributesForProduct($productId);
                $productAttributes = $this->productHelper->getProductData($productId);
                if (isset($attr_list_resp['success']) && $attr_list_resp['success']) {
                    $attr_list = isset($attr_list_resp['message']) ? json_decode($attr_list_resp['message'], true)
                        : array();
                }
                $imgUrl = Context::getContext()->shop->getBaseURL(true) . 'modules/cedelkjopnordic/views/img/loading.gif';
                $this->context->smarty->assign(array(
                    'imgUrl' => $imgUrl,
                    'currentToken' => Tools::getAdminTokenLite('AdminCedElkjopnordicProducts'),
                    'controllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicProducts'),
                    'profileControllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicProfile'),
                    'elkjopnordicAttributeList' => $attr_list,
                    'elkjopnordicVariantAttributeList' => array(),
                    'elkjopnordicVariantAttributes' => $this->elkjopnordicProfile->getVariantAttributes(),
                    'skip_attributes' => $this->elkjopnordicCategory->getSkipAttributes(),
                    'elkjopnordicDefaultAttributeList' => $this->elkjopnordicCategory->getDefaultAttributes(),
                    'productAttributes' => $productAttributes
                ));
                $editProductTemplate = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/product/form/edit_product.tpl'
                );

                return $editProductTemplate;
            } else {
                die('Product Id not found!');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function saveProduct()
    {
        $db = Db::getInstance();
        $productData = Tools::getValue('productAttributes');
        $productData = json_encode($productData);
        $product_id = Tools::getValue('id_product');
        $res = $db->update(
            'cedelkjopnordic_products',
            array(
                'data' => pSQL($productData),
            ),
            'id_product=' . (int)$product_id
        );
        if ($res) {
            $link = new LinkCore();
            $controller_link = $link->getAdminLink('AdminCedElkjopnordicProducts') . '&updated=1';
            Tools::redirectAdmin($controller_link);
            $this->confirmations[] = "Product attributes updated successfully";
        }
    }

    /**
     * @param $productId
     * @return array
     */
    public function getAttributesForProduct($productId)
    {
        $params = array();
        try {
            $db = db::getInstance();
            $sql = 'Select `id_cedelkjopnordic_profile` from `' . _DB_PREFIX_ .
                'cedelkjopnordic_profile_products` where `id_product`="' .(int)$productId . '"';
            $res = $db->executeS($sql);
            if ($res && isset($res[0]['id_cedelkjopnordic_profile'])) {
                $elkjopnordicProfileId = (int)$res[0]['id_cedelkjopnordic_profile'];
                $elkjopnordicCategoryResp = $this->elkjopnordicProfile->getProfileCategoryById($elkjopnordicProfileId);
                if (isset($elkjopnordicCategoryResp['success']) && $elkjopnordicCategoryResp['success']) {
                    $elkjopnordicCategory = isset($elkjopnordicCategoryResp['message']) ? $elkjopnordicCategoryResp['message'] : '';
                    $params['hierarchy'] = $elkjopnordicCategory;
                    $elkjopnordic_attributes = $this->elkjopnordicCategory->getElkjopnordicAttributes($params);

                    if (isset($elkjopnordic_attributes['success']) && !$elkjopnordic_attributes['success']) {
                        return array('success' => false, 'message' => isset($elkjopnordic_attributes['message']) ?
                            $elkjopnordic_attributes['message'] : '');
                    } else {
                        return array('success' => true, 'message' => json_encode($elkjopnordic_attributes));
                    }
                } else {
                    return array('success' => false, 'message' => isset($elkjopnordicCategoryResp['message']) ?
                        $elkjopnordicCategoryResp['message'] : '');
                }
            } else {
                return array('success' => false, 'message' => 'Profile Not found.');
            }
        } catch (\Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     *
     */
    protected function processBulkUpload()
    {
        $db = Db::getInstance();
        try {
            $db->delete(
                'cedelkjopnordic_products_chunk'
            );
            $product_id_array = array();
            if (is_array($this->boxes) && !empty($this->boxes)) {
                $product_id_array = $this->boxes;
            }
            if (count($product_id_array) > 10) {
                $db->insert(
                    'cedelkjopnordic_products_chunk',
                    array(
                        'key' => 'upload_chunk',
                        'values' => pSQL(json_encode($product_id_array))
                    )
                );
                $link = new LinkCore();
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicBulk') .
                    '&bulk_upload=redirected&redirected=1';
                Tools::redirectAdmin($controller_link);
            } elseif (count($product_id_array)) {
                $result = $this->productHelper->uploadProducts($product_id_array);
                if (isset($result['success'])) {
                    foreach ($result['success'] as $res) {
                        $this->confirmations[] = $res . '<br>';
                    }
                }
                if (isset($result['error'])) {
                    foreach ($result['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            } else {
                $this->errors[] = 'No product selected';
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     *
     */
    public function processBulkupdateOffers()
    {
        $db = Db::getInstance();
        try {
            $db->delete(
                'cedelkjopnordic_products_chunk'
            );
            $product_id_array = array();
            if (is_array($this->boxes) && !empty($this->boxes)) {
                $product_id_array = $this->boxes;
            }
            if (count($product_id_array) > 10) {
                $db->insert(
                    'cedelkjopnordic_products_chunk',
                    array(
                        'key' => 'upload_chunk',
                        'values' => pSQL(json_encode($product_id_array))
                    )
                );
                $link = new LinkCore();
                $controller_link = $link->getAdminLink('AdminCedElkjopnordicBulk') .
                    '&bulk_update_offers=redirected&redirected=1';
                Tools::redirectAdmin($controller_link);
            } elseif (count($product_id_array)) {
                $result = $this->productHelper->updateOffers($product_id_array);
                if (isset($result['success'])) {
                    foreach ($result['success'] as $res) {
                        $this->confirmations[] = $res . '<br>';
                    }
                }
                if (isset($result['error'])) {
                    foreach ($result['error'] as $res) {
                        $this->errors[] = $res;
                    }
                }
            } else {
                $this->errors[] = 'No product selected';
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     *
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
        $this->addJquery();
        $this->addJqueryPlugin('autocomplete');
        $this->addJS(_PS_MODULE_DIR_ . 'cedelkjopnordic/views/js/admin/product/product_list.js');
    }
}
