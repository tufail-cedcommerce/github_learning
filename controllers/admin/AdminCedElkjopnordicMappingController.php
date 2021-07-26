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
 
class AdminCedElkjopnordicMappingController extends ModuleAdminController
{
    public $defaultLang;

    public function __construct()
    {
        $this->lang = false;
        $this->defaultLang = ((int)Configuration::get('CEDELKJOPNORDIC_LANGUAGE_STORE')) ?
            (int)Configuration::get('CEDELKJOPNORDIC_LANGUAGE_STORE')
            : (int)Configuration::get('PS_LANG_DEFAULT');
        if (Tools::getIsset('removeelkjopnordic_option_mapping')
            && Tools::getValue('removeelkjopnordic_option_mapping')
            && Tools::getValue('id')
        ) {
            $status = $this->deleteAttributemap(Tools::getValue('id'));
            if ($status) {
                $this->confirmations[] = 'Attribute Deleted Successfully.';
            } else {
                $this->errors[] = 'Failed To Delete Attribute(s).';
            }
        }
        if (Tools::getIsset('mapped') && Tools::getValue('mapped')) {
            $this->confirmations[] = 'Mapping Saved Successfully.';
        }
        $this->_orderBy = 'id';
        $this->_orderWay = 'DESC';
        $this->bootstrap = true;
        $this->table = 'cedelkjopnordic_option_mapping';
        $this->identifier = 'id';
       
        $this->_select .= 'marketplace_attribute, id_attribute';
        $this->list_no_link = true;
        $this->addRowAction('edit');
        $this->addRowAction('remove');
        $this->fields_list = array(
            'id' => array(
                'title' => 'ID',
                'type' => 'text',
            ),
            'marketplace_attribute' => array(
                'title' => 'Marketplace Attribute',
                'type' => 'text',
                'callback' => 'marketplaceName'
            ),
            'id_attribute' => array(
                'title' => 'Store Attribute',
                'type' => 'text',
                'callback' => 'attributeName'
            ),
        );
        parent::__construct();
    }
    
