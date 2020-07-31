<?php
namespace Bluecom\Paygent\Model;

class PaygentOption extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\ResourceModel\PaygentOption');
    }

    /**
     * Load order by custom attribute value. Attribute value should be unique
     *
     * @param string $attribute
     * @param string $value
     * @return $this
     */
    public function loadByAttribute($attribute, $value)
    {
        $this->load($value, $attribute);
        return $this;
    }
}