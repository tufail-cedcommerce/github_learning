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
 * @package   CedAmazon
 */


require_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';

class CedelkjopnordicOptionUpdateModuleFrontController extends ModuleFrontController
{
    public function initContent(){
        $db = Db::getInstance();
        try {
         $elkjopnordicHelper = new CedElkjopnordicHelper();
         $attributes = $db->executeS("SELECT *  FROM `"._DB_PREFIX_."cedelkjopnordic_attributes` WHERE `attribute_type` LIKE '%LIST%' AND category_id !='' ORDER BY `category_id`  ASC");
         


        foreach($attributes as $attribute){
                 $list_of_values = array();
                    // echo '<pre>';                    print_r($attribute);
                $response = $elkjopnordicHelper->WGetRequest('values_lists', $attribute['values_list'], 'json');

                if (isset($response['success']) && $response['success']) {
                    $response_attribute_options = $response['response'];
                    $response_attribute_options = json_decode($response_attribute_options, true);
                  
                    foreach($response_attribute_options as $response_attribute_option) { 
                        foreach($response_attribute_option as $response_attr) { 
                         if($response_attr && isset($response_attr['code']) && ($response_attr['code']==$attribute['attribute_code']) && isset($response_attr['values']) && !empty($response_attr['values'])) { 
                                
                                $list_of_values = $response_attr['values'];
                        } }
                    }
//echo '<pre>';                    print_r($list_of_values);
                    /*if (isset($response_attribute_options['values_lists'][0]['values'])) {
                        $response_attribute_options = $response_attribute_options['values_lists'][0]['values'];
                    }*/
                  
                    $attribute_option_db_chunk = array_chunk($list_of_values, 100);
                   
                    foreach ($attribute_option_db_chunk as $options) {
                        $attribute_option_db_param = array();
                        foreach ($options as $data) {
                             $attribute_option_db_param[] = array(
                                 'category_id' => pSQL($attribute['category_id']),
                                 'attribute_code' =>  pSQL($attribute['attribute_code']),
                                 'values_list' =>  pSQL($attribute['values_list']),
                                 'default_value' => '',
                                 'value_code' =>  pSQL($data['code']),
                                 'value_label' =>  pSQL($data['label'])
                             );
                        }
                    
                       $db->insert(
                            'cedelkjopnordic_attribute_options',
                            $attribute_option_db_param
                        );
                    }
                    
                }
        
         
        }

        } catch (\Exception $e) {
            echo $e->getMessage();die;
        }
        $this->setTemplate('module:cedelkjopnordic/views/templates/front/orderstatus.tpl');
    }
}
