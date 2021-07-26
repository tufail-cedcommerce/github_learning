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

require_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProfile.php';
require_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicCategory.php';

class AdminCedElkjopnordicProfileController extends ModuleAdminController
{
    public $elkjopnordicProfile;
    public $elkjopnordicHelper;
    public $elkjopnordicCategory;

    public function __construct()
    {
        $this->elkjopnordicHelper = new CedElkjopnordicHelper();

        $this->id_lang = Context::getContext()->language->id;
        $this->bootstrap = true;
        $this->table = 'cedelkjopnordic_profile';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->className = 'CedElkjopnordicProfile';
        $this->addRowAction('edit');
        $this->addRowAction('deleteProfile');

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
        $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'cedelkjopnordic_profile_products 
                        cpp ON (a.id = cpp.id_cedelkjopnordic_profile)';
        $this->_select = 'COUNT(cpp.`id_product`) AS `product_count`';
        $this->_group = 'GROUP BY a.id';
        $this->fields_list = array(
            'id' => array(
                'title' => 'Profile ID',
                'type' => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'align' => 'text-center',
            ),
            'status' => array(
                'title' => 'Status',
                'type' => 'bool',
                'align' => 'text-center',
                'class' => 'fixed-width-sm',
                'callback' => 'profileStatus',
                'orderby' => false
            ),
            'product_count' => array(
                'title' => 'Product Count',
                'type' => 'int',
                'align' => 'text-center',
            ),
            'elkjopnordic_categories' => array(
                'title' => 'Sync',
                'type' => 'text',
                'align' => 'text-center',
                'callback' => 'profileSync',
                'orderby' => false
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
        $this->elkjopnordicProfile = new CedElkjopnordicProfile();
        $this->elkjopnordicCategory = new CedElkjopnordicCategory();
        if (Tools::isSubmit('submitElkjopnordicProfileSave')) {
            $this->saveProfile();
        }
        if (Tools::getIsset('created') && Tools::getValue('created')) {
            $this->confirmations[] = "Profile created successfully";
        }
    }

    /**
     * @param $value
     * @return string
     * @throws SmartyException
     */
    public function profileStatus($value)
    {
        $this->context->smarty->assign(array('profile_status' => (string)$value));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/profile_status.tpl'
        );
    }
    /**
     * @param $value
     * @return string
     * @throws SmartyException
     */
    public function profileSync($value, $row_data)
    {       
        $elkjopnordic_category = @json_decode($value, true);
        if(!empty($elkjopnordic_category)){
      
            $count = count($elkjopnordic_category);
            $elkjopnordic_category = $elkjopnordic_category['level_' . $count];  
            $this->context->smarty->assign(array(
            'value' => (string)$value,
            'profile_id' => $row_data['id'],
            'shop_id' => Context::getContext()->shop->id,
            'category_id' => $elkjopnordic_category)
            );
            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/profileSync.tpl'
            );
        }
        return $value;
    }

     public function ajaxProcessgetAttributeSynced()
    {
        $profileId = Tools::getValue('profile_id');
        $category_id = Tools::getValue('category_id');
        $shop_id = Tools::getValue('shop_id');
        $params = Tools::getAllValues();
        $response = $this->elkjopnordicHelper->WGetRequest('products/attributes', array('hierarchy' => $category_id), 'json');
        if (!empty($response)) {
            if (isset($response['success']) && $response['success']) {
                    $response_attributes = $response['response'];
                    $response_attributes = json_decode($response_attributes, true);
                    $count = 0;
                    if(!empty($response_attributes)){
                    
                        $db = Db::getInstance();    
                        foreach($response_attributes as $response_attribute) {
                            $id = $db->getValue("SELECT id FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE AND category_id ='".$category_id."' AND attribute_code = '".pSQL($response_attribute['code'])."'");
                            if(!$id){
                                $db->execute("INSERT INTO `"._DB_PREFIX_."cedelkjopnordic_attributes` SET category_id ='".$category_id."' , attribute_code = '".pSQL($response_attribute['code'])."', attribute_label = '".pSQL($response_attribute['label'])."', default_value = '".pSQL($response_attribute['default_value'])."', required = '".pSQL($response_attribute['required'])."', is_variant = '".pSQL($response_attribute['is_variant'])."', attribute_type = '".pSQL($response_attribute['type'])."', values_list = '".pSQL($response_attribute['values_list'])."',values = '".pSQL($response_attribute['values'])."'");
                                $count++;
                            }
                        }
                    }
            }
            die(json_encode(array(
                'success' => true,
                'message' => $count.' New attributes added to profile.'
            )));
        } else {
            die(json_encode(array(
                'success' => false,
                'message' => !empty($message) ? $message : 'Please Select Correct Leaf Category'
            )));
        }
    }
    