    public function attributeName($row_value, $data )
    {
       if($row_value=='id_manufacturer')
       $row_value = 'Manufacturer';
       else if(strpos($row_value,'feature')!==false){
       
       $value  = Db::getInstance()->getvalue("SELECT name FROM `"._DB_PREFIX_."feature_lang` WHERE id_feature ='".str_replace('feature-','',$row_value)."' AND id_lang = '".Context::getContext()->language->id."'");
       if($value)
       $row_value = $value;
       }
       
       else {
       $value  = Db::getInstance()->getvalue("SELECT name FROM `"._DB_PREFIX_."attribute_lang` WHERE id_attribute ='".$row_value."' AND id_lang = '".Context::getContext()->language->id."'");
       if($value)
       $row_value = $value;
       }
       return $row_value;
    }
    public function marketplaceName($row_value, $data )
    {
       if((int)$row_value && is_numeric($row_value))
       {
       $value  = Db::getInstance()->getRow("SELECT attribute_label FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE `attribute_code` LIKE '".$row_value."' ORDER BY `attribute_type` ASC");
       if(isset($value['attribute_label']) && $value['attribute_label'])
       $row_value = $value['attribute_label'];
       }
       return $row_value;
    }
    public function deleteAttributemap($id = '')
    {
        $db = Db::getInstance();
        if ($id) {
            $result = $db->delete(
                'cedelkjopnordic_option_mapping',
                'id=' . (int)$id
            );
            if ($result) {
                return true;
            } else {
                return false;
            }
        } else {
            $sql = "TRUNCATE TABLE `" . _DB_PREFIX_ . "cedelkjopnordic_option_mapping`";
            return $db->execute($sql);
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_mapping'] = array(
                'href' => self::$currentIndex . '&addcedelkjopnordic_option_mapping&token=' . $this->token,
                'desc' => 'Add Mapping',
                'icon' => 'process-icon-new'
            );
            $this->page_header_toolbar_btn['delete_all'] = array(
                'href' => self::$currentIndex . '&deleteall&token=' . $this->token,
                'desc' => 'Delete All',
                'icon' => 'process-icon-eraser'
            );
        } else {
            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => 'Back To List',
                'icon' => 'process-icon-back'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryUi('ui.autocomplete');
    }
    public function renderForm()
    {


         $profileIds = Db::getInstance()->executes("SELECT id, title as profile_name,elkjopnordic_categories FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE id_shop = '".Context::getContext()->shop->id."'");
     
        $profile_attr = array();
        $selected_profile = Tools::getValue('id_profile');
        if($selected_profile) {
        
          $elkjopnordic_categories = Db::getInstance()->getValue("SELECT elkjopnordic_categories FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE id_shop = '".Context::getContext()->shop->id."' AND id='".$selected_profile."'");
          $elkjopnordic_categories = @json_decode($elkjopnordic_categories, true);
         
          if(!empty($elkjopnordic_categories) && isset($elkjopnordic_categories['level_2']) && $elkjopnordic_categories['level_2'])
           $profile_attr = Db::getInstance()->executes("SELECT attribute_code, attribute_label,category_id FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE category_id = '".pSQL($elkjopnordic_categories['level_2'])."' AND attribute_type = 'LIST'");     
        } else if($profileIds['0']['elkjopnordic_categories']){
          $elkjopnordic_categories = $profileIds['0']['elkjopnordic_categories'];
          $elkjopnordic_categories = @json_decode($elkjopnordic_categories, true);
         
          if(!empty($elkjopnordic_categories) && isset($elkjopnordic_categories['level_2']) && $elkjopnordic_categories['level_2'])
           $profile_attr = Db::getInstance()->executes("SELECT attribute_code, attribute_label,category_id FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE category_id = '".pSQL($elkjopnordic_categories['level_2'])."' AND attribute_type = 'LIST'");
        }
        
        $elkjopnordic_attributes = $profile_attr;
        $this->array_sort_by_column( $elkjopnordic_attributes, 'attribute_label');
        $elkjopnordic_attributes_values = array();
    
        $rowCount = 0;
        $already_mapped_attributes = array();
        if (Tools::getIsset('id') && Tools::getValue('id')) {
            $already_mapped_attributes = $this->getAttributeMappings(Tools::getValue('id'));
            $already_mapped_attribute_values = $this->getAttributeMappingValues(Tools::getValue('id'));
            $already_mapped_attributes['mapped_options'] = $already_mapped_attribute_values;
   
            if (!empty($already_mapped_attributes['mapped_options'])) {
                $rowCount = count($already_mapped_attributes['mapped_options']);
            }
        }
        
        $profileIds = Db::getInstance()->executes("SELECT id, title as profile_name FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE id_shop = '".Context::getContext()->shop->id."'");
        $this->array_sort_by_column($profileIds, 'profile_name');
        $features = $this->getStoreAttributes();
        $option_values = $this->getStoreAttributeValues();

        $controllerUrl = $this->context->link->getAdminLink('AdminCedElkjopnordicMapping').'&addcedelkjopnordic_option_mapping';
        $selected_option_id = Tools::getValue('selected_option_id');

        $show_option  = Tools::getValue('show_option');
        $this->context->smarty->assign(
            array(
                'token' => $this->token,
                'controllerUrl' => $controllerUrl,
                'option_values' => $option_values,
                'features' => $features,
                'profileIds' => $profileIds,
                'attribute_row' => $rowCount,
                'rowCount' => $rowCount,
                'show_option' => $show_option,
                'selected_option_id' => $selected_option_id,
                'selected_profile' => $selected_profile,
                'already_mapped_attributes' => $already_mapped_attributes,
                'elkjopnordic_attributes' => $elkjopnordic_attributes,
                'elkjopnordic_attributes_values' => $elkjopnordic_attributes_values,
            )
        );
        $return = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedelkjopnordic/views/templates/admin/option/option_mapping.tpl'
        );
        return $return;
    }
    
    function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
            $sort_col = array();
            foreach ($arr as $key=> $row) {
                $sort_col[$key] = $row[$col];
            }

            array_multisort($sort_col, $dir, $arr);
        }

    public function ajaxProcessgetAttributeValues()
    {
       $params = Tools::getAllValues();
        if(isset( $params['profile']) && $params['profile']) {
          $profile_attr = array();
          $elkjopnordic_categories = Db::getInstance()->getValue("SELECT elkjopnordic_categories FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE id_shop = '".Context::getContext()->shop->id."' AND id='".$params['profile']."'");
          $elkjopnordic_categories = @json_decode($elkjopnordic_categories, true);
         
          if(!empty($elkjopnordic_categories) && isset($elkjopnordic_categories['level_2']) && $elkjopnordic_categories['level_2']){
         
           $profile_attr = Db::getInstance()->executes("SELECT value_code, value_label FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE category_id LIKE '".pSQL($elkjopnordic_categories['level_2'])."' AND attribute_code='".$params['cedelkjopnordic_option_id']."' AND value_label LIKE '%".$params['type']."%'");  
          }
            
           
           die(json_encode($profile_attr)); 
        }
    }
    
    public function ajaxProcessgetAttributeValuesRefresh()
    {
       $params = Tools::getAllValues();
        if(isset( $params['profile']) && $params['profile']) {
          $profile_attr = array();
          $elkjopnordic_categories = Db::getInstance()->getValue("SELECT elkjopnordic_categories FROM `"._DB_PREFIX_."cedelkjopnordic_profile` WHERE id_shop = '".Context::getContext()->shop->id."' AND id='".$params['profile']."'");
          $elkjopnordic_categories = @json_decode($elkjopnordic_categories, true);
         
          if(!empty($elkjopnordic_categories) && isset($elkjopnordic_categories['level_2']) && $elkjopnordic_categories['level_2']){
         
           $profile_attr = Db::getInstance()->executes("SELECT value_code, value_label FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE category_id LIKE '".pSQL($elkjopnordic_categories['level_2'])."' AND attribute_code='".$params['cedelkjopnordic_option_id']."'");  
          
          $value_list_param = array();
        
         
          $value_list_param['code'] = $params['cedelkjopnordic_option_id'];
          $this->elkjopnordicHelper = new CedElkjopnordicHelper();
          $response = $this->elkjopnordicHelper->WGetRequest('values_lists', $value_list_param, 'json');
                  
                if (isset($response['success']) && $response['success']) {
                    $response_attribute_options = $response['response'];
                    $response_attribute_options = json_decode($response_attribute_options, true);
                    if (isset($response_attribute_options['values_lists'][0]['values'])) {
                        $response_attribute_options = $response_attribute_options['values_lists'][0]['values'];
                    }
                    if(!empty($response_attribute_options)){
                    Db::getInstance()->execute("DELETE FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE category_id LIKE '".pSQL($elkjopnordic_categories['level_2'])."' AND attribute_code='".$params['cedelkjopnordic_option_id']."'");

                    $attribute_option_db_chunk = array_chunk($response_attribute_options, 100);
                   
                    foreach ($attribute_option_db_chunk as $options) {
                        $attribute_option_db_param = array();
                        foreach ($options as $data) {
                             $attribute_option_db_param[] = array(
                                 'category_id' => pSQL($elkjopnordic_categories['level_2']),
                                 'attribute_code' => pSQL($value_list_param['code']),
                                 'values_list' =>  pSQL($value_list_param['code']),
                                 'default_value' => '',
                                 'value_code' =>  pSQL($data['code']),
                                 'value_label' =>  pSQL($data['label'])
                             );
                        }
                        Db::getInstance()->insert(
                            'cedelkjopnordic_attribute_options',
                            $attribute_option_db_param
                        );
                        }
                    }
                    
                }
                }  
           die(json_encode($profile_attr)); 
        }
    }

    public function getAmazonColorMaps()
    {
         $result = array();
        return $result;
    }

    public function getAttributeMappings($id)
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_option_mapping` where `id`='" . $id . "'";
        $result = $db->ExecuteS($sql);
        if (is_array($result) && count($result)) {
            return $result['0'];
        } else {
            return array();
        }
    }
    
    public function getAttributeMappingValues($id)
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_option_mapping_values` where `id_mapping`='" .(int)$id . "'";
        $result = $db->ExecuteS($sql);
        if (!empty($result)) {
            return $result;
        } else {
            return array();
        }
    }

    public function getStoreAttributes()
    {
        $db = Db::getInstance();
        $default_lang = Configuration::get('PS_LANG_DEFAULT');
        $sql = "SELECT CONCAT('attribute-',`id_attribute_group`) as `id_attribute`,`name` 
        FROM `" . _DB_PREFIX_ . "attribute_group_lang` where `id_lang`='" . $default_lang . "'";
        $result = $db->ExecuteS($sql);
        if (is_array($result) && count($result)) {
            $result[] = array('id_attribute'=> 'id_manufacturer' ,'name' => 'Manufacturer');     
            $sql = "SELECT CONCAT('feature-',`id_feature`) as id_attribute,`name` 
        FROM `" . _DB_PREFIX_ . "feature_lang` where `id_lang`='" . $default_lang . "'";
            $features = $db->ExecuteS($sql);
            if(!empty($features)){
            $result = array_merge($features,$result);
            }      
            $this->array_sort_by_column($result, 'name');
            return $result;
        } else {
            return $result[] = array('id_attribute'=> 'id_manufacturer' ,'name' => 'Manufacturer');  
        }
    }

    public function getStoreAttributeValues()
    {
        $db = Db::getInstance();
        $default_lang = (Context::getContext()->language->id)?Context::getContext()->language->id: Configuration::get('PS_LANG_DEFAULT');
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "attribute` a 
        LEFT join `" . _DB_PREFIX_ . "attribute_lang` al ON (a.id_attribute = al.id_attribute) 
        where al.id_lang='" . (int)$default_lang . "'";
        $result = $db->ExecuteS($sql);
        if (!empty($result)) {
            $option_values = array();
            $this->array_sort_by_column($result, 'name');
            foreach ($result as $value) {
                $option_values['attribute-'.$value['id_attribute_group']][$value['name']] = $value['name'];
            }
            $manufacturers = Db::getInstance()->executes("SELECT `name` FROM `"._DB_PREFIX_."manufacturer` WHERE active='1'");
            foreach($manufacturers as $manufacturer){
            $option_values['id_manufacturer'][$manufacturer['name']] = $manufacturer['name'] ;
            }
             $sql = "SELECT CONCAT('feature-',a.id_feature) as id_attribute_group,al.value FROM `" . _DB_PREFIX_ . "feature_value` a 
        LEFT join `" . _DB_PREFIX_ . "feature_value_lang` al ON (a.id_feature_value = al.id_feature_value) 
        where al.id_lang='" . (int)$default_lang . "'";
        $features = $db->ExecuteS($sql);
        $this->array_sort_by_column($features, 'value');
         foreach($features as $feature){
            $option_values[$feature['id_attribute_group']][$feature['value']] = $feature['value'] ;
            }
        
            return $option_values;
        } else {
            return array();
        }
    }

    public function postProcess()
    {
        try {
            if (Tools::getIsset('savemapping') && Tools::getValue('savemapping')) {
                if (version_compare(_PS_VERSION_, '1.6.1', '>=') == true) {
                    $values = Tools::getAllValues();
                } else {
                    $values = $_POST;
                }
                $status = $this->saveOptionMapping($values);
                if ($status) {
                    $link = new LinkCore();
                    $controller_link = $link->getAdminLink('AdminCedElkjopnordicMapping') . '&mapped=1';
                    Tools::redirectAdmin($controller_link);
                    $this->confirmations[] = 'Attribute Mapped Successfully.';
                } else {
                    $this->errors[] = 'Failed To Map Attribute(s).';
                }
            }
            if (Tools::getIsset('deleteall')) {
                $r = $this->deleteAttributemap();
                if ($r) {
                    $this->confirmations[] = "Mapping Deleted Successfully";
                } else {
                    $this->errors[] = "Failed To Delete Mapping";
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        parent::postProcess();
    }

    public function saveOptionMapping($data)
    {
    //print_r(Tools::getAllValues());die;
         if (isset($data['cedelkjopnordic_option_mapping'])
            && count($data['cedelkjopnordic_option_mapping'])
            && isset($data['cedelkjopnordic_option_id'])
            && isset($data['store_option_id'])
        ) {
            $db = Db::getInstance();
            $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_option_mapping` 
            where `marketplace_attribute` = '" . pSQL($data['cedelkjopnordic_option_id']) . "' AND `id_profile` = '".(int)$data['product_data_profile_selected']."'";
            $result = $db->ExecuteS($sql);
            $id_mapping = 0;
            if (isset($data['id']) && $data['id'] && isset($result['0']['id'])) {
                $id_mapping = (int)$result['0']['id'];
                $res = $db->update(
                    'cedelkjopnordic_option_mapping',
                    array(
                        'marketplace_attribute' => pSQL($data['cedelkjopnordic_option_id']),
                        'id_attribute' => pSQL($data['store_option_id']),
                        'id_profile' => (int)$data['product_data_profile_selected'],
                    ),
                    'id=' . (int)$result['0']['id']
                );
            } else {
                $res = $db->insert(
                    'cedelkjopnordic_option_mapping',
                    array(
                        'marketplace_attribute' => pSQL($data['cedelkjopnordic_option_id']),
                        'id_attribute' => pSQL($data['store_option_id']),
                        'id_profile' => (int)$data['product_data_profile_selected'],
                    )
                );
                $id_mapping = (int)$db->Insert_ID();
            }
            if ($res && $id_mapping) {
                $db->delete(
                    'cedelkjopnordic_option_mapping_values',
                    'id_mapping=' . (int)$id_mapping
                );
                if(isset($data['cedelkjopnordic_option_mapping']) 
                && !empty($data['cedelkjopnordic_option_mapping'])) {
                        foreach($data['cedelkjopnordic_option_mapping'] as $cedelkjopnordic_option_mapping) {
                         $db->insert(
                            'cedelkjopnordic_option_mapping_values',
                            array(
                                'marketplace_value' => pSQL($cedelkjopnordic_option_mapping['cedelkjopnordic_option_value']),
                                'marketplace_code' => pSQL($cedelkjopnordic_option_mapping['cedelkjopnordic_option_code']),
                                'store_value' => pSQL($cedelkjopnordic_option_mapping['store_option_value']),
                                'id_mapping' => (int)$id_mapping,
                            )
                        );
                        }
                }
               
                return true;
            } else {
                return false;
            }
        }
    }

    public function displayRemoveLink($token = null, $id = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('Remove', self::$cache_lang)) {
            self::$cache_lang['Remove'] = 'Remove';
        }
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' .
                $id . '&removeelkjopnordic_option_mapping=' .
                $id . '&token=' . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Remove'],
            'id' => $id
        ));
        return $tpl->fetch();
    }
}

