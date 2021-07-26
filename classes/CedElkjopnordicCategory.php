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

class CedElkjopnordicCategory extends ObjectModel
{
    public $elkjopnordicHelper;

    public function __construct()
    {
        $this->elkjopnordicHelper = new CedElkjopnordicHelper();
    }

    /**
     * @param $params
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getElkjopnordicCategory($params)
    {
        $cat_id = '';
        try {
            $db = Db::getInstance();
            $level = 0;
            if (isset($params['max_level'])) {
                $level = $params['max_level'] + 1;
            }
            if (isset($params['hierarchy'])) {
                $cat_id = $params['hierarchy'];
            } else {
                $cat_id = 0;
                $level = 1;
            }

            $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_categories` 
            WHERE `category_id`='" . pSQL($cat_id) . "'";
            $res = $db->executeS($sql);
            if (count($res) == 0) {
                $response = $this->elkjopnordicHelper->WGetRequest('hierarchies', $params, 'json');
                if (isset($response['success']) && $response['success']) {
                    $response_categories = $response['response'];
                    $response_categories = json_decode($response_categories, true);
                    if (isset($response_categories['hierarchies'])) {
                        $response_categories = $response_categories['hierarchies'];
                    }
                    $res = $db->insert(
                        'cedelkjopnordic_categories',
                        array(
                            'category_id' => pSQL($cat_id),
                            'category_data' => pSQL(json_encode($response_categories)),
                            'level' => (int)$level,
                        )
                    );
                    return $response_categories;
                } else {
                    if (isset($response['message'])) {
                        $error = $response['message'];
                    } else {
                        $error = 'Some problem occured!';
                    }
                    return array(
                        'success' => false,
                        'message' => $error
                    );
                }
            } else {
                if (isset($res[0]['category_data'])) {
                    return json_decode($res[0]['category_data'], true);
                } else {
                    return array(
                        'success' => false,
                        'message' => ''
                    );
                }
            }
        } catch (\Exception $e) {
            $this->elkjopnordicHelper->log(
                __METHOD__,
                'Exeception',
                $e->getMessage(),
                json_encode(
                    array(
                        'url' => 'hierarcies',
                        'Request Param' => $params,
                        'Response' => $response
                    )
                ),
                true
            );
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * @param $params
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getElkjopnordicAttributes($params)
    {
        $attributeParam = array();
        $attributeParam['hierarchy'] = '';
        try {
            if (isset($params['hierarchy'])) {
                $attributeParam['hierarchy'] = $params['hierarchy'];
            }
            $db = Db::getInstance();
            $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_attributes` WHERE `category_id`='" .
                pSQL($attributeParam['hierarchy']) . "'";
            $res = $db->executeS($sql);
          
            if (empty($res)) {
                $response = $this->elkjopnordicHelper->WGetRequest('products/attributes', $attributeParam, 'json');
                if (isset($response['success']) && $response['success']) {
                    $response_attributes = $response['response'];
                    $response_attributes = json_decode($response_attributes, true);
                    if (isset($response_attributes['attributes'])) {
                        $response_attributes = $response_attributes['attributes'];
                    }
                    $attributes_db_param = array();
                   
                    foreach ($response_attributes as $attributes) {
                    
                      //  if ($attributeParam['hierarchy']==$attributes['hierarchy_code'] ||
                         //   $attributes['hierarchy_code']== '') {
                            $attributes_db_param[] = array(
                                'category_id' => pSQL($attributeParam['hierarchy']),
                                'attribute_code' => pSQL($attributes['code']),
                                'attribute_label' => pSQL($attributes['label']),
                                'default_value' => pSQL($attributes['default_value']),
                                'required' => (int)$attributes['required'],
                                'is_variant' => (int)$attributes['variant'],
                                'attribute_type' => pSQL($attributes['type']),
                                'values_list' => pSQL($attributes['values_list']),
                                'values' => pSQL($attributes['values'])
                            );
                        //}
                    }

                    $res = $db->insert(
                        'cedelkjopnordic_attributes',
                        $attributes_db_param
                    );


                    return $attributes_db_param;
                } else {
                    if (isset($response['message'])) {
                        $error = $response['message'];
                    } else {
                        $error = 'Some problem occured!';
                    }
                    return array(
                        'success' => false,
                        'message' => $error
                    );
                }
            } else {
                return $res;
            }
        } catch (\Exception $e) {
            $this->elkjopnordicHelper->log(
                'CronCreateOrder',
                'Exeception',
                $e->getMessage(),
                json_encode(
                    array(
                        'Request Param' => $params,
                        'Response' => $response
                    )
                ),
                true
            );
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * @param $params
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getElkjopnordicAttributeOptions($params)
    {
        $value_list_param = array();
        $value_list_param['code'] = '';
        $attribute_code = '';
        $search_term = '';
        try {
            if (isset($params['values_list_code']) && $params['values_list_code']) {
                $value_list_param['code'] = $params['values_list_code'];
            }
            if (isset($params['attribute_code']) && $params['attribute_code']) {
                $attribute_code = $params['attribute_code'];
            }
            if (isset($params['filter_name'])) {
                $search_term = $params['filter_name'];
            }
            $db = Db::getInstance();
            $sql = "SELECT `value_code` , `value_label` FROM `" . _DB_PREFIX_ .
                "cedelkjopnordic_attribute_options` WHERE `attribute_code`='" . pSQL($attribute_code) .
                "' AND `values_list`='" . pSQL($value_list_param['code']) . "' AND `category_id` LIKE '".pSQL($params['category_id'])."'";
            $res = $db->executeS($sql);

            if (count($res) == 0) {
                $response = $this->elkjopnordicHelper->WGetRequest('values_lists', $value_list_param, 'json');
                if (isset($response['success']) && $response['success']) {
                    $response_attribute_options = $response['response'];
                    $response_attribute_options = json_decode($response_attribute_options, true);
                    if (isset($response_attribute_options['values_lists'][0]['values'])) {
                        $response_attribute_options = $response_attribute_options['values_lists'][0]['values'];
                    }
                    $attribute_option_db_chunk = array_chunk($response_attribute_options, 100);
                    foreach ($attribute_option_db_chunk as $options) {
                        $attribute_option_db_param = array();
                        foreach ($options as $data) {
                             $attribute_option_db_param[] = array(
                                 'category_id' => pSQL($params['category_id']),
                                 'attribute_code' => pSQL($attribute_code),
                                 'values_list' =>  pSQL($value_list_param['code']),
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
                    $sql = "SELECT `value_code` , `value_label` FROM `" . _DB_PREFIX_ .
                        "cedelkjopnordic_attribute_options` WHERE `attribute_code`='" . pSQL($attribute_code) .
                        "' AND `values_list`='" . pSQL($value_list_param['code']) .
                        "' AND `value_label` LIKE '%" . pSQL($search_term) . "%' LIMIT 5";
                    $res = $db->executeS($sql);

                    return $res;
                } else {
                    if (isset($response['message'])) {
                        $error = $response['message'];
                    } else {
                        $error = 'Some problem occured!';
                    }
                    return array(
                        'success' => false,
                        'message' => $error
                    );
                }
            } else {
                $sql = "SELECT `value_code` , `value_label` FROM `" . _DB_PREFIX_ .
                    "cedelkjopnordic_attribute_options` WHERE `attribute_code`='" . pSQL($attribute_code) .
                    "' AND `values_list`='" . pSQL($value_list_param['code']) .
                    "' AND `value_label` LIKE '%" . pSQL($search_term). "%' LIMIT 5";
                $res = $db->executeS($sql);
                return $res;
            }
        } catch (\Exception $e) {
            $this->elkjopnordicHelper->log(
                'CronCreateOrder',
                'Exeception',
                $e->getMessage(),
                json_encode(
                    array(
                        'Request Param' => $params,
                        'Response' => $response
                    )
                ),
                true
            );
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * @return array
     */
    public function getSkipAttributes()
    {
        $skip_array = array(
            'Image1',
            'Image2',
            'Image3',
            'Shop_SKU'

        );
        return $skip_array;
    }

    /**
     * @return array
     */
    public function getDefaultAttributes()
    {
        $default_attr = array(
        );
        return $default_attr;
    }
}