    public function ajaxProcessgetOptionSynced()
    {
        $profileId = Tools::getValue('profile_id');
        $category_id = Tools::getValue('category_id');
        $shop_id = Tools::getValue('shop_id');
        $params = Tools::getAllValues();
        
        if ($profileId && $category_id) {
         $db = Db::getInstance();    
             $attributes = $db->executeS("SELECT *  FROM `" . _DB_PREFIX_ . "cedelkjopnordic_attributes` WHERE `attribute_type` LIKE '%LIST%' AND category_id ='".$category_id."' ORDER BY `category_id`  ASC");
            $updated_attributes_c = array(); 
            foreach ($attributes as $attribute) {
                $list_of_values = array();
                $response = $this->elkjopnordicHelper->WGetRequest('values_lists', array('code' => trim($attribute['values_list'])), 'json');
               
                if (isset($response['success']) && $response['success']) {
                    
                    $response_attribute_options = $response['response'];
                    $response_attribute_options = @json_decode($response_attribute_options, true);
                    foreach ($response_attribute_options as $response_attribute_option) {
                        foreach ($response_attribute_option as $response_attr) {
                            if ($response_attr && isset($response_attr['code']) && ($response_attr['code'] == $attribute['attribute_code']) && isset($response_attr['values']) && !empty($response_attr['values'])) {
                                $list_of_values = $response_attr['values'];
                            }
                        }
                    }

                    $attribute_option_db_chunk = array_chunk($list_of_values, 1000);
                    $db->execute("DELETE FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE category_id = '".$category_id."' AND attribute_code = '".pSQL(trim($attribute['values_list']))."'");
                    foreach ($attribute_option_db_chunk as $options) {
                            $sql = "INSERT INTO `"._DB_PREFIX_."cedelkjopnordic_attribute_options` (`id`, `category_id`, `attribute_code`, `values_list`, `default_value`, `value_code`, `value_label`) VALUES ";
                            foreach ($options as $data) {
                                $sql .= "(NULL, '".$attribute['category_id']."', '".pSQL(trim($attribute['attribute_code']))."', '".pSQL($attribute['attribute_code'])."', '', '".pSQL($data['code'])."', '".pSQL($data['label'])."'), ";
                            }
                            $sql = rtrim( $sql,', ');
                            $sql = rtrim( $sql,',');
                            
                            $updated_attributes_c[trim($attribute['values_list'])] = $db->execute($sql);
                    }

                }


            }     
            die(json_encode(array(
                'success' => true,
                'message' => implode(',', array_keys($updated_attributes_c)).' options updated successfully.'
            )));
        } else {
            die(json_encode(array(
                'success' => false,
                'message' => !empty($message) ? $message : 'Please Select Correct Leaf Category'
            )));
        }
    }
    /**
     * @return bool|ObjectModel|void
     */
    public function postProcess()
    {
        if (Tools::getIsset('deleteprofile') && Tools::getValue('deleteprofile')) {
            $id = Tools::getValue('deleteprofile');
            $res = $this->elkjopnordicProfile->deleteProfile($id);
            if ($res) {
                $this->confirmations[] = "Profile " . $id . " deleted successfully";
            } else {
                $this->errors[] = "Failed to delete Profile " . $id;
            }
        }
        parent::postProcess();
    }

