<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Model\Rule\Condition;


use Magento\Customer\Model\AddressFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Context;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\Config\Source\Country as CountrySource;

/**
 * @method string getAttribute()
 * @method $this setAttributeOption($attributes)
 * @method array getAttributeOption()
 */
class Shipping extends AbstractCondition
{
    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var ShippingConfig
     */
    protected $shippingConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CountrySource
     */
    protected $countrySource;

    public function __construct(
        AddressFactory $addressFactory,
        ShippingConfig $shippingConfig,
        ScopeConfigInterface $scopeConfig,
        CountrySource $countrySource,
        Context $context
    ) {
        $this->addressFactory = $addressFactory;
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig = $scopeConfig;
        $this->countrySource = $countrySource;

        return parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'shipping_method' => __('Shipping: Shipping Method'),
        ];

        $addressAttributes = $this->addressFactory->create()->getAttributes();
        foreach ($addressAttributes as $attr) {
            if ($attr->getStoreLabel() && $attr->getAttributeCode()) {
                $attributes[$attr->getAttributeCode()] = __('Shipping: ') . $attr->getStoreLabel();
            }
        }

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'shipping_method':
            case 'country_id':
                $type = 'multiselect';
                break;

            default:
                $type = 'string';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'shipping_method':
            case 'country_id':
                $type = 'multiselect';
                break;
            default:
                $type = 'text';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getValueOption()
    {
        $options = [];

        if ($this->getAttribute() === 'shipping_method') {
            foreach ($this->shippingConfig->getAllCarriers() as $code => $method) {
                $options[$code] = $this->scopeConfig->getValue("carriers/$code/title");
            }
        } elseif ($this->getAttribute() === 'country_id') {
            foreach ($this->countrySource->toOptionArray(true) as $country) {
                $options[$country['value']] = $country['label'];
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AbstractModel $object)
    {
        $attrCode = $this->getAttribute();

        /** @var \Magento\Sales\Model\Order $object */
        $shippingAddress = $object->getShippingAddress();

        if ($shippingAddress) {
            $value = $shippingAddress->getData($attrCode);
        } else {
            $value = false;
        }

        return parent::validateAttribute($value);
    }
}
