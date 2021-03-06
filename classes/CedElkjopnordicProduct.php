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
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProfile.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicCategory.php';
//include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/StripTags.php';

class CedElkjopnordicProduct
{
    public $elkjopnordicProfile;
    public $elkjopnordicCategory;
    public $elkjopnordicHelper;
    public $product_info = array();
    public $defaultLang;

    public function __construct()
    {
        $this->defaultLang = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));
        
        $this->elkjopnordicProfile = new CedElkjopnordicProfile();
        $this->elkjopnordicCategory = new CedElkjopnordicCategory();
        $this->elkjopnordicHelper = new CedElkjopnordicHelper();
    }

    /**
     * @param $ids
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uploadProducts($ids)
    {
        $productToUploadList = array();
        $errors = array();
        $success = array();

        if (count($ids) > 0) {
            $defaultLanguage = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));
            $shop_id = Context::getContext()->shop->id;
            foreach ($ids as $id) {
                $validation_error = array();
                if (!$id) {
                    $errors[] = array('success' => array(), 'error' => array(array('Id ' . $id . ' is invalid')));
                }
				$s = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products` WHERE `id_product`='" .
            (int)$id . "' AND `product_feed_status`='2' AND id_shop = '".(int)$shop_id."'";
                    $is_created = Db::getInstance()->getValue($s);
                    
                    if($is_created){
					
                    $errors[] ='Product ID '.$id.' is Excluded';
               		 continue;
					}
                    

                $product = new Product($id, true);
                if ($id) {
                    $this->product_info = (array)$product;
                }


                $elkjopnordic_category = '';
                $attribute_mappings = array();


                $profileData = $this->getProductsProfile($id);
                $profile_id = 0; 
                if (!$profileData) {
                    $validation_error[] = 'Profile not Mapped For Product' . $id;
                    $profileData = array();
                } else {
                    $elkjopnordic_category = $profileData['profileElkjopnordicCategories'];
                    $count = count($elkjopnordic_category);
                    $elkjopnordic_category = $elkjopnordic_category['level_' . $count];

                    $attribute_mappings = $profileData['profileAttributes'];
                    if (is_array($attribute_mappings)) {
                        $attribute_mappings = array_filter($attribute_mappings);
                    }
                    $profile_id = $profileData['id'];
                }

                $productToUpload = $this->prepareSimpleProduct(
                    $id,
                    $this->product_info,
                    $profileData,
                    $elkjopnordic_category,
                    $attribute_mappings
                );

                $productToUpload = $this->prepareVariantProduct($id, $product, $productToUpload, $profileData,$elkjopnordic_category);

                foreach ($productToUpload as $elkjopnordic_product) {
                

                    $sku = isset($elkjopnordic_product['Shop_SKU']) ? $elkjopnordic_product['Shop_SKU'] : '';

                    $elkjopnordic_product['ProductID'] = $sku;
                    $elkjopnordic_product['CategoryIdentifier'] = $elkjopnordic_category;

                    $validation_result = $this->validateProduct($elkjopnordic_product, $id, $sku, $profile_id,$attribute_mappings);
               
                    if (isset($validation_result['success']) && $validation_result['success']) {
                        if (!isset($productToUploadList[$elkjopnordic_category]) ||
                            !is_array($productToUploadList[$elkjopnordic_category])) {
                            $productToUploadList[$elkjopnordic_category] = array();
                        }
                        array_push($productToUploadList[$elkjopnordic_category], $elkjopnordic_product);
                    } else {
                        $validation_error = array_merge($validation_error, $validation_result['error']);
                    }
                }

                if (!empty($validation_error)) {
                    $this->updateErrorInformation($id, $validation_error);
                } else {
                    $this->updateErrorInformation($id, array());
                }
                $errors = array_merge($errors, $validation_error);
            }
            
            if (!empty($productToUploadList)) {
                foreach ($productToUploadList as $productToUpload) {
                    $status = $this->feedRequest($productToUpload, 'products');
                    if (isset($status['success']) && $status['success']) {
                        $success[] = $status['message'];
                    } else {
                        $errors[] = $status['message'];
                    }
                }
            }
            return array('success' => $success, 'error' => $errors);
        }
    }

    /**
     * @param $product_id
     * @param $product
     * @param $profileData
     * @param $elkjopnordic_category
     * @param $attribute_mappings
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function prepareSimpleProduct($product_id, $product, $profileData, $elkjopnordic_category, $attribute_mappings)
    {
        $productMappedAttributes = $this->getProductData($product_id);
      
        $productToUpload = $this->getMappingValues(
            $product_id,
            $product,
            $attribute_mappings,
            $productMappedAttributes,
            $profileData,
            $elkjopnordic_category
        );

        if ($this->productSecondaryImageURL($product_id)) {
            $images_array = $this->productSecondaryImageURL($product_id);

            for ($i = 1; $i <= 3; $i++) {
         
                $productToUpload = $this->setProductImages($productToUpload, (int)$i, $images_array);
                
            }
           
        }

        



        if (isset($profileData['profileAdditionalInfo'])) {
            $productToUpload = $this->setReferenceType(
                $productToUpload,
                $product,
                $profileData['profileAdditionalInfo']
            );
        }
        $productToUpload['VariantId']='';
        
         if(isset($productToUpload['Title_en']) && $productToUpload['Title_en'])
                        $productToUpload['Title_en'] = substr($productToUpload['Title_en'], 0, 70);


                        if(isset($productToUpload['Title_no']) && $productToUpload['Title_no'])
                        $productToUpload['Title_no'] = substr($productToUpload['Title_no'], 0, 70);


                        if(isset($productToUpload['Title_fi']) && $productToUpload['Title_fi'])
                        $productToUpload['Title_fi'] = substr($productToUpload['Title_fi'], 0, 70);


                        if(isset($productToUpload['Title_se']) && $productToUpload['Title_se'])
                        $productToUpload['Title_se'] = substr($productToUpload['Title_se'], 0, 70);


                        if(isset($productToUpload['Title_dk']) && $productToUpload['Title_dk'])
                        $productToUpload['Title_dk'] = substr($productToUpload['Title_dk'], 0, 70);

                       if(isset($productToUpload['Article_name']))
                        $productToUpload['Article_name'] = substr($productToUpload['Article_name'] , 0, 50);
       
        return $productToUpload;
    }

    /**
     * @param $id
     * @param Product $product
     * @param $productToUpload
     * @param $profileData
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function prepareVariantProduct($id, Product $product, $productToUpload, $profileData)
    {
        $defaultLanguage = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));
        if ($product->getAttributeCombinations($defaultLanguage)) {
            $product_combination = $this->getAttributesResume($id, $defaultLanguage);

            $variantProductToUpload = $this->processCombinations($productToUpload, $product_combination, $profileData);
        } else {
            $variantProductToUpload[0] = $productToUpload;
        }
        return $variantProductToUpload;
    }

    /**
     * @param $data
     * @param $product_id
     * @param $sku
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function validateProduct(&$data, $product_id, $sku, $profile_id=0,$attribute_mappings= array())
    {

        $category_param = array();
        if (empty($sku)) {
            $sku = $product_id;
        }
        $errors = array();
        $elkjopnordic_category = $data['CategoryIdentifier'];
        $elkjopnordic_attributes = array();
        if (empty($elkjopnordic_category)) {
            $errors[] = "$sku : No Elkjopnordic Category Found";
        }
        if (empty($data['Shop_SKU'])) {
            $errors[] = "$sku : Product Sku is Empty";
        }

        $category_param['hierarchy'] = $elkjopnordic_category;
        $attr_response = $this->elkjopnordicCategory->getElkjopnordicAttributes($category_param);
        if (isset($attr_response['success']) && !$attr_response['success']) {
            $errors[] = "$sku : " . $attr_response['message'];
        } else {
            $elkjopnordic_attributes = $attr_response;
        }

        foreach ($elkjopnordic_attributes as $elkjopnordic_attribute) {
            $attr_code = $elkjopnordic_attribute['attribute_code'];
            $attr_label = $elkjopnordic_attribute['attribute_label'];
            if($profile_id){
                  
               $mapping_code = str_replace('system-','',$attribute_mappings[$attr_code]['mapping']);
 
                    $mappings = Db::getInstance()->getRow("SELECT * FROM `"._DB_PREFIX_."cedelkjopnordic_option_mapping` op JOIN `"._DB_PREFIX_."cedelkjopnordic_option_mapping_values` opv ON (op.id=opv.id_mapping) WHERE op.id_profile='".$profile_id."' AND op.marketplace_attribute='".pSQL($attr_code)."' AND op.id_attribute='".pSQL($mapping_code)."' AND store_value LIKE '".pSQL($data[$attr_code])."'");
             
                    if(isset($mappings['marketplace_code']) && $mappings['marketplace_code']){
                    $data[$attr_code] =$mappings['marketplace_code'];
                    } else if(isset($mappings['marketplace_value']) && $mappings['marketplace_value']){
                    $data[$attr_code] = $mappings['marketplace_value'];
                    }            
            }
                
            if (isset($elkjopnordic_attribute['required']) && $elkjopnordic_attribute['required']) {
                $is_required = true;
            } else {
                $is_required = false;
            }
            
            $cedCat= new CedElkjopnordicCategory();
            $skipattr = $cedCat->getSkipAttributes();
            if ($is_required) {
                if (in_array($attr_code, $skipattr)) {
                    continue;
                } else {
                    if (!isset($data[$attr_code]) || empty($data[$attr_code])) {
                        $errors[] = "$sku : Required Attribute '<b>$attr_label</b>' is not mapped 
                    or has null value";
                    }
                }
            }
        }

        if (!empty($errors)) {
            return array(
                'error' => $errors
            );
        }
        return array(
            'success' => true
        );
    }

    /**
     * @param $product_id
     * @param array $error
     * @throws PrestaShopDatabaseException
     */
    public function updateErrorInformation($product_id, $error = array(),$status = "")
    {
        $db = Db::getInstance();
        $shop_id = Context::getContext()->shop->id;
        if (!empty($error)) {
            $status = 'Invalid';
        }
        $s = "SELECT `id`, `elkjopnordic_status` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products` WHERE `id_product`='" .
            (int)$product_id . "' AND id_shop = '".(int)$shop_id."'";
        $q = $db->executeS($s);

        if (count($q)) {
            if (empty($error)) {
                $status = isset($status)?$status:$q[0]['elkjopnordic_status'];
            }
            if (empty($status) || $status == 'Invalid') {
                $status = 'Uploaded';
            }
            $sql = "UPDATE `" . _DB_PREFIX_ . "cedelkjopnordic_products` SET `error_message`='" .
                pSQL(json_encode($error)) . "', `elkjopnordic_status`='" . $status . "',id_shop = '".(int)$shop_id."' 
          WHERE `id_product`='" . (int)$product_id . "'";
          $db->execute($sql);
        } else {
            if ($status) {
                $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedelkjopnordic_products` SET `id_product`='" . $product_id . "', 
          `error_message`='" . pSQL(json_encode($error)) . "', `elkjopnordic_status`='" . $status . "',id_shop = '".(int)$shop_id."'";
			 $db->execute($sql);
            }
            
        }
        
        
    }

    /**
     * @param $productToUpload
     * @param $i
     * @param $images_array
     * @return mixed
     */
    public function setProductImages($productToUpload, $i, $images_array)
    {
        if ($i == 1 && !isset($productToUpload['Image1'])) {
            if (isset($images_array['Image1'])) {
                $productToUpload['Image1'] = $images_array['Image1'];
            } else {
                $productToUpload['Image1'] = '';
            }
        } else {
            if (isset($images_array['productSecondaryImageURL'][$i])) {
                $productToUpload["Image".($i)] = $images_array['productSecondaryImageURL'][$i];
            } else {
                $productToUpload["Image".($i)] = '';
            }
        }
        
        return $productToUpload;
    }

    /**
     * @param $product_id
     * @param $product
     * @param $attribute_mappings
     * @param $productMappedAttributes
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getMappingValues($product_id, $product, $attribute_mappings, $productMappedAttributes,$profileData,$elkjopnordic_category_value='')
    {
        $mapped_values = array();
       
        if ($product_id && count($attribute_mappings)) {

            foreach ($attribute_mappings as $key => $value) {

                if ($key == 'price' || $key == 'quantity' || $key == 'variant-size-value'
                    || $key == 'variant-colour-value') {
                    continue;
                }

                if (isset($productMappedAttributes[$key]['default_value']) &&
                    $productMappedAttributes[$key]['default_value'] != '') {
                    $attr_val = $productMappedAttributes[$key]['default_value'];
                } else {
                    $mapped_expresion = explode('-', $value['mapping']);
                    $attr_val = '';

                    if (isset($mapped_expresion['0'])
                        && in_array($mapped_expresion['0'], array('system', 'attribute', 'feature'))) {
                        $attr_type = $mapped_expresion['0'];

                        $attribute_id = str_replace($attr_type . '-', "", $value['mapping']);

                        if ($attr_type=='attribute') {

                            $attr_val = $this->getAttributeValue($attribute_id, $product_id);
                        }else if($attr_type=='feature'){
                                $attr_val = $this->getFeatureValue($attribute_id, $product_id,$elkjopnordic_category_value,$key);

                        }else if($attr_type== 'system') {

                                $attribute_id_lang = explode('-', $attribute_id);
                               
                                if (isset($attribute_id_lang['0']) &&  isset($attribute_id_lang['1']) && $attribute_id_lang['1']) {
                                    $attr_val = $this->getSystemValue($attribute_id_lang['0'], $product, $product_id,$attribute_id_lang['1'],$elkjopnordic_category_value,$key);
                                } else if($attribute_id=='elcategory'){
                                    $elkjopnordic_category = $profileData['profileElkjopnordicCategories'];
                                    $count = count($elkjopnordic_category);
                                    $attr_val = $elkjopnordic_category['level_' . $count];
                                } else {
                                    $attr_val = $this->getSystemValue($attribute_id, $product, $product_id,0,$elkjopnordic_category_value,$key);
                                }

                        } else {
                        $attr_val = $value;
                        }

                    } else {
                    if (isset($value['default_value']) && $value['default_value']) {
                        $attr_val = $value['default_value'];
                    } else if (isset($value['default_text']) && $value['default_text']) {
                        $attr_val = $value['default_text'];
                    }
                    }
                }

                if ($attr_val == '') {
                    if (isset($value['default_value']) && $value['default_value']) {
                        $attr_val = $value['default_value'];
                    }
                    if (isset($value['default_text']) && $value['default_text']) {
                        $attr_val = $value['default_text'];
                    }
                    if (isset($value['default_code']) && $value['default_code']) {
                        $attr_val = $value['default_code'];
                    }
                    
                }
                $mapped_values[$key] = $attr_val;
            }
            
            return $mapped_values;
        } else {
            return array();
        }
    }

    /**
     * @param $attribute_group_id
     * @param $product_id
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function getAttributeValue($attribute_group_id, $product_id)
    {
        $sql_db_intance = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $features = $sql_db_intance->executeS('
	        SELECT *, al.name as attr_name
			FROM ' . _DB_PREFIX_ . 'product_attribute pa
			LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac 
			ON pac.id_product_attribute = pa.id_product_attribute
			LEFT JOIN ' . _DB_PREFIX_ . 'attribute a 
			ON a.id_attribute = pac.id_attribute
			LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group ag 
			ON ag.id_attribute_group = a.id_attribute_group
			LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al 
			ON (a.id_attribute = al.id_attribute AND al.id_lang = "' . (int)$this->defaultLang . '")
			LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl 
			ON (ag.id_attribute_group = agl.id_attribute_group 
			AND agl.id_lang = "' . (int)$this->defaultLang . '")
			WHERE pa.id_product = "' . (int)$product_id . '" 
			AND a.id_attribute_group = "' . (int)$attribute_group_id . '" 
			ORDER BY pa.id_product_attribute');
        if (isset($features['0']['attr_name'])) {
            return $features['0']['attr_name'];
        } else {
            return false;
        }
    }

    /**
     * @param $attribute_id
     * @param $product_id
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function getFeatureValue($attribute_id, $product_id,$elkjopnordic_category='',$key='')
    {
        $sql_db_intance = Db::getInstance(_PS_USE_SQL_SLAVE_);

        $features = $sql_db_intance->executeS('
	        SELECT value FROM ' . _DB_PREFIX_ . 'feature_product pf
	        LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature 
	        AND fl.id_lang = ' . (int)$this->defaultLang . ')
	        LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl 
	        ON (fvl.id_feature_value = pf.id_feature_value 
	        AND fvl.id_lang = ' . (int)$this->defaultLang . ')
	        LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature 
	        AND fl.id_lang = ' . (int)$this->defaultLang . ')
	        ' . Shop::addSqlAssociation('feature', 'f') . '
	        WHERE pf.id_product = ' . (int)$product_id . ' 
	        AND fl.id_feature = "' . (int)$attribute_id . '" 
	        ORDER BY f.position ASC');
        if (isset($features['0']['value'])) {
              if ($attribute_id == '7') {
                    $codes_brand = $sql_db_intance->getValue("SELECT `value_code` FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE `value_label` LIKE '".pSQL($features['0']['value'])."%' AND `category_id` LIKE '".pSQL($elkjopnordic_category)."' AND `attribute_code` = '".pSQL($key)."' ORDER BY `id`  DESC");
                        if($codes_brand){
                           return $codes_brand;
                        } else{
                            return $features['0']['value'];
                        }                      
                
            }
            if ($attribute_id == '10') {
                    $codes_brand = $sql_db_intance->getValue("SELECT `value_code` FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE `value_label` LIKE '".pSQL($features['0']['value'])."%' AND `category_id` LIKE '".pSQL($elkjopnordic_category)."' AND `attribute_code` = '".pSQL($key)."' ORDER BY `id`  DESC");
                        if($codes_brand){
                           return $codes_brand;
                        } else{
                            return $features['0']['value'];
                        }                      
                
            }
            return $features['0']['value'];
        } else {
            return false;
        }
    }

    /**
     * @param $attribute_id
     * @param $product
     * @param int $product_id
     * @return bool|float
     * @throws PrestaShopDatabaseException
     */
    public function getSystemValue($attribute_id, $product, $product_id = 0,$id_lang=0,$elkjopnordic_category='',$key)
    {
        $id_shop = Context::getContext()->shop->id;
        if($id_lang){
            if((int)$id_lang==3){
                $id_shop = 2;
                $product = (array) new Product($product_id, true, null,(int)$id_shop);
            }
            if((int)$id_lang==2){
                $id_shop = 1;
                $product = (array) new Product($product_id, true, null,(int)$id_shop);
            }
            if((int)$id_lang==4){
                $id_shop = 6;
                $product = (array) new Product($product_id, true, null,(int)$id_shop);
            }
            if((int)$id_lang==5){
                $id_shop = 4;
                $product = (array) new Product($product_id, true, null,(int)$id_shop);
            }
            $product[$attribute_id] = $product[$attribute_id][$id_lang];
        }
        if (isset($product[$attribute_id])) {
            $db = Db::getInstance();
            if (strpos('description',$attribute_id)!==false) {
             $content = html_entity_decode($product[$attribute_id]);
                                                $this->stripTags = new Zend_Filter_StripTags();
                                                // <strong> replace to <b>
                                                $content = preg_replace("/<strong(.*?)>(.*?)<\/strong>/", "<b>$2</b>", $content);
                                                // Filtering other tags except 'b', 'br', 'p'
                                                $this->stripTags->setTagsAllowed(['br']);
                                                $this->stripTags->setAttributesAllowed([]);
                                                $content = $this->stripTags->filter($content);
                                                $content = html_entity_decode($content);
                                                $content = str_replace(';','',$content);
                                                $content = str_replace('<br />',"\n",$content);
                                                $content = str_replace('<br >',"\n",$content);
                                                $content = str_replace('<br>',"\n",$content);
                                                $content = str_replace('</br>',"\n",$content);
                                                
                                                return $content;

                
            }
            if ($attribute_id == 'id_manufacturer') {
                if (isset($product['id_manufacturer']) && $product['id_manufacturer']) {
                    $Execute = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'manufacturer` 
                    where `id_manufacturer`=' . (int)$product['id_manufacturer'];
                    $qresult = $db->ExecuteS($Execute);
                    if (isset($qresult['0']["name"])) {
                    $codes_brand = $db->getValue("SELECT `value_code` FROM `"._DB_PREFIX_."cedelkjopnordic_attribute_options` WHERE `value_label` LIKE '".pSQL($qresult['0']["name"])."%'  AND `category_id` LIKE '".pSQL($elkjopnordic_category)."' AND `attribute_code` = '".pSQL($key)."' ORDER BY `id`  DESC");
                        if($codes_brand){
                           return $codes_brand;
                        } else{
                            return $qresult['0']["name"];
                        }
                        
                    }
                }
            }
            if ($attribute_id == 'id_category_default') {
                if (isset($product['id_category_default']) && $product['id_category_default']) {
                    $Execute = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'category_lang` 
                    where `id_category`=' . (int)$product['id_category_default'] . ' 
                    AND `id_lang` = ' . (int)$this->defaultLang;
                    $qresult = $db->ExecuteS($Execute);
                    if (isset($qresult['0']["name"])) {
                        return $qresult['0']["name"];
                    }
                }
            }
            if ($attribute_id == 'id_tax_rules_group') {
                if (isset($product['id_tax_rules_group']) && $product['id_tax_rules_group']) {
                    $Execute = 'SELECT `rate` FROM `' . _DB_PREFIX_ . 'tax_rule` tr 
                    LEFT JOIN `' . _DB_PREFIX_ . 'tax` t on (t.id_tax = tr.id_tax) 
                    where tr.`id_tax_rules_group`=' . (int)$product['id_tax_rules_group'];
                    $qresult = $db->ExecuteS($Execute);
                    if (isset($qresult['0']["rate"])) {
                        return number_format($qresult['0']["rate"], 2);
                    }
                }
            }

            if (isset($product[$attribute_id])) {
                return $product[$attribute_id];
            } else {
                return false;
            }
        } elseif ($attribute_id == 'price_ttc') {
            $id_product_attribute = null;
            if (isset($product['id_product_attribute'])) {
                $id_product_attribute = $product['id_product_attribute'];
            }
            $price_tax_incl = (float) Product::getPriceStatic($product_id, true, $id_product_attribute, 2, null, false, true);
            return $price_tax_incl;
        } else {
            return false;
        }
    }

    /**
     * @param $productToUpload
     * @param $product
     * @param $profileAdditionalInfo
     * @return mixed
     */
    public function setReferenceType($productToUpload, $product, $profileAdditionalInfo)
    {
        $productToUpload['product-reference-type'] = '';
        $productToUpload['product-reference-value'] = '';
        if (isset($profileAdditionalInfo['product_reference_type'])) {
            $referenceType = $profileAdditionalInfo['product_reference_type'];
            if ($referenceType == 'EAN' && isset($product['ean13'])) {
                $productToUpload['product-reference-type'] = 'EAN';
                $productToUpload['EAN'] = $product['ean13'];
            } elseif ($referenceType == 'SHOP_SKU' && isset($product['reference'])) {
                $productToUpload['product-reference-type'] = 'SHOP_SKU';
                $productToUpload['product-reference-value'] = $product['reference'];
            } elseif ($referenceType == 'MPN' && isset($product['reference'])) {
                $productToUpload['product-reference-type'] = 'MPN';
                $productToUpload['product-reference-value'] = $product['reference'];
            } elseif ($referenceType == 'UPC' && isset($product['upc'])) {
                $productToUpload['product-reference-type'] = 'UPC';
                $productToUpload['product-reference-value'] = $product['upc'];
            }
        }
        return $productToUpload;
    }

    /**
     * @param int $product_id
     * @return array|bool
     * @throws PrestaShopDatabaseException
     */
    public function getProductsProfile($product_id = 0)
    {
        $db = Db::getInstance();
        $sql = 'SELECT `id_cedelkjopnordic_profile` FROM `' . _DB_PREFIX_ . 'cedelkjopnordic_profile_products`
            WHERE `id_product` =' . $product_id . ' AND id_shop_profile = '.Context::getContext()->shop->id;
        $response = $db->ExecuteS($sql);
        if (is_array($response) && count($response) && isset($response[0]['id_cedelkjopnordic_profile'])) {
            $profile_id = $response[0]['id_cedelkjopnordic_profile'];
            $profileData = $this->elkjopnordicProfile->getProfileDataById((int)$profile_id);
            $profileData['id'] = $profile_id;
            return $profileData;
        } else {
            return false;
        }
    }

    /**
     * @param $productId
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getProductData($productId)
    {
        try {
            $db = db::getInstance();
            $sql = 'Select `data` from `' . _DB_PREFIX_ . 'cedelkjopnordic_products` where id_product="' . (int)$productId . '"';
            $res = $db->executeS($sql);
            if ($res && isset($res[0]['data'])) {
                if ($res[0]['data'] == '') {
                    return array();
                } else {
                    return json_decode($res[0]['data'], true);
                }
            } else {
                return array();
            }
        } catch (\Exception $e) {
            $this->elkjopnordicHelper->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url'=>'',
                        'Response' => ''
                    )
                ),
                true
            );
            return array();
        }
    }

    /**
     * @param int $product_id
     * @param int $attribute_id
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function productSecondaryImageURL($product_id = 0, $attribute_id = 0)
    {
        $db = Db::getInstance();
        if ($product_id) {
            $additionalAssets = array();
            $default_lang = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));

            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'image` i LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON
             (i.`id_image` = il.`id_image`)';

            if ($attribute_id) {
                $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` ai ON (i.`id_image` = ai.`id_image`)';
                $attribute_filter = ' AND ai.`id_product_attribute` = ' . (int)$attribute_id;
                $sql .= ' WHERE i.`id_product` = ' . (int)$product_id . ' AND il.`id_lang` = ' .
                    (int)$default_lang . $attribute_filter . ' ORDER BY i.`position` ASC';
            } else {
                $sql .= ' WHERE i.`id_product` = ' . (int)$product_id . ' AND il.`id_lang` = ' .
                    (int)$default_lang . ' ORDER BY i.`position` ASC';
            }

            $Execute = $db->ExecuteS($sql);
            $imageType = Configuration::get('ELKJOPNORDIC_IMAGE_TYPE') ? Configuration::get('ELKJOPNORDIC_IMAGE_TYPE') :
                'large';
            $imageType = str_replace('_default', '', $imageType);
            if (version_compare(_PS_VERSION_, '1.7', '>=') === true) {
                $type = ImageType::getFormattedName($imageType);
            } else {
                $type = ImageType::getFormatedName($imageType);
            }
            $product = new Product($product_id);
            $link = new Link;
            if (!empty($Execute)) {
                foreach ($Execute as $image) {
                    $image_url = $link->getImageLink(
                        $product->link_rewrite[$default_lang],
                        $image['id_image'],
                        $type
                    );
                    if (isset($image['cover']) && $image['cover']) {
                        $additionalAssets['Image1'] = (Configuration::get('PS_SSL_ENABLED') ? 'https://' :
                                'http://') . $image_url;
                    } else {
                        $additionalAssets['productSecondaryImageURL'][] = (Configuration::get('PS_SSL_ENABLED') ?
                                'https://' : 'http://') . $image_url;
                    }
                }
            }
            return $additionalAssets;
        }
    }

    /**
     * @param $id_product
     * @param $id_attribute
     * @return mixed|string
     * @throws PrestaShopDatabaseException
     */
    public function getMappedValue($id_product, $id_attribute)
    {
        if (is_numeric($id_attribute)) {
            return $this->getProductAttributes($id_product, $id_attribute);
        } else {
            return $this->getProductSystemAttributes($id_attribute);
        }
    }

    /**
     * @param $product_id
     * @param $id_feature
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function getProductAttributes($product_id, $id_feature)
    {
        $sql_db_intance = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $default_lang = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));
        $features = $sql_db_intance->executeS('
        SELECT name, value, pf.id_feature
        FROM ' . _DB_PREFIX_ . 'feature_product pf
        LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = ' .
            (int)$default_lang . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND
         fvl.id_lang = ' . (int)$default_lang . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = ' .
            (int)$default_lang . ')
        ' . Shop::addSqlAssociation('feature', 'f') . '
        WHERE pf.id_product = ' . (int)$product_id . ' AND pf.id_feature = ' . (int)$id_feature . '
        ORDER BY f.position ASC');
        if (isset($features['0']['value']) && $features['0']['value']) {
            return $features['0']['value'];
        } else {
            return '';
        }
    }

    /**
     * @param $id_attribute
     * @return mixed
     */
    public function getProductSystemAttributes($id_attribute)
    {
        if (isset($this->product_info[$id_attribute]) && $this->product_info[$id_attribute]) {
            $this->product_info[$id_attribute] = strip_tags($this->product_info[$id_attribute]);

            if (strpos($this->product_info[$id_attribute], '.') !== false) {
                $temp = explode('.', $this->product_info[$id_attribute]);
                if (isset($temp['1']) && isset($temp['0']) && is_numeric($temp['1']) && is_numeric($temp['0'])) {
                    $this->product_info[$id_attribute] = number_format($this->product_info[$id_attribute], '2');
                }
            }

            if ($id_attribute == 'id_manufacturer') {
                return $this->product_info['manufacturer_name'];
            } else {
                return $this->product_info[$id_attribute];
            }
        }
    }

    /**
     * @param $product_id
     * @param $id_lang
     * @param string $attribute_value_separator
     * @param string $attribute_separator
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getAttributesResume(
        $product_id,
        $id_lang,
        $attribute_value_separator = ' - ',
        $attribute_separator = ', '
    ) {
        if (!Combination::isFeatureActive()) {
            return array();
        }

        $combinations = Db::getInstance()->executeS('SELECT pa.*, product_attribute_shop.*
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
                WHERE pa.`id_product` = ' . (int)$product_id . '
                GROUP BY pa.`id_product_attribute`');

        if (!$combinations) {
            return false;
        }

        $product_attributes = array();
        foreach ($combinations as $combination) {
            $product_attributes[] = (int)$combination['id_product_attribute'];
        }

        $lang = Db::getInstance()->executeS('SELECT pac.id_product_attribute, GROUP_CONCAT(agl.`id_attribute_group`,
         \'' . pSQL($attribute_value_separator) . '\',al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR \'' .
            pSQL($attribute_separator) . '\') as combinations ,a.id_attribute_group
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND
                 al.`id_lang` = ' . (int)$id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = 
                agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$id_lang . ')
                WHERE pac.id_product_attribute IN (' . implode(',', $product_attributes) . ')
                GROUP BY pac.id_product_attribute');

        foreach ($lang as $k => $row) {
            $temp = explode(',', $row['combinations']);
            $temp3 = array();
            foreach ($temp as $key => $value) {
                $temp1 = explode('-', $value);
                $temp3[trim($temp1['0'])] = trim($temp1['1']);
            }
            $combinations[$k]['combinations'] = $temp3;
        }

        //Get quantity of each variations
        foreach ($combinations as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';

            if (!Cache::isStored($cache_key)) {
                $result = StockAvailable::getQuantityAvailableByProduct(
                    $row['id_product'],
                    $row['id_product_attribute']
                );
                Cache::store(
                    $cache_key,
                    $result
                );
                $combinations[$key]['quantity'] = $result;
            } else {
                $combinations[$key]['quantity'] = Cache::retrieve($cache_key);
            }
        }

        return $combinations;
    }

    /**
     * @param $productToUpload
     * @param $product_combination
     * @param $profileData
     * @return array
     */
    public function processCombinations($productToUpload, $product_combination, $profileData)
    {
        $attribute_mappings = $profileData['profileAttributes'];
        if (is_array($attribute_mappings)) {
            $attribute_mappings = array_filter($attribute_mappings);
        }
        
        $variant_attribute_key = false;
        foreach($attribute_mappings as $k => $attribute_mapping){
             if(strpos($attribute_mapping['mapping'],'attribute')!==false)
             $variant_attribute_key = $k;
        }        

        $variant_product = array();
        $variant_product_list = array();
        $variant_size_value = '';
        $variant_color_value = '';
        if (isset($attribute_mappings['variant-size-value']['mapping']) &&
            $attribute_mappings['variant-size-value']['mapping']) {
            $mapped_expression = explode('-', $attribute_mappings['variant-size-value']['mapping']);
            if ($mapped_expression[0] == 'attribute') {
                $variant_size_value = $mapped_expression[1];
            }

            if (isset($attribute_mappings['variant-colour-value']['mapping']) &&
                $attribute_mappings['variant-colour-value']['mapping']) {
                $mapped_expression = explode('-', $attribute_mappings['variant-colour-value']['mapping']);
                if ($mapped_expression[0] == 'attribute') {
                    $variant_color_value = $mapped_expression[1];
                }
            }
            $increment_id = 0;
           
            foreach ($product_combination as $combination) {
                $variant_product[$increment_id] = array();
                if (isset($combination['combinations'])) {
                    foreach ($combination['combinations'] as $key => $value) {
                        if ($variant_size_value == $key) {
                            $variant_product[$increment_id]['variant-size-value'] = $value;
                        }
                        if ($variant_color_value == $key) {
                            $variant_product[$increment_id]['variant-colour-value'] = $value;
                        }
                    }
                    if (isset($variant_product[$increment_id]['variant-size-value'])) {
                        if (isset($combination['reference'])) {
                            $variant_product_list[$increment_id] = array_merge(
                                $variant_product[$increment_id],
                                $productToUpload
                            );
                            $variant_product_list[$increment_id]['Shop_SKU'] = $combination['reference'];
                            $variant_product_list[$increment_id]['VariantId'] = $combination['reference'];
                            if (isset($profileData['profileAdditionalInfo'])) {
                                $variant_product_list[$increment_id] = $this->setReferenceType(
                                    $variant_product_list[$increment_id],
                                    $combination,
                                    $profileData['profileAdditionalInfo']
                                );
                            }
                        }
                    }
                }

                if (isset($variant_product_list[$increment_id]['variant-size-value'])) {
                    if (!isset($variant_product_list[$increment_id]['variant-colour-value'])) {
                        $variant_product_list[$increment_id]['variant-colour-value'] = '';
                    }
                    $increment_id++;
                }
            }
        }

        if (empty($variant_product_list)) {
            $variant_product_list = array();
                if(!empty($product_combination)) {
                   foreach($product_combination as  $product_combi) {
                  
                       if(!empty($product_combi['combinations'])){
                        $productToUploadTemp = $productToUpload;
                        $productToUploadTemp['EAN'] = $product_combi['ean13'];
                        $productToUploadTemp['Shop_SKU'] = $product_combi['reference'];
                        if($variant_attribute_key){
                                $productToUploadTemp['VariantId'] = $product_combi['id_product'];
                                $productToUploadTemp[$variant_attribute_key] = implode(' ',$product_combi['combinations']);
                        } else {
                                $productToUploadTemp['VariantId'] ='';
                        }
                        
                   
                        if(isset($productToUploadTemp['Title_en']) && $productToUploadTemp['Title_en'])
                        $productToUploadTemp['Title_en'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 2,1), 0, 70);

                        if(!isset($productToUploadTemp['Title_en']))
                            $productToUploadTemp['Title_en'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 2,1), 0, 70);
                            
                        if(isset($productToUploadTemp['Title_se']) && $productToUploadTemp['Title_se'])
                        $productToUploadTemp['Title_se'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 2,1), 0, 70);

                        if(isset($productToUploadTemp['Title_dk']) && $productToUploadTemp['Title_dk'])
                        $productToUploadTemp['Title_dk'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 3,2), 0, 70);
                        
                        if(isset($productToUploadTemp['Title_no']) && $productToUploadTemp['Title_no'])
                        $productToUploadTemp['Title_no'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 5,4), 0, 70);
                        
                        if(isset($productToUploadTemp['Title_fi']) && $productToUploadTemp['Title_fi'])
                        $productToUploadTemp['Title_fi'] = substr(self::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], 4,6), 0, 70);

                        if(isset($productToUploadTemp['Article_name']))
                        $productToUploadTemp['Article_name'] = substr($productToUploadTemp['Article_name'] , 0, 70);

                      /* if(isset($productToUploadTemp['Title_no']) && $productToUploadTemp['Title_no'])
                        $productToUploadTemp['Title_no'] = Product::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], (int)$this->defaultLang);

                        if(isset($productToUploadTemp['Title_fi']) && $productToUploadTemp['Title_fi'])
                        $productToUploadTemp['Title_fi'] = Product::getProductName($product_combi['id_product'], $product_combi['id_product_attribute'], (int)$this->defaultLang);*/

                       if (isset($productToUpload['Shop_Sku'])) {
                            $productToUploadTemp['VariantId'] = $productToUpload['Shop_Sku'];
                        } else {
                            $productToUploadTemp['VariantId'] = "";
                        }
                       
                        $images_array = $this->productSecondaryImageURL($product_combi['id_product'],$product_combi['id_product_attribute']);
                      //  echo '<pre>';
