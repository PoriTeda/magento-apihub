<?php

namespace Riki\Checkout\Block\Checkout;

class LayoutProcessor extends \Magento\Checkout\Block\Checkout\LayoutProcessor
{
    const ID_AMBASSADOR = 3 ;
    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var \Magento\Ui\Component\Form\AttributeMapper
     */
    protected $attributeMapper;

    /**
     * @var AttributeMerger
     */
    protected $merger;
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;
    /**
     * Must add own construct because there are some private variables
     *
     * @param \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param \Magento\Ui\Component\Form\AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     */
    public function __construct(
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider,
        \Magento\Ui\Component\Form\AttributeMapper $attributeMapper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        AttributeMerger $merger
    )
    {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->currentCustomer = $currentCustomer;

        parent::__construct($attributeMetadataDataProvider, $attributeMapper, $merger);
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     *
     * @return array
     */
    public function process($jsLayout)
    {
        /** @var \Magento\Eav\Api\Data\AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );

        $elements = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getIsUserDefined()) {
                continue;
            }

            // name attributes will be processed later
            if (in_array($attribute->getAttributeCode(), ['firstname', 'lastname', 'firstnamekana', 'lastnamekana'])) {
                continue;
            }

            $elements[$attribute->getAttributeCode()] = $this->attributeMapper->map($attribute);
            if (isset($elements[$attribute->getAttributeCode()]['label'])) {
                $label = $elements[$attribute->getAttributeCode()]['label'];
                $elements[$attribute->getAttributeCode()]['label'] = __($label);
            }
        }
        unset($elements['riki_type_address']);
        // The following code is a workaround for custom address attributes
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']
        )) {
            if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'])
            ) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'] = [];
            }

            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'] =
                array_merge_recursive(
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'],
                    $this->processPaymentConfiguration(
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['renders']['children'],
                        $elements
                    )
                );
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        )) {
            $fields = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $this->merger->merge(
                $elements,
                'checkoutProvider',
                'shippingAddress',
                $fields
            );
        }

        $jsLayout = $this->_processRikiAttributes($jsLayout);

        return $jsLayout;
    }

    /**
     * Update custom_attributes data for elements
     *
     * @param $jsLayout
     *
     * @return mixed
     */
    protected function _processRikiAttributes($jsLayout)
    {
        /**
         * Need to process custom_attributes data for attributes which are not user defined because Magento ignores them
         */

        $arrayAttributes = array('riki_nickname','apartment');
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        )->addFieldToFilter('attribute_code', $arrayAttributes);
        $addressElements = [];
        foreach ($attributes as $attribute) {
            $addressElements[$attribute->getAttributeCode()] = $this->attributeMapper->map($attribute);
        }

        $paymentMethodRenders = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['payments-list']['children'];
        if (is_array($paymentMethodRenders)) {
            foreach ($paymentMethodRenders as $name => $renderer) {
                if (isset($renderer['children']) && array_key_exists('form-fields', $renderer['children'])) {
                    $fields = $renderer['children']['form-fields']['children'];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['payments-list']['children'][$name]['children']
                    ['form-fields']['children'] = $this->merger->merge(
                        $addressElements,
                        'checkoutProvider',
                        $renderer['dataScopePrefix'] . '.custom_attributes',
                        $fields
                    );

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['payments-list']['children'][$name]['children']
                    ['form-fields']['children'] = $this->_addNameElements(
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                        ['children']['payment']['children']['payments-list']['children'][$name]['children']
                        ['form-fields']['children'],
                        $renderer['dataScopePrefix']
                    );

                }
            }
        }
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        )) {
            $fields = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $this->merger->merge(
                $addressElements,
                'checkoutProvider',
                'shippingAddress.custom_attributes',
                $fields
            );

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $this->_addNameElements(
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'],
                'shippingAddress'
            );
        }

