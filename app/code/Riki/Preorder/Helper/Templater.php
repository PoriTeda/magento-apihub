<?php

namespace Riki\Preorder\Helper;

use Magento\Framework\App\Helper\Context;

class Templater extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    public function __construct(Context $context, \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
        parent::__construct($context);
    }


    public function process($template, \Magento\Catalog\Model\Product $product)
    {
        $this->product = $product;
        $result = preg_replace_callback('/\{([^\{\}]+)\}/', array($this, 'attributeReplaceCallback'), $template);
        return $result;
    }

    protected function attributeReplaceCallback($match)
    {
        $attributeCode = $match[1];
        $value = $this->product->getResource()->getAttributeRawValue($this->product->getId(), $attributeCode, $this->product->getStoreId());

        $attributes = $this->product->getResource()->getAttributesByCode();
        if (isset($attributes[$attributeCode])) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $attributes[$attributeCode];
            $frontend = $attribute->getFrontendInput();

            if ($frontend == 'select') {
                $value = $attribute->getSource()->getOptionText($value);
            } else if ($frontend == 'date') {
                try {
                    // Avoid timezone offset issue
                    $date = new \Zend_Date($value);
                    $value = $this->timezone->formatDate($date, \IntlDateFormatter::MEDIUM , false);
                }
                catch (\Zend_Date_Exception $e) {
                    $value = '';
                }
            }
        }

        return ($value === false) ? '' : $value;
    }
}