//print_r($images_array);
//print_r($productToUploadTemp);
                        
                         $i = 0; 
                         
                            if (isset($images_array['productSecondaryImageURL'][$i])) {
                                $productToUploadTemp["Image".($i+1)] = $images_array['productSecondaryImageURL'][$i];
                            }
                            for ($i = 2; $i <= 4; $i++) {
                         
                            if (isset($productToUploadTemp["Image".($i)])) {
                                $productToUploadTemp["Image".($i)]='';
                            }
                                
                        }
                       // print_r($productToUploadTemp);
                        $variant_product_list[] = $productToUploadTemp;
                       }
                       
                   }
                } else {
                        if(isset($productToUpload['Title_en']) && $productToUpload['Title_en'])
                        $productToUpload['Title_en'] = substr($productToUpload['Title_en'], 0, 70);


                        if(isset($productToUpload['Title_no']) && $productToUpload['Title_no'])
                        $productToUpload['Title_no'] = substr($productToUpload['Title_no'], 0, 70);


                        if(isset($productToUpload['Title_fi']) && $productToUpload['Title_fi'])
                        $productToUpload['Title_fi'] = substr($productToUpload['Title_fi'], 0, 70);


                        if(isset($productToUpload['Title_se']) && $productToUpload['Title_se'])
                        $productToUpload['Title_se'] = substr($productToUpload['Title_se'], 0, 70);


                        if(isset($productToUpload['Title_dk']) && $productToUpload['Title_dk'])
                        $productToUpload['Title_dk'] = substr($productToUpload['Title_dk'], 0, 70);

                       if(isset($productToUpload['Article_name']))
                        $productToUpload['Article_name'] = substr($productToUpload['Article_name'] , 0, 70);
                        
                        
                        
                        $productToUpload['VariantId'] = "";
                        
                        $variant_product_list[] = $productToUpload;
                }
        }
                     //     echo '<pre>';