        return $jsLayout;
    }

    /**
     * Add grouped name to existing elements
     *
     * @param $fields
     * @param $dataScopePrefix
     *
     * @return array
     */
    protected function _addNameElements($fields, $dataScopePrefix)
    {
        $addressFields = [
            'riki_normal_name_group' => [
                'component' => 'Magento_Ui/js/form/components/group',
                'label' => __('Normal Name (full width)'),
                'required' => true,
                'provider' => 'checkoutProvider',
                'sortOrder' => '200',
                'type' => 'group',
                'config' => [
                    'template' => 'ui/group/group',
                ],
                'children' => [
                    [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => $dataScopePrefix,
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input-event-change'
                        ],
                        'dataScope' => $dataScopePrefix . '.lastname',
                        'provider' => 'checkoutProvider',
                        'validation' => [
                            'required-entry-last-name' => true,
                            'validate_double_byte_last_name' => true,
                            'max_text_length' => 25
                        ],
                        'exampleTmp' => __('Example: Yamada')
                    ],
                    [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => $dataScopePrefix,
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input-event-change'
                        ],
                        'dataScope' => $dataScopePrefix . '.firstname',
                        'provider' => 'checkoutProvider',
                        'validation' => [
                            'required-entry-first-name' => true,
                            'validate_double_byte_first_name' => true,
                            'max_text_length' => 25
                        ],
                        'exampleTmp' => __('Example: Taro')
                    ]
                ],
            ],
            'riki_kana_name_group' => [
                'component' => 'Magento_Ui/js/form/components/group',
                'label' => __('Kana Name (full width)'),
                'required' => true,
                'provider' => 'checkoutProvider',
                'sortOrder' => '300',
                'type' => 'group',
                'config' => [
                    'template' => 'ui/group/group',
                ],
                'children' => [
                    [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => $dataScopePrefix . '.custom_attributes',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input-event-change'
                        ],
                        'dataScope' => $dataScopePrefix . '.custom_attributes.lastnamekana',
                        'provider' => 'checkoutProvider',
                        'validation' => [
                            'required-entry-last-name-katakana' => true,
                            'validate_double_byte_last_kanatana_name' => true,
                            'max_text_length' => 40,
                            'validate-katakana' => true
                        ],
                        'exampleTmp' => __('Example: Katakana Yamada')
                    ],
                    [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => $dataScopePrefix . '.custom_attributes',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input-event-change'
                        ],
                        'dataScope' => $dataScopePrefix . '.custom_attributes.firstnamekana',
                        'provider' => 'checkoutProvider',
                        'validation' => [
                            'required-entry-first-name-katakana' => true,
                            'validate_double_byte_first_kanatana_name' => true,
                            'max_text_length' => 40,
                            'validate-katakana' => true
                        ],
                        'exampleTmp' => __('Example: Katakana Taro')
                    ]
                ],
            ]
        ];

        /**
         * change event keyup to change for riki_nickname
         */
        if (
            isset($fields['riki_nickname']) &&
            isset($fields['riki_nickname']['config']) &&
            isset($fields['riki_nickname']['config']['elementTmpl'])
        ) {
            $fields['riki_nickname']['config']['elementTmpl'] = 'ui/form/element/input-event-change';
        }

        /**
         * change event keyup to change for telephone
         */
        if (
            isset($fields['telephone']) &&
            isset($fields['telephone']['config']) &&
            isset($fields['telephone']['config']['elementTmpl'])
        ) {
            $fields['telephone']['config']['elementTmpl'] = 'ui/form/element/input-event-change';
        }

        /**
         * change event keyup to change for street
         */
        if (isset($fields['street']) && isset($fields['street']['children'])) {
            if (is_array($fields['street']['children']) && count($fields['street']['children']) > 0) {
                foreach ($fields['street']['children'] as $key => $childItem) {
                    if (
                        isset($fields['street']['children'][$key]['config'])
                        && isset($fields['street']['children'][$key]['config']['elementTmpl'])
                    ) {
                        $fields['street']['children'][$key]['config']['elementTmpl'] = 'ui/form/element/input-event-change';

                        /**
                         * remove validate min-text
                         */
                        if (
                            isset($fields['street']['children'][$key]['config']['validation']) &&
                            isset($fields['street']['children'][$key]['config']['validation']['min_text_length'])
                        ) {
                            unset($fields['street']['children'][$key]['config']['validation']['min_text_length']);
                        }
                    }
                }
            }
        }

        return array_merge($fields, $addressFields);
    }

    /**
     * Inject billing address component into every payment component
     *
     * @param array $configuration list of payment components
     * @param array $elements attributes that must be displayed in address form
     *
     * @return array
     */
    private function processPaymentConfiguration(array &$configuration, array $elements)
    {
        $output = [];
        foreach ($configuration as $paymentGroup => $groupConfig) {
            foreach ($groupConfig['methods'] as $paymentCode => $paymentComponent) {
                if (empty($paymentComponent['isBillingAddressRequired'])) {
                    continue;
                }
                $output[$paymentCode . '-form'] = [
                    'component' => 'Magento_Checkout/js/view/billing-address',
                    'displayArea' => 'billing-address-form-' . $paymentCode,
                    'provider' => 'checkoutProvider',
                    'deps' => 'checkoutProvider',
                    'dataScopePrefix' => 'billingAddress' . $paymentCode,
                    'sortOrder' => 1,
                    'children' => [
                        'form-fields' => [
                            'component' => 'uiComponent',
                            'displayArea' => 'additional-fieldsets',
                            'children' => $this->merger->merge(
                                $elements,
                                'checkoutProvider',
                                'billingAddress' . $paymentCode,
                                [
                                    'riki_nickname' => [
                                        'sortOrder' => 400,
                                        'exampleTmp' => __('Example: home address'),
                                        'validation' => [
                                            'max_text_length' => 20
                                        ],
                                    ],
                                    'postcode' => [
                                        'sortOrder' => 401,
                                        'component' => 'Magento_Ui/js/form/element/post-code',
                                        'exampleTmp' => __('Example: 100-0001'),
                                        'config' => [
                                            'elementTmpl' => 'Riki_ZipcodeValidation/form/element/input-postcode'
                                        ],
                                        'validation' => [
                                            'required-entry' => true
                                        ]
                                    ],
                                    'region' => [
                                        'sortOrder' => 500,
                                        'visible' => false
                                    ],
                                    'region_id' => [
                                        'sortOrder' => 402,
                                        'component' => 'Magento_Ui/js/form/element/region',
                                        'config' => [
                                            'template' => 'ui/form/field',
                                            'elementTmpl' => 'ui/form/element/select',
                                            'customEntry' => 'billingAddress' . $paymentCode . '.region',
                                        ],
                                        'validation' => [
                                            'validate-select' => true
                                        ],
                                        'filterBy' => [
                                            'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                            'field' => 'country_id'
                                        ],
                                    ],
                                    'city' => [
                                        'sortOrder' => 403,
                                        'validation' => [
                                            'max_text_length' => 30
                                        ],
                                        'exampleTmp' => __('Example: Chiyoda-ku')
                                    ],
                                    'street' => [
                                        'sortOrder' => 404,
                                        'validation' => [
                                            'max_text_length' => 30
                                        ],
                                        'exampleTmp' => __('Example: Chiyoda')
                                    ],
                                    'telephone' => [
                                        'sortOrder' => 406,
                                        'validation' => [
                                            'max_text_length' => 16,
                                            'validate-phone-number' => true
                                        ]
                                    ],
                                    'country_id' => [
                                        'sortOrder' => 407
                                    ],
                                    'apartment' => [
                                        'sortOrder' => 405,
                                        'validation' => [
                                            'max_text_length' => 40
                                        ],
                                    ],
                                    'company' => [
                                        'validation' => [
                                            'min_text_length' => 0
                                        ],
                                        'visible' => false
                                    ],
                                    'fax' => [
                                        'validation' => [
                                            'min_text_length' => 0
                                        ],
                                        'visible' => false
                                    ],
                                    'vat_id' => [
                                        'visible' => false
                                    ]
                                ]
                            ),
                        ],
                    ],
                ];
            }
            unset($configuration[$paymentGroup]['methods']);
        }

        return $output;
    }

    /**
     * @return array
     */
    public function getMembership(){
        $arrayMember = array();
        $customerMembership = $this->currentCustomer->getCustomer()->getCustomAttribute('membership')->getValue() ;

        if($customerMembership){
            $arrayMember = explode(',',$customerMembership);
        }
        return $arrayMember;
    }
}