    /**
     *
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_profile'] = array(
                'href' => self::$currentIndex . '&addcedelkjopnordic_profile&token=' . $this->token,
                'desc' => $this->l('Add New Profile', null, null, false),
                'icon' => 'process-icon-new'
            );
        } elseif ($this->display == 'edit' || $this->display == 'add') {
            $this->page_header_toolbar_btn['backtolist'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back To List', null, null, false),
                'icon' => 'process-icon-back'
            );
        }
        parent::initPageHeaderToolbar();
    }

    /**
     * @param null $token
     * @param null $id
     * @return string
     * @throws SmartyException
     */
    public function displayDeleteProfileLink($token = null, $id = null)
    {
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = 'Delete';
        }

        $this->context->smarty->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id
                . '&deleteprofile=' . $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Delete'],
            'id' => $id
        ));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/deleteprofile_row_action.tpl'
        );
    }

    /**
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderList()
    {
        if (Tools::getIsset('action') && (Tools::getValue('action') == 'getAttributeOptions')) {
            $response = $this->ajaxProcessGetAttributeOptions();
            die($response);
        }
        $content = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/list_actions.tpl'
        );
        return $content.parent::renderList();
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
        $elkjopnordicProfile = new CedElkjopnordicProfile();

        // $variantAttributeMapping = array();
        $profileInfo = array();
        $profileStoreCategories = array();
        $profileAttributes = array();
        $profileAdditionalInfo = array();

        $storeFeatures = $this->elkjopnordicHelper->getFeatures();
        $storeAttributes = $this->elkjopnordicHelper->getAttributes();
        $storeDefaultAttributes = $this->elkjopnordicHelper->getSystemAttributes();

        $manufacturer = new Manufacturer();
        $manufacturer_list = $manufacturer::getLiteManufacturersList();
        $imgUrl = Context::getContext()->shop->getBaseURL(true) . 'modules/cedelkjopnordic/views/img/loading.gif';

        $idProfile = Tools::getValue('id');

        if (!empty($idProfile)) {
            $profileData = $elkjopnordicProfile->getProfileDataById($idProfile);
            $profileInfo = $profileData['profileInfo'];
            $profileStoreCategories = $profileData['profileStoreCategories'];
            $profileAttributes = $profileData['profileAttributes'];
            $profileAdditionalInfo = $profileData['profileAdditionalInfo'];
        }

        $this->context->smarty->assign(array('profileId' => $idProfile));
        $this->context->smarty->assign(array(
            'imgUrl' => $imgUrl,
            'currentToken' => Tools::getAdminTokenLite('AdminCedElkjopnordicProfile'),
            'controllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicProfile'),
            'manufacturer_list' => $manufacturer_list,
            'getCategoryControllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicProfile') .
                '&method=getCategory',
            'elkjopnordicAttributeList' => array(),
            'elkjopnordicVariantAttributeList' => array(),
            'elkjopnordicVariantAttributes' => $this->elkjopnordicProfile->getVariantAttributes(),
            'storeFeatures' => $storeFeatures,
            'storeAttributes' => $storeAttributes,
            'storeDefaultAttributes' => $storeDefaultAttributes,
            //'profileVariantAttributes'=> $variantAttributeMapping,
            'skip_attributes' => $this->elkjopnordicCategory->getSkipAttributes(),
            'elkjopnordicDefaultAttributeList' => $this->elkjopnordicCategory->getDefaultAttributes(),
            'profileInfo' => $profileInfo,
            'profileAttributes' => $profileAttributes,
            'profileAdditionalInfo' => $profileAdditionalInfo,
            'productOfferState' => $this->elkjopnordicProfile->productOfferState(),
            'productReferenceType' => $this->elkjopnordicProfile->productReferenceType(),
            'productLogisticClass' => $this->elkjopnordicProfile->productLogisticClass(),
            'elkjopnordicClubEligible' => $this->elkjopnordicProfile->elkjopnordicClubEligible()
        ));
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $tree_categories_helper = new HelperTreeCategories('categories-treeview');
            $tree_categories_helper->setRootCategory((Shop::getContext() == Shop::CONTEXT_SHOP ?
                Category::getRootCategory()->id_category : 0))
                ->setUseCheckBox(true);
        } else {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $root_category = Category::getRootCategory();
                $root_category = array(
                    'id_category' => $root_category->id_category,
                    'name' => $root_category->name);
            } else {
                $root_category = array('id_category' => '0', 'name' => $this->l('Root'));
            }
            $tree_categories_helper = new Helper();
        }
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $tree_categories_helper->setUseSearch(true);
            $tree_categories_helper->setSelectedCategories($profileStoreCategories);
            $this->context->smarty->assign(array(
                'storeCategories' => $tree_categories_helper->render()));
        } else {
            $this->context->smarty->assign(array(
                'storeCategories' => $tree_categories_helper->renderCategoryTree(
                    $root_category,
                    $profileStoreCategories,
                    'categoryBox'
                )
            ));
        }
        $profileTemplate = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/edit_profile.tpl'
        );

        return $profileTemplate;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function ajaxProcessGetElkjopnordicCategory()
    {
        $params = Tools::getAllValues();
        $elkjopnordicCategory = new CedElkjopnordicCategory();
        $params = array_filter($params);

        $level = $params['max_level'];
        if ($params['max_level'] > 1) {
            $params['max_level'] = $params['max_level'] - 1;
        }

        $response_categories = $elkjopnordicCategory->getElkjopnordicCategory($params);
        $profileElkjopnordicCategories = array();
        if (isset($params['elkjopnordic_profile_id'])) {
            $profileData = $this->elkjopnordicProfile->getProfileDataById($params['elkjopnordic_profile_id']);
            $profileElkjopnordicCategories = $profileData['profileElkjopnordicCategories'];
        }

        if (isset($response_categories['success']) && !$response_categories['success']) {
            if (isset($response_categories['message'])) {
                die(json_encode(array(
                    'success' => false,
                    'message' => $response_categories['message']
                )));
            } else {
                die(json_encode(array(
                    'success' => false,
                    'message' => ''
                )));
            }
        }

        $this->context->smarty->assign(
            array(
                'elkjopnordicCategoryList' => $response_categories,
                'profileElkjopnordicCategories' => $profileElkjopnordicCategories,
                'level' => $level,
            )
        );

        if (!empty($response_categories) && count($response_categories) >= $level) {
            $res = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/elkjopnordic_categories.tpl'
            );
            die(json_encode(array(
                'success' => true,
                'message' => $res
            )));
        } else {
            die(json_encode(array(
                'success' => false,
                'message' => ''
            )));
        }
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessUpdateElkjopnordicCategoryAttributes()
    {

        $storeFeatures = $this->elkjopnordicHelper->getFeatures();
        $storeAttributes = $this->elkjopnordicHelper->getAttributes();
        $storeDefaultAttributes = $this->elkjopnordicHelper->getSystemAttributes();
        $profileId = Tools::getValue('elkjopnordic_profile_id');
        //  $category_id = Tools::getValue('hierarchy');

        $profileAttributeMapping = array();
         $variantAttributeMapping = array();
        if (isset($profileId) && is_numeric($profileId)) {
            $profileData = $this->elkjopnordicProfile->getProfileDataById($profileId);
            $profileAttributeMapping = $profileData['profileAttributes'];
        }
        $params = Tools::getAllValues();
        $elkjopnordicCategory = new CedElkjopnordicCategory();
        $attr_list = array();
        $message = '';

        $elkjopnordicAttributes = $elkjopnordicCategory->getElkjopnordicAttributes($params);

        if (isset($elkjopnordicAttributes['success']) && !$elkjopnordicAttributes['success']) {
            if (isset($elkjopnordicAttributes['message'])) {
                $message = $elkjopnordicAttributes['message'];
            }
        } else {
            $attr_list = $elkjopnordicAttributes;
        }

        if (!empty($attr_list)) {
            $this->context->smarty->assign(
                array(
                    'currentToken' => Tools::getAdminTokenLite('AdminCedElkjopnordicProfile'),
                    'controllerUrl' => $this->context->link->getAdminLink('AdminCedElkjopnordicProfile'),
                    'elkjopnordicAttributeList' => $attr_list,
                    'elkjopnordicVariantAttributeList' => array(),
                    'elkjopnordicVariantAttributes' => $this->elkjopnordicProfile->getVariantAttributes(),
                    'storeFeatures' => $storeFeatures,
                    'storeAttributes' => $storeAttributes,
                    'storeDefaultAttributes' => $storeDefaultAttributes,
                    'profileAttributes' => $profileAttributeMapping,
                    'profileVariantAttributes'=> $variantAttributeMapping,
                    'skip_attributes' => $this->elkjopnordicCategory->getSkipAttributes(),
                    'elkjopnordicDefaultAttributeList' => $this->elkjopnordicCategory->getDefaultAttributes(),
                )
            );


            $res = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/profile/profile_attribute_mapping.tpl'
            );
            die(json_encode(array(
                'success' => true,
                'message' => $res
            )));
        } else {
            die(json_encode(array(
                'success' => false,
                'message' => !empty($message) ? $message : 'Please Select Correct Leaf Category'
            )));
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function ajaxProcessGetAttributeOptions()
    {
        $params = Tools::getAllValues();
        $elkjopnordicCategory = new CedElkjopnordicCategory();
        $attributeOptions = $elkjopnordicCategory->getElkjopnordicAttributeOptions($params);
        $attr_option_list = array();
        if (isset($attributeOptions['success']) && !$attributeOptions['success']) {
            if (isset($attributeOptions['message'])) {
                $message = $attributeOptions['message'];
                $this->elkjopnordicHelper->log(
                    __METHOD__,
                    'Info',
                    $message,
                    json_encode(
                        array(
                            'Request Param' => $params,
                            'Response' => $attributeOptions
                        )
                    ),
                    true
                );
            }
        } else {
            $attr_option_list = $attributeOptions;
        }
        die(json_encode($attr_option_list));
    }

    /**
     *
     */
    public function saveProfile()
    {
        $db = Db::getInstance();
        $profileData = Tools::getAllValues();
        try {
            $profileId = Tools::getValue('elkjopnordicProfileId');
			if (!isset($profileData['categoryBox'])){
		$profileData['categoryBox'] = array();
		}
            $result = $this->elkjopnordicProfile->validateProfile($profileData);
        
            if (isset($result['valid']) && $result['valid']) {
                if (!empty($profileId)) {
                    $shop_id = Shop::getContextShopID();
                    $res = $db->update(
                        'cedelkjopnordic_profile',
                        array(
                            'title' => pSQL($profileData['profileTitle']),
                            'status' => (int)$profileData['profileStatus'],
                            'product_manufacturer' => pSQL(json_encode($profileData['profileManufacturer'])),
                            'store_category' => pSQL(json_encode($profileData['categoryBox'])),
                            'elkjopnordic_categories' => pSQL(json_encode($profileData['elkjopnordicCategory'])),
                            'profile_attribute_mapping' => pSQL(json_encode($profileData['profileAttributes'])),
                            'profile_additional_info' => pSQL(json_encode($profileData['profileelkjopnordicInfo']))
                        ),
                        'id=' . (int)$profileId. ' AND id_shop ='. (int)$shop_id
                    );
                    if ($res && count($profileData['categoryBox'])) {
                        $prod_result = $this->updateProfileProducts(
                            $profileId,
                            $profileData['categoryBox'],
                            $profileData['profileManufacturer'],
                            'update'
                        );
                        if ($prod_result) {
                            $this->confirmations[] = "Profile updated successfully";
                        }
                    }
                    if ($res) {
                            $this->confirmations[] = "Profile updated successfully";
                        }
                } else {
                    $p_code = $db->getValue(
                        "select `id` from `" . _DB_PREFIX_ . "cedelkjopnordic_profile` 
                                  where `title`='" . pSQL($profileData['profileTitle']) . "'"
                    );
                    if (!$p_code) {
                    // for multishop comatibility start
                        $shop_id = Shop::getContextShopID();
                        $shop_ids=array();
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
                        foreach($shop_ids as $shop_id){
                        $res = $db->insert(
                            'cedelkjopnordic_profile',
                            array(
                                'title' => pSQL($profileData['profileTitle']),
                                'status' => (int)$profileData['profileStatus'],
                                'product_manufacturer' => pSQL(json_encode($profileData['profileManufacturer'])),
                                'store_category' => pSQL(json_encode($profileData['categoryBox'])),
                                'elkjopnordic_categories' => pSQL(json_encode($profileData['elkjopnordicCategory'])),
                                'profile_attribute_mapping' => pSQL(json_encode($profileData['profileAttributes'])),
                                'profile_additional_info' => pSQL(json_encode($profileData['profileelkjopnordicInfo'])),
                                'id_shop' => (int)$shop_id
                            )
                        );
                        $newProfileId = $db->Insert_ID();
                        if ($res && $newProfileId && count($profileData['categoryBox'])) {
                            $prod_result = $this->updateProfileProducts(
                                $newProfileId,
                                $profileData['categoryBox'],
                                $profileData['profileManufacturer'],
                                'new'
                            );
                           
                        }
                        }
                        
                         if ($res) {
                            $this->confirmations[] = "Profile created successfully";
                        }
                         if ($res) {
                                $link = new LinkCore();
                                $controller_link = $link->getAdminLink('AdminCedElkjopnordicProfile') . '&created=1';
                                Tools::redirectAdmin($controller_link);
                               
                            }
                    } else {
                        $this->errors[] = "The profile code must be unique. " . $profileData['profileTitle'] .
                            " is already assigned to profile Id " . $p_code;
                    }
                }
            } else {
                foreach ($result['errors'] as $err) {
                    $this->errors[] = $err;
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * @param $profileId
     * @param array $categories
     * @param array $manufacturer
     * @param string $type
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function updateProfileProducts($profileId, $categories = array(), $manufacturer = array(), $type = '')
    {
        if ($profileId && count($categories)) {
            $db = Db::getInstance();
            $res = '';
            $productIds = array();
            $sql = "SELECT DISTINCT cp.`id_product` FROM `" . _DB_PREFIX_ . "category_product` cp
            JOIN `" . _DB_PREFIX_ . "product` p ON (p.id_product = cp.`id_product`) JOIN `" . _DB_PREFIX_ .
                "manufacturer` m ON (p.id_manufacturer = m.id_manufacturer) WHERE `id_category` IN (" .
                implode(',', (array)$categories) . ") AND p.id_manufacturer IN (" .
                implode(',', (array)$manufacturer) . ")";
            $data = $db->executeS($sql);
            if (count($data)) {
                foreach ($data as $item) {
                    $productIds[] = $item['id_product'];
                }
            }
            $idsToDisable = array();
            if (count($productIds)) {
                $query = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products` 
                WHERE `id_cedelkjopnordic_profile` != " . (int)$profileId . " AND `id_product` 
                IN (" . implode(',', (array)$productIds) . ")";
                $dbResult = $db->executeS($query);
                if (count($dbResult)) {
                    foreach ($dbResult as $re) {
                        $idsToDisable[] = $re['id_product'];
                    }
                }
                $query = "DELETE FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products` 
                WHERE `id_cedelkjopnordic_profile` != " . (int)$profileId . " AND `id_product` 
                IN (" . implode(',', (array)$productIds) . ")";
                $db->execute($query);

                if ($type == 'new') {
                } else {
                    $idsToDisableSameProfile = array();
                    $sqlQuery = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products`
                     WHERE `id_cedelkjopnordic_profile` = " . (int)$profileId . " AND `id_product` 
                     NOT IN (" . implode(',', (array)$productIds) . ")";
                    $queryResult = $db->executeS($sqlQuery);
                    if (count($queryResult)) {
                        foreach ($queryResult as $res) {
                            $idsToDisableSameProfile[] = $res['id_product'];
                        }
                    }

                    $idsToDisable = array_merge($idsToDisable, $idsToDisableSameProfile);
                    $query = "DELETE FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products` 
                    WHERE `id_cedelkjopnordic_profile` = " . (int)$profileId . "";
                    $db->execute($query);
                }

                $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedelkjopnordic_profile_products` 
                (id_cedelkjopnordic_profile, id_product) values";
                foreach ($productIds as $id) {
                    $sql .= "(" . (int)$profileId . ", " . (int)$id . "),";
                }
                $sql = rtrim($sql, ',');
                $sql .= ";";
                $res = $db->execute($sql);
                if ($res) {
                    return true;
                }
            }
        }
        return true;
    }

    /**
     *
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
        $this->addJquery();
        $this->addJqueryPlugin('autocomplete');
        $this->addJS(_PS_MODULE_DIR_ . 'cedelkjopnordic/views/js/admin/profile/profile.js');
    }
}