//print_r($images_array);
//print_r($productToUploadTemp);die;
        return $variant_product_list;
    }

    public static function getProductName($id_product, $id_product_attribute = null, $id_lang = null,$id_shop = 1)
    {
        // use the lang in the context if $id_lang is not defined
        if (!$id_lang) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        // creates the query object
        $query = new DbQuery();

        // selects different names, if it is a combination
        if ($id_product_attribute) {
            $query->select('IFNULL(CONCAT(pl.name, \' \', GROUP_CONCAT("" , \' - \', al.name SEPARATOR \', \')),pl.name) as name');
        } else {
            $query->select('DISTINCT pl.name as name');
        }

        // adds joins & where clauses for combinations
        if ($id_product_attribute) {
            $query->from('product_attribute', 'pa');
           // $query->join(Shop::addSqlAssociation('product_attribute', 'pa'));
            $query->innerJoin('product_lang', 'pl', 'pl.id_product = pa.id_product AND pl.id_lang = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl'));
            $query->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
            $query->leftJoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
            $query->leftJoin('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = ' . (int) $id_lang);
            $query->leftJoin('attribute_shop', 'ats', 'ats.id_attribute = atr.id_attribute AND ats.id_shop = ' . (int) $id_shop);
            $query->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = ' . (int) $id_lang);
            $query->where('pa.id_product = ' . (int) $id_product . ' AND pa.id_product_attribute = ' . (int) $id_product_attribute);
        } else {
            // or just adds a 'where' clause for a simple product

            $query->from('product_lang', 'pl');
            $query->where('pl.id_product = ' . (int) $id_product);
            $query->where('pl.id_lang = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl'));
        }

        return Db::getInstance()->getValue($query);
    }
    /**
     * @param array $productToUpload
     * @param array $categoryAttributes
     * @return array
     */
    public function validateCategoryProduct($productToUpload = array(), $categoryAttributes = array())
    {
        $errors = array();
        if (count($productToUpload) && count($categoryAttributes)) {
            foreach ($productToUpload as $categry => $products) {
                foreach ($products as $product) {
                    if (!empty($product) && isset($categoryAttributes[$categry])) {
                        $productAttributes = isset($categoryAttributes[$categry]) ? $categoryAttributes[$categry] :
                            array();
                        foreach ($productAttributes as $attribute) {
                            if (isset($attribute['type']) &&
                                !in_array(trim($attribute['type']), array('DATE', 'MEDIA'))) {
                                // first checking validation
                                $attribute_code = trim($product[$attribute['code']]);
                                if (isset($attribute['code'])
                                    && isset($attribute['validations'])
                                    && ($attribute['validations'])
                                    && isset($product[$attribute['code']])
                                    && !empty($attribute_code)
                                ) {
                                    $validations = explode(',', $attribute['validations']);
                                    foreach ($validations as $validation) {
                                        $validation = explode('|', $validation);
                                        if (isset($validation['0']) && ($validation['0'] == 'MIN_LENGTH') &&
                                            isset($validation['1'])) {
                                            if (Tools::strlen($product[$attribute['code']]) < $validation['1']) {
                                                $errors[] = $product['sku'] . " : " . $attribute['label'] .
                                                    " minimum length should be " . $validation['1'];
                                            }
                                        } elseif (isset($validation['0']) && ($validation['0'] == 'MAX_LENGTH') &&
                                            isset($validation['1'])) {
                                            if (Tools::strlen($product[$attribute['code']]) > $validation['1']) {
                                                $errors[] = $product['sku'] . " : " . $attribute['label'] .
                                                    " maximum length should be " . $validation['1'];
                                            }
                                        }
                                    }
                                }
                                $attribute_code = trim($product[$attribute['code']]);
                                if (isset($attribute['code'])
                                    && isset($attribute['required'])
                                    && ($attribute['required'])
                                    && (!isset($product[$attribute['code']])
                                        || (isset($product[$attribute['code']]) && empty($attribute_code)))
                                ) {
                                    $errors[] = $product['sku'] . " : " . $attribute['label'] .
                                        " is not mapped or have empty value in product";
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = "No product data found to validate";
        }
        return $errors;
    }

    /**
     * @param $ids
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function updateOffers($ids)
    {
        $errors = array();
        $successes = array();
        $validation_error = array();
        try {
            $offers = array();
            foreach ($ids as $product_id) {
                $offerToUpload = array();
                $profileData = $this->getProductsProfile($product_id);
                if ($profileData) {
                    $elkjopnordic_category = $profileData['profileElkjopnordicCategories'];
                    $count = count($elkjopnordic_category);
                    $elkjopnordic_category = $elkjopnordic_category['level_' . $count];
                    $context = Context::getContext();
                    $context->cart = new Cart();
                    $product = new Product(
                        $product_id,
                        true,
                        $this->defaultLang,
                        (int)$context->shop->id,
                        $context
                    );

                    $product_data = (array)$product;
                     $s = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products` WHERE `id_product`='" .
            (int)$product_id . "' AND `product_feed_status`='1'";//AND `elkjopnordic_status` ='Live'
                    $is_created = Db::getInstance()->getValue($s);
                    
                    if($is_created)
                    continue;
                    
                    $offerToUpload = $this->prepareSingleOffer(
                        $product_id,
                        $product_data,
                        $offerToUpload,
                        $profileData
                    );

                    $offerToUpload = $this->prepareVariantOffers($product_id, $product, $offerToUpload, $profileData);

                    foreach ($offerToUpload as $elkjopnordic_offer) {
                        if (!isset($offers[$elkjopnordic_category]) || !is_array($offers[$elkjopnordic_category])) {
                            $offers[$elkjopnordic_category] = array();
                        }
                        array_push($offers[$elkjopnordic_category], $elkjopnordic_offer);
                    }
                } else {
                    $validation_error[] = 'Profile Not mapped For Product' . $product_id;
                }

                $errors = array_merge($errors, $validation_error);
            }
           
            if (!empty($offers)) {
                foreach ($offers as $offer) {
                    $this->elkjopnordicHelper->log(
                        __METHOD__,
                        'Info',
                        'Offer Updated',
                        json_encode(
                            array(
                                'url'=>'',
                                'params'=>$offers,
                                'Response' => 'sdfdgdfhrfgh'
                            )
                        ),
                        true
                    );
                    $result = $this->feedRequest($offer, 'offers');

                    if (isset($result['success']) && $result['success']) {
                        $successes[] = $result['message'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();

            $this->elkjopnordicHelper->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url'=>'',
                        'params'=>$offers,
                        'Response' => $result
                    )
                ),
                true
            );
        }

        return array(
            'success' => $successes,
            'error' => $errors,
        );
    }

    /**
     * @param $product_id
     * @param $product_data
     * @param $offerToUpload
     * @param $profileData
     * @param string $type
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function prepareSingleOffer($product_id, $product_data, $offerToUpload, $profileData, $type = 'UPDATE')
    {
        $context = Context::getContext()->cloneContext();

        $context->currency->id = Configuration::get('ELKJOPNORDIC_CURRENCY_STORE');
        $specific_price_output = false;

        $elkjopnordic_settings = isset($profileData['profileAdditionalInfo']) ?
            $profileData['profileAdditionalInfo'] : array();
        if (isset($elkjopnordic_settings['product_reference_type']) && !empty($elkjopnordic_settings['product_reference_type'])) {
            $product_reference_type = $elkjopnordic_settings['product_reference_type'];
        } else {
            $product_reference_type = 'SHOP_SKU';
        }

        $min_quantity_alert = isset($elkjopnordic_settings['min_quantity_alert']) ?
            $elkjopnordic_settings['min_quantity_alert'] : '';
        $state = isset($elkjopnordic_settings['product_offer_state']) ?
            $elkjopnordic_settings['product_offer_state'] : '';
        $shippingtime = isset($elkjopnordic_settings['shippingtime']) ?
            $elkjopnordic_settings['shippingtime'] : 'false';
        $available_start_date = '';
        $available_end_date = '';
        $logistic_class = isset($elkjopnordic_settings['product_logistic_class']) ?
            $elkjopnordic_settings['product_logistic_class'] : '';

        $offer_prices = SpecificPrice::getSpecificPrice(
            (int) $product_id,
           (int) Context::getContext()->shop->id,
            0,
            0,
            0,
            1,
            0,
            0,
            0,
            null
        );
        $discount_price = '';                    
        $offer_specific_prices = self::getMarketplacePrice($product_id, 0, true,$context,$specific_price_output);               
        
        $discount_start_date = '';
        $discount_end_date = '';
        $lead_time_to_ship = '1';
        $best_before_date = '';
        $expiry_date = '';
        $sku = isset($product_data['reference']) ? $product_data['reference'] : '';
        if ($product_reference_type == 'SHOP_SKU' || $product_reference_type == 'MPN') {
            $product_id_value = $sku;
        } elseif ($product_reference_type == 'EAN') {
            $product_id_value = $product_data['ean13'];
        } elseif ($product_reference_type == 'UPC') {
            $product_id_value = $product_data['upc'];
        }

        $internal_description = isset($elkjopnordic_settings['internal-description']) ?
            $elkjopnordic_settings['internal-description'] : '';
        $price_additional_info = "";

        if (isset($profileData['profileAttributes']['price']['mapping']) &&
            $profileData['profileAttributes']['price']['mapping'] != "") {
            $v_price =  self::getMarketplacePrice($product_id, 0, false,$context,$specific_price_output);
            /*$this->getMappedValuesForOffer(
                $product_id,
                $product_data,
                $profileData['profileAttributes']['price']
            );*/
        } else {
            $v_price = 0;
        }
        if( $v_price > $offer_specific_prices){
        $discount_price = $offer_specific_prices;
        $discount_start_date = date("Y-m-d",$offer_prices['from']);
        $discount_end_date = date("Y-m-d",$offer_prices['to']);
        }
        

        if (isset($elkjopnordic_settings['price-variant-type'])) {
            $price_variant_type = $elkjopnordic_settings['price-variant-type'];
            $price_variant_amount = 0;
            if (isset($elkjopnordic_settings['price-variant-amount'])) {
                $price_variant_amount = (float)$elkjopnordic_settings['price-variant-amount'];
            }
            $v_price =  self::getMarketplacePrice($product_id, 0, false,$context,$specific_price_output);
            $v_price = $this->setPriceVariantAmount(
                (int)$price_variant_type,
                (float)$price_variant_amount,
                (float)$v_price
            );
        }


        $offer_description = isset($elkjopnordic_settings['description']) ?
            $elkjopnordic_settings['description'] : '';


        if (isset($profileData['profileAttributes']['quantity']['mapping']) &&
            $profileData['profileAttributes']['quantity']['mapping'] != "") {
            $quantity = $this->getMappedValuesForOffer(
                $product_id,
                $product_data,
                $profileData['profileAttributes']['quantity']
            );
        } else {
            $quantity = StockAvailable::getQuantityAvailableByProduct($product_id);
        }

		if(Context::getContext()->shop->id==4) {
			if($v_price >= 350) {
				$quantity =0;
			}
		}

        $offerToUpload = array(
            'sku' => $sku,
            'product-id' => $product_id_value,
            'product-id-type' => $product_reference_type,
            'description' => $offer_description,
            'internal-description' => $internal_description,
            'price' => $v_price,
            'price-additional-info' => $price_additional_info,
            'quantity' => ($quantity>=0)?$quantity:0,
            'min-quantity-alert' => $min_quantity_alert,
            'state' => $state,
            'available-start-date' => $available_start_date,
            'available-end-date' => $available_end_date,
            'discount-price' => $discount_price,
            'discount-start-date' => $discount_start_date,
            'discount-end-date' => $discount_end_date,
            'leadtime-to-ship' => $lead_time_to_ship,
            'update-delete' => $type,
            'favorite-rank' => '',
            'shippingtime' => $shippingtime
        );

        return $offerToUpload;
    }

    /**
     * @param $product_id
     * @param $product_data
     * @param $value
     * @return bool|float|string
     * @throws PrestaShopDatabaseException
     */
     
    public static function getMarketplacePrice(
        $id_product,
        $id_product_attribute,
        $usereduc,
        $context = null,
        &$specific_price_output=null
    ){
        if(!$context)
            $context = Context::getContext()->cloneContext();
        $id_currency = Validate::isLoadedObject($context->currency)
            ? (int)$context->currency->id
            : (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $id_group = Configuration::get('CEDBOL_CUSTOMER_GROUP_ID');

       
            return Product::priceCalculation(
                $context->shop->id,
                $id_product,
                $id_product_attribute,
                $context->country->id,
                0,
                0,
                $id_currency,
                $id_group,
                1,
                true,
                2,
                false,
                $usereduc,
                true,
                $specific_price_output,
                true,
                null,
                false,
                null,
                0
            );
        
    }
     
    public function getMappedValuesForOffer($product_id, $product_data, $value)
    {
        $mapped_expression = explode('-', $value['mapping']);
        $attr_val = '';
        if (isset($mapped_expression['0'])
            && in_array($mapped_expression['0'], array('system', 'attribute', 'feature'))) {
            $attr_type = $mapped_expression['0'];
            $attribute_id = str_replace($attr_type . '-', "", $value['mapping']);
            switch ($attr_type) {
                case 'attribute':
                    $attr_val = $this->getAttributeValue($attribute_id, $product_id);
                    break;
                case 'feature':
                    $attr_val = $this->getFeatureValue($attribute_id, $product_id);
                    break;
                case 'system':
                    $attr_val = $this->getSystemValue($attribute_id, $product_data, $product_id);
                    break;
                default:
                    $attr_val = $attribute_id;
                    break;
            }
        }

        if ($attr_val == '') {
            if (isset($value['default_value'])) {
                $attr_val = $value['default_value'];
            }
        }
        return $attr_val;
    }

    /**
     * @param $product_id
     * @param Product $product
     * @param $offerToUpload
     * @param $profile_info
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function prepareVariantOffers($product_id, Product $product, $offerToUpload, $profile_info)
    {
        $variantOfferToUpload = array();
        if ($product->getAttributeCombinations($this->defaultLang)) {
            $product_combination = $this->getAttributesResume($product_id, $this->defaultLang);
            $variantOfferToUpload = $this->processCombinationForOffers(
                $offerToUpload,
                $product_combination,
                $profile_info,$product_id,(array)$product
            );
        } else {
            $variantOfferToUpload[0] = $offerToUpload;
        }
        return $variantOfferToUpload;
    }

    /**
     * @param $offerToUpload
     * @param $product_combination
     * @param $profileData
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function processCombinationForOffers($offerToUpload, $product_combination, $profileData,$product_id=0,$product_data)
    {
        $attribute_mappings = $profileData['profileAttributes'];
        if (is_array($attribute_mappings)) {
            $attribute_mappings = array_filter($attribute_mappings);
        }
        $variant_product = array();
        $variant_product_list = array();
            $increment_id = 0;
            foreach ($product_combination as $combination) {
            
                $variant_product[$increment_id] = array();
                if (isset($combination['combinations']) && !empty($combination['combinations'])) {
                    
                       $variant_product_list[$increment_id] =   $this->prepareSingleOffer(
                        $product_id,
                        $product_data,
                        $offerToUpload,
                        $profileData
                    );
                         /*if (isset($profileData['profileAttributes']['price']['mapping']) &&
                           $profileData['profileAttributes']['price']['mapping'] != "") {
                            $variant_product_list[$increment_id]['price'] = /*$this->getMappedValuesForOffer(
                                $combination['id_product'],
                                $combination,
                                $profileData['profileAttributes']['price']
                            ); */
                            $variant_product_list[$increment_id]['price'] =  Product::getPriceStatic($product_id, true, (int)$combination['id_product_attribute'], 2, null, false, false);

                             $offer_prices = SpecificPrice::getSpecificPrice(
                                (int) $product_id,
                               (int) Context::getContext()->shop->id,
                                0,
                                0,
                                0,
                                1,
                                (int)$combination['id_product_attribute'],
                                0,
                                0,
                                null
                            );
                    
                            $offer_specific_prices = Product::getPriceStatic(
                                                    (int) $product_id,
                                                    true,
                                                    (int)$combination['id_product_attribute'],
                                                    2
                                                );  

                            if( $variant_product_list[$increment_id]['price'] > $offer_specific_prices){
                                $variant_product_list[$increment_id]['discount-price'] = $offer_specific_prices;
                                
                                if($offer_prices['from']=='0000-00-00 00:00:00' || $offer_prices['to']=='0000-00-00 00:00:00'){
                                 $offer_prices['from']=date("Y-m-d",strtotime("-1 month"));
                                 $offer_prices['to']=date("Y-m-d",strtotime("+1 year"));
                                } else{
                                $offer_prices['to']=date("Y-m-d", strtotime($offer_prices['to']));
                                $offer_prices['from']=date("Y-m-d", strtotime($offer_prices['from']));
                                }
                              
                                $variant_product_list[$increment_id]['discount-start-date'] = $offer_prices['from'];
                                $variant_product_list[$increment_id]['discount-end-date']=$offer_prices['to'];
                            }
       

                            if (isset($profileData['profileAdditionalInfo']['price-variant-type'])) {
                                $price_variant_type = $profileData['profileAdditionalInfo']['price-variant-type'];
                                $price_variant_amount = 0;
                                if (isset($profileData['profileAdditionalInfo']['price-variant-amount'])) {
                                    $price_variant_amount = (int)$profileData['profileAdditionalInfo']
                                    ['price-variant-amount'];
                                }
                                $variant_product_list[$increment_id]['price'] = $this->setPriceVariantAmount(
                                    (int)$price_variant_type,
                                    (float)$price_variant_amount,
                                    (float)$variant_product_list[$increment_id]['price']
                                );
                            }
                      //  }

                        if (isset($profileData['profileAttributes']['quantity']['mapping']) &&
                            $profileData['profileAttributes']['quantity']['mapping'] != "") {
                            $variant_product_list[$increment_id]['quantity'] = $this->getMappedValuesForOffer(
                                $combination['id_product'],
                                $combination,
                                $profileData['profileAttributes']['quantity']
                            );
                            ($variant_product_list[$increment_id]['quantity']>=0)?$variant_product_list[$increment_id]['quantity']:0;
                        } else {
                            $variant_product_list[$increment_id]['quantity'] =
                                StockAvailable::getQuantityAvailableByProduct($product_id,(int)$combination['id_product_attribute']);
                                ($variant_product_list[$increment_id]['quantity']>=0)?$variant_product_list[$increment_id]['quantity']:0;
                        }

					   if(Context::getContext()->shop->id==4) {
							if($variant_product_list[$increment_id]['price'] >= 350) {
								 $variant_product_list[$increment_id]['quantity'] = 0;
							}
						}  
                        $sku = isset($combination['reference']) ? $combination['reference'] : '';
                        $variant_product_list[$increment_id]['sku'] = $sku;
                        
                        $product_reference_type = isset($profileData['profileAdditionalInfo']['product_reference_type']) ?
                            $profileData['profileAdditionalInfo']['product_reference_type'] : '';
                        $product_id_value = '';
                        if ($product_reference_type == 'SHOP_SKU' || $product_reference_type == 'MPN') {
                            $product_id_value = $sku;
                        } elseif ($product_reference_type == 'EAN') {
                            $product_id_value = $combination['ean13'];
                        } elseif ($product_reference_type == 'UPC') {
                            $product_id_value = $combination['upc'];
                        }
                        $variant_product_list[$increment_id]['product-id'] = $product_id_value;
                       
                        $increment_id++;
                    
                }
            
        }
        if (empty($variant_product_list)) {
            $variant_product_list[0] = $offerToUpload;
        }

        return $variant_product_list;
    }

    /**
     * @param $price_variant_type
     * @param $price_variant_amount
     * @param $price
     * @return float|int
     */
    public function setPriceVariantAmount($price_variant_type, $price_variant_amount, $price)
    {
    
        if ($price_variant_type == '2' || $price_variant_type == 2) {
            $price =  $price + $price_variant_amount ;
        } elseif ($price_variant_type == '3' || $price_variant_type == 3) {
            $price = $price - $price_variant_amount;
        } elseif ($price_variant_type == '4' || $price_variant_type == 4) {
            $price = $price + ($price_variant_amount * 0.01 * $price);
        } elseif ($price_variant_type == '5' || $price_variant_amount == 5) {
            $price = $price - ($price_variant_amount * 0.01 * $price);
        }
       
        return $price;
    }

    /**
     * @param null $feedId
     * @param string $url
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getFeeds($feedId = null, $url = 'products')
    {
        $cedelkjopnordicHelper = new CedElkjopnordicHelper();
        $response = array();
        try {
            if ($feedId != null) {
                $url = $url . '/imports/' . $feedId;
            }
            $response = $cedelkjopnordicHelper->WGetRequest($url, array(), 'json');

            if (isset($response['success']) && $response['success']) {
                if (json_decode($response['response'], true)) {
                    return json_decode($response['response'], true);
                }
                return $response['response'];
            } else {
                return array();
            }
        } catch (Exception $e) {
            $cedelkjopnordicHelper->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url'=>'',
                        'params'=>'',
                        'Response' => $response
                    )
                ),
                true
            );
        };
            return array();
    }


    /**
     * @param $feedId
     * @param string $url
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getFeedsErrors($feedId, $url = 'products')
    {
        $response = array();
        $cedelkjopnordicHelper = new CedElkjopnordicHelper();
        try {
            if ($feedId != null) {
                if($url=='products')
                $url = $url . '/imports/' . $feedId . '/transformation_error_report';
                else
                $url = $url . '/imports/' . $feedId . '/error_report';
            }
           
            $response = $cedelkjopnordicHelper->WGetRequest($url, array(), 'json');
            if (isset($response['success']) && $response['success']) {
                if (json_decode($response['response'], true)) {
                    return json_decode($response['response'], true);
                }
                return $response['response'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $cedelkjopnordicHelper->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url'=>$url,
                        'Response' => $response
                    )
                ),
                true
            );
            $cedelkjopnordicHelper->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url'=>$url,
                        'Response' => $response
                    )
                ),
                true
            );

            return false;
        }
    }

    /**
     * @param $feed_id
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getFeedById($feed_id)
    {
        if ($feed_id) {
            $db = Db::getInstance();
            $result = $db->ExecuteS("SELECT * FROM `" . _DB_PREFIX_ .
                "cedelkjopnordic_products_feed` where `import_id`='" . $feed_id . "'");
            if (is_array($result) && count($result)) {
                return $result['0'];
            } else {
                return false;
            }
        }
    }

    /**
     * @param $feed_id
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getPriceFeedById($feed_id)
    {
        if ($feed_id) {
            $db = Db::getInstance();
            $result = $db->ExecuteS("SELECT * FROM `" . _DB_PREFIX_ .
                "cedelkjopnordic_price_feed` WHERE `import_id`='" . $feed_id . "' AND id_shop='".Context::getContext()->shop->id."'");
            if (is_array($result) && count($result)) {
                return $result['0'];
            } else {
                return false;
            }
        }
    }

    /**
     * @param $data
     * @param string $type
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function feedRequest($data, $type = 'products')
    {
      
        $cedelkjopnordicHelper = new CedElkjopnordicHelper();
        $feed_path = _PS_MODULE_DIR_ . "cedelkjopnordic/product_feed/request/" . $type;

        if (!is_dir($feed_path)) {
            mkdir($feed_path, 0777, true);
        }
        switch ($type) {
            case 'products':
                $file_path = $feed_path . '/' . time() . 'product_feed.csv';
                $feed_url = 'products/imports';
                break;
            case 'offers':
                $file_path = $feed_path . '/' . time() . 'offer_feed.csv';
                $feed_url = 'offers/imports?import_mode=NORMAL';
                break;
        }
        $headers = array();
        
        try {
            $file = fopen($file_path, 'w');
            foreach ($data as $row) {
                ksort($row);
                if(isset($row['CategoryIdentifier']))
                    unset($row['CategoryIdentifier']);
                if (count($headers) == 0) {
                    $headers = array_keys($row);
                    sort($headers);
                    fputcsv($file, $headers);
                    
                    fputcsv($file, $row);
                } else {
                
                    fputcsv($file, $row);
                }
            }
            fclose($file);
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
         $file_name = basename($file_path);

        $feed_final_path = _PS_BASE_URL_ . __PS_BASE_URI__ .
            "modules/cedelkjopnordic/product_feed/request/$type/" . $file_name;
            
        $response = $cedelkjopnordicHelper->WPostRequest($feed_url, array('file' => $file_path, 'import_mode' => 'NORMAL'));
       
        if (isset($response['success']) && $response['success']) {
            $response = $response['response'];
            if (json_decode($response, true)) {
                $response = json_decode($response, true);
            }
            if (isset($response['import_id']) && $response['import_id']) {
                $this->syncFeed($response['import_id'], $type, $feed_final_path);
                return array('success' => true, 'message' => $response['import_id']);
            } else {
                return array('success' => false, 'message' => 'Some Error While Procesing Feed.'.$feed_final_path);
            }
        } else {
            return array('success' => false, 'message' => $response['message']);
        }
    }

    /**
     * @param $import_id
     * @param string $type
     * @param string $feed_file
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function syncFeed($import_id, $type = 'products', $feed_file = '')
    {
        $db = Db::getInstance();
        $res_path = _PS_MODULE_DIR_ . 'cedelkjopnordic/product_feed/response/' . $type;
        if (!is_dir($res_path)) {
            mkdir($res_path, 0777, true);
        }
        $feed = $this->getFeeds($import_id, $type);
        $shop_id = Context::getContext()->shop->id;
        if (isset($feed['import_id']) && $feed['import_id'] != 0) {
            $feed['error_file'] = '';
            if ($type == 'products') {
                if (isset($feed['has_transformation_error_report']) && $feed['has_transformation_error_report']) {
                    $error_report_file = $res_path . '/' . $import_id . '.csv';
                    $error_report = $this->getFeedsErrors($import_id, 'products');
                    
                    file_put_contents($error_report_file, $error_report);

                    $feed['error_file'] = _PS_BASE_URL_ . __PS_BASE_URI__ .
                        'modules/cedelkjopnordic/product_feed/response/products/' . $import_id . '.csv';
                }
                $feed['feed_file'] = $feed_file;
                $feed['id_shop'] = $shop_id;
                $columns = array(
                    'date_created',
                    'has_error_report',
                    'has_new_product_report',
                    'has_transformation_error_report',
                    'has_transformed_file',
                    'import_id',
                    'import_status',
                    'shop_id',
                    'transform_lines_in_error',
                    'transform_lines_in_success',
                    'transform_lines_read',
                    'transform_lines_with_warning',
                    'error_file',
                    'feed_file',
                    'id_shop'
                );
                $is_feed_exist = $this->isFeedExist($import_id, 'products');
                if ($is_feed_exist) {
                    $sql = "UPDATE `" . _DB_PREFIX_ . "cedelkjopnordic_products_feed` SET ";
                } else {
                    $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedelkjopnordic_products_feed` SET ";
                }
                foreach ($feed as $key => $value) {
                    if (!in_array($key, $columns)) {
                        continue;
                    }
                    if ($key == 'date_created') {
                        $value = explode('T', $value);
                        $value = $value['0'];
                    }
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $sql .= " `" . $key . "`='" . $value . "',";
                }
                $sql = rtrim($sql, ',');
                if ($is_feed_exist) {
                    $sql .= " WHERE `import_id`='" .pSQL($feed['import_id'] ). "'";
                }
                $db->execute($sql);
            }
            if ($type == 'offers') {
                $feed['error_file'] = '';
                if (isset($feed['has_error_report']) && $feed['has_error_report']) {
                    $error_report_file = $res_path . '/' . $import_id . '.csv';
                    $error_report = $this->getFeedsErrors($import_id, 'offers');
                   
                    file_put_contents($error_report_file, $error_report);

                    $feed['error_file'] = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/cedelkjopnordic/product_feed/response/offers/' .
                        $import_id . '.csv';
                }
                $feed['feed_file'] = $feed_file;
                $feed['id_shop'] = Context::getContext()->shop->id;
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
                    'feed_file',
                    'id_shop'
                );
                $is_feed_exist = $this->isFeedExist($import_id, 'offers');
                if ($is_feed_exist) {
                    $sql = "UPDATE `" . _DB_PREFIX_ . "cedelkjopnordic_offers_feed` SET ";
                } else {
                    $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedelkjopnordic_offers_feed` SET ";
                }
                foreach ($feed as $key => $value) {
                    if (!in_array($key, $columns)) {
                        continue;
                    }
                    if ($key == 'date_created') {
                        $value = explode('T', $value);
                        $value = $value['0'];
                    }
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $sql .= " `" . $key . "`='" . $value . "',";
                }
                $sql = rtrim($sql, ',');
                if ($is_feed_exist) {
                    $sql .= " WHERE `import_id`='" . pSQL($feed['import_id']) . "'";
                }
                
                $db->execute($sql);
            }
            return array(
                'success' => true,
                'message' => "Feed $import_id Synced Successfully"
            );
        }
        return array(
            'success' => false,
            'message' => "Feed $import_id : " . isset($feed['message']) && is_string($feed['message']) ?
                $feed['message'] : 'Failed to sync feed'
        );
    }

    /**
     * @param $import_id
     * @param string $type
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function isFeedExist($import_id, $type = 'products')
    {
        $db = Db::getInstance();
        if ($type == 'products') {
            $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products_feed`
             WHERE `import_id`='" . pSQL($import_id) ."' AND id_shop='".(int)Context::getContext()->shop->id."'";
        } else {
            $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_offers_feed` 
            WHERE `import_id`='" . pSQL($import_id ). "' AND id_shop='".(int)Context::getContext()->shop->id."'";
        }
        $query = $db->executeS($sql);
        if (count($query) && isset($query[0]['id'])) {
            return true;
        }
        return false;
    }
    /**
     * @param $product_ids
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function syncStatus($product_ids)
    {
        $product_array = array();
        $errors = array();
        $successes = array();
        $query_param = '?products=';
         foreach ($product_ids as $key => $product_id) {
            $profileData = $this->getProductsProfile($product_id);
          
            if ($profileData) {
                $profileIdType = isset($profileData['profileAdditionalInfo']['product_reference_type']) ?
                    $profileData['profileAdditionalInfo']['product_reference_type'] : '';
                $product = new Product($product_id);
                $product_combination = $this->getAttributesResume($product_id, $this->defaultLang);
                if(!empty( $product_combination)) {
                 foreach($product_combination as $product_combi){
                        $product = (array)$product_combi;
                        if ($profileIdType == 'EAN' && isset($product['ean13'])) {
                            $product_array[$product['ean13']] = $product_id;
                            $query_param .=$product['ean13'].'|EAN'.',';
                        } elseif ($profileIdType == 'SHOP_SKU' && isset($product['reference'])) {
                           // found only basis if UPC
                            $product_array[$product['reference']] = $product_id;
                            $query_param .=$product['reference'].'|SHOP_SKU'.',';
                           // $product_array[$product['ean13']] = $product_id;
                           // $query_param .=$product['ean13'].'|EAN'.',';
                        } elseif ($profileIdType == 'MPN' && isset($product['reference'])) {
                            $product_array[$product['reference']] = $product_id;
                            $query_param .=$product['reference'].'|MPN'.',';
                        } elseif ($profileIdType == 'UPC' && isset($product['upc'])) {
                            $product_array[$product['upc']] = $product_id;
                            $query_param .=$product['upc'].'|UPC'.',';
                        }
                        }
                } else {
                        $product = (array)$product;
                        if ($profileIdType == 'EAN' && isset($product['ean13'])) {
                            $product_array[$product['ean13']] = $product_id;
                            $query_param .=$product['ean13'].'|EAN'.',';
                        } elseif ($profileIdType == 'SHOP_SKU' && isset($product['reference'])) {
                           // found only basis if UPC
                            $product_array[$product['reference']] = $product_id;
                            $query_param .=$product['reference'].'|SHOP_SKU'.',';
                           // $product_array[$product['ean13']] = $product_id;
                           // $query_param .=$product['ean13'].'|EAN'.',';
                        } elseif ($profileIdType == 'MPN' && isset($product['reference'])) {
                            $product_array[$product['reference']] = $product_id;
                            $query_param .=$product['reference'].'|MPN'.',';
                        } elseif ($profileIdType == 'UPC' && isset($product['upc'])) {
                            $product_array[$product['upc']] = $product_id;
                            $query_param .=$product['upc'].'|UPC'.',';
                        }
                }
                
            }
        }
        
        $query_param = rtrim($query_param, ',');
        $cmxwalmartHelper = new CedElkjopnordicHelper();
      
        $response_product = $cmxwalmartHelper->WGetRequest('products'.$query_param, array(), 'json');
      
        if (isset($response_product['success'])) {
            if ($response_product['success']) {
                if (isset($response_product['response'])) {
                    $response_product = $response_product['response'];
                    $response_product = json_decode($response_product, true);
                    $response_product = isset($response_product['products']) ? $response_product['products'] : array();
                    foreach ($response_product as $product) {
                        if (isset($product_array[$product['product_id']])) {
                            $this->updateErrorInformation(
                                (int)$product_array[$product['product_id']],
                                array(),
                                'Live'
                            );
                            unset($product_array[$product['product_id']]);
                        }
                    }

                    foreach ($product_array as $key => $product_id) {
                        $this->updateErrorInformation((int)$product_id, array(), 'Not Created Yet');
                        unset($product_array[$key]);
                    }
                }
                $successes[] = 'Product '.json_encode($product_ids).' synced successfully.';
            } else {
                $errors[] = isset($response_product['message']) ?
                    (is_array($response_product['message']) ? json_encode($response_product['message']):
                        $response_product['message']) :
                    (is_array($response_product) ? json_encode($response_product): $response_product);
            }
        } else {
            $errors[] = is_array($response_product) ? json_encode($response_product) : $response_product;
        }
        return array('success' => $successes, 'error' => $errors);
    }
    public function makeInclude($product_ids){
        $shop_id = Context::getContext()->shop->id;
        $product_idss = array_chunk($product_ids,300);
        foreach ($product_idss as $product_ids) {
            $sql = "INSERT INTO "._DB_PREFIX_."cedelkjopnordic_products (id, id_product, product_feed_status, id_shop) VALUES ";
            foreach ($product_ids as $product_id) {
                $sql .="((SELECT `id` FROM ps_cedelkjopnordic_products pscp WHERE pscp.id_product='".(int)$product_id."' AND pscp.id_shop = '".(int)$shop_id."'  LIMIT 1), '".(int)$product_id."', 1,'".(int)$shop_id."'), ";
            }
            $sql = rtrim($sql,', ');
            $sql .= " ON DUPLICATE KEY UPDATE id_product=values(id_product), product_feed_status=values(product_feed_status), id_shop = values(id_shop)";
            Db::getInstance()->execute($sql);
        }
    }

    public function makeExclude($product_ids){
        $shop_id = Context::getContext()->shop->id;
        $product_idss = array_chunk($product_ids,300);
        foreach ($product_idss as $product_ids) {
            $sql = "INSERT INTO "._DB_PREFIX_."cedelkjopnordic_products (id, id_product, product_feed_status, id_shop) VALUES ";
            foreach ($product_ids as $product_id) {
                $sql .="((SELECT `id` FROM ps_cedelkjopnordic_products pscp WHERE pscp.id_product='".(int)$product_id."' AND pscp.id_shop = '".(int)$shop_id."'  LIMIT 1), '".(int)$product_id."', 2,'".(int)$shop_id."'), ";
            }
            $sql = rtrim($sql,', ');
            $sql .= " ON DUPLICATE KEY UPDATE id_product=values(id_product), product_feed_status=values(product_feed_status), id_shop = values(id_shop)";
            Db::getInstance()->execute($sql);
        }
    }
    public function removeProfile($product_ids,$profile_id){
        $product_idss = array_chunk($product_ids,300);
        foreach ($product_idss as $product_ids) {
            $sql = "DELETE FROM "._DB_PREFIX_."cedelkjopnordic_profile_products WHERE id_product IN (".implode(',',$product_ids).") AND id_cedelkjopnordic_profile ='".(int)$profile_id."'";
            Db::getInstance()->execute($sql);
        }

    }
    public function assignProfile($product_ids,$profile_id){
        $product_idss = array_chunk($product_ids,300);
        $shop_id = Context::getContext()->shop->id;
        foreach ($product_idss as $product_ids) {
            
            foreach ($product_ids as $product_id) {
                $sql = "INSERT INTO "._DB_PREFIX_."cedelkjopnordic_profile_products (id, id_product, id_cedelkjopnordic_profile, id_shop_profile) VALUES ";
                $sql .="((SELECT `id` FROM "._DB_PREFIX_."cedelkjopnordic_profile_products pscp WHERE pscp.id_product='".(int)$product_id."' AND pscp.id_shop_profile = '".(int)$shop_id."' LIMIT 1), '".(int)$product_id."', '".(int)$profile_id."', '".(int)$shop_id."') ";
                $sql = rtrim($sql,', ');
            $sql .= " ON DUPLICATE KEY UPDATE id_product=values(id_product), id_cedelkjopnordic_profile=values(id_cedelkjopnordic_profile), id_shop_profile=values(id_shop_profile)";

            Db::getInstance()->execute($sql);
            }
            
        }
    }
    public function uploadAllProducts($ids,$batch_id =false)
    {
        $productToUpload = array();
        $validation_error = array();
        $errors = array();
        if (count($ids) > 0) {
            $defaultLanguage = Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE')?(int)Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'): (Context::getContext()->language->id?Context::getContext()->language->id:(int)Configuration::get('PS_LANG_DEFAULT'));
            $shop_id = Context::getContext()->shop->id;
            foreach ($ids as $id) {
                $validation_error = array();
                if (!$id) {
                    $errors[] = array('success' => array(), 'error' => array(array('Id ' . $id . ' is invalid')));
                }
                $s = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products` WHERE `id_product`='" .
                    (int)$id . "' AND `product_feed_status`='2' AND id_shop = '".(int)$shop_id."'";
                $is_created = Db::getInstance()->getValue($s);

                if($is_created){

                    $errors[] ='Product ID '.$id.' is Excluded';
                    continue;
                }


                $product = new Product($id, true, $defaultLanguage);
                if ($id) {
                    $this->product_info = (array)$product;
                }


                $elkjopnordic_category = '';
                $attribute_mappings = array();


                $profileData = $this->getProductsProfile($id);

                if (!$profileData) {
                    $validation_error[] = 'Profile not Mapped For Product' . $id;
                    $profileData = array();
                } else {
                    $elkjopnordic_category = $profileData['profileElkjopnordicCategories'];
                    $count = count($elkjopnordic_category);
                    $elkjopnordic_category = $elkjopnordic_category['level_' . $count];

                    $attribute_mappings = $profileData['profileAttributes'];
                    if (is_array($attribute_mappings)) {
                        $attribute_mappings = array_filter($attribute_mappings);
                    }
                }

                $productToUpload = $this->prepareSimpleProduct(
                    $id,
                    $this->product_info,
                    $profileData,
                    $elkjopnordic_category,
                    $attribute_mappings
                );

                $productToUpload = $this->prepareVariantProduct($id, $product, $productToUpload, $profileData);

                foreach ($productToUpload as $elkjopnordic_product) {

                    $sku = isset($elkjopnordic_product['Shop_SKU']) ? $elkjopnordic_product['Shop_SKU'] : '';

                    $elkjopnordic_product['ProductID'] = $sku;
                    $elkjopnordic_product['CategoryIdentifier'] = $elkjopnordic_category;

                    $validation_result = $this->validateProduct($elkjopnordic_product, $id, $sku, $profileData['id'],$attribute_mappings);

                    if (isset($validation_result['success']) && $validation_result['success']) {
                        if (!isset($productToUploadList[$elkjopnordic_category]) ||
                            !is_array($productToUploadList[$elkjopnordic_category])) {
                            $productToUploadList[$elkjopnordic_category] = array();
                        }
                        array_push($productToUploadList[$elkjopnordic_category], $elkjopnordic_product);
                    } else {
                        $validation_error = array_merge($validation_error, $validation_result['error']);
                    }
                }

                if (!empty($validation_error)) {
                    $this->updateErrorInformation($id, $validation_error);
                } else {
                    $this->updateErrorInformation($id, array());
                }
                $errors = array_merge($errors, $validation_error);
            }

            
            if ($batch_id)
                $folder_identifier = $batch_id;
            else
                $folder_identifier = time();

            if (!empty($productToUploadList)) {
                foreach ($productToUploadList as $key => $value) {
                    $upload_alldir = _PS_MODULE_DIR_ . 'cedelkjopnordic/product_feed/request/all_products/' . $folder_identifier;    
                    if (!is_dir($upload_alldir)) {
                        mkdir($upload_alldir, 0777, true);
                    }

                    foreach ($value as $val) {
                        ksort($val);

                        if (!file_exists($upload_alldir . '/' . $key . '-batch-'.$folder_identifier.'.csv')) {
                            $cho = fopen($upload_alldir . '/' . $key .'-batch-'.$folder_identifier. '.csv', 'w');
                            $headers = array_keys($val);
                            fputcsv($cho, $headers);
                        } else {
                            $cho = fopen($upload_alldir . '/' . $key  .'-batch-'.$folder_identifier. '.csv', 'a');
                        }
                        if ($cho)
                            fputcsv($cho, array_values($val));
                        fclose($cho);
                    }
                    
                }
                return array('success' => true, 'message' => 'batch ' . $folder_identifier . ' is in process', 'batch' => $folder_identifier);

            } else {
                return array('success' => false, 'message' => $validation_error);
            }
        }
    }
    
    public function uploadAllOffers($ids,$batch_id =false)
    {
        $productToUpload = array();
        $validation_error = array();
        $errors = array();
        if (count($ids) > 0) {
           $offers = array();
            foreach ($ids as $product_id) {
                $offerToUpload = array();
                $profileData = $this->getProductsProfile($product_id);
                if ($profileData) {
                    $elkjopnordic_category = $profileData['profileElkjopnordicCategories'];
                    $count = count($elkjopnordic_category);
                    $elkjopnordic_category = $elkjopnordic_category['level_' . $count];
                    $context = Context::getContext();
                    $context->cart = new Cart();
                    $product = new Product(
                        $product_id,
                        true,
                        $this->defaultLang,
                        (int)$context->shop->id,
                        $context
                    );

                    $product_data = (array)$product;
                     $s = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_products` WHERE `id_product`='" .
            (int)$product_id . "' AND `product_feed_status`='1'";//AND `elkjopnordic_status` ='Live'
                    $is_created = Db::getInstance()->getValue($s);
                    
                    if($is_created)
                    continue;
                    
                    $offerToUpload = $this->prepareSingleOffer(
                        $product_id,
                        $product_data,
                        $offerToUpload,
                        $profileData
                    );

                    $offerToUpload = $this->prepareVariantOffers($product_id, $product, $offerToUpload, $profileData);

                    foreach ($offerToUpload as $elkjopnordic_offer) {
                        if (!isset($offers[$elkjopnordic_category]) || !is_array($offers[$elkjopnordic_category])) {
                            $offers[$elkjopnordic_category] = array();
                        }
                        array_push($offers[$elkjopnordic_category], $elkjopnordic_offer);
                    }
                } else {
                    $validation_error[] = 'Profile Not mapped For Product' . $product_id;
                }

                $errors = array_merge($errors, $validation_error);
            
            }

            
            if ($batch_id)
                $folder_identifier = $batch_id;
            else
                $folder_identifier = time();

            if (!empty($offers)) {
                foreach ($offers as $key => $value) {
                    $upload_alldir = _PS_MODULE_DIR_ . 'cedelkjopnordic/product_feed/request/all_offers/' . $folder_identifier;    
                    if (!is_dir($upload_alldir)) {
                        mkdir($upload_alldir, 0777, true);
                    }

                    foreach ($value as $val) {
                        ksort($val);

                        if (!file_exists($upload_alldir . '/' . $key .'-batch-'.$folder_identifier. '.csv')) {
                            $cho = fopen($upload_alldir . '/' . $key .'-batch-'.$folder_identifier. '.csv', 'w');
                            $headers = array_keys($val);
                            fputcsv($cho, $headers);
                        } else {
                            $cho = fopen($upload_alldir . '/' . $key .'-batch-'.$folder_identifier. '.csv', 'a');
                        }
                        if ($cho)
                            fputcsv($cho, array_values($val));
                        fclose($cho);
                    }
                    
                }
                return array('success' => true, 'message' => 'batch ' . $folder_identifier . ' is in process...', 'batch' => $folder_identifier);

            } else {
                return array('success' => false, 'message' => $validation_error);
            }
        }
    }
}
