<?php
namespace Riki\GiftOrder\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class GiftOption implements ArrayInterface
{
    protected $_giftMessageHelper;
    public function __construct(
        \Riki\GiftOrder\Helper\Data  $data
    )
    {
        $this->_giftMessageHelper  = $data;
    }

    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        $ret[''] = "please select gift message";
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    public function toArray()
    {
        $resultArray = array();
        $collection = $this->_giftMessageHelper->getGiftMessageOption();
        foreach ($collection as $option) {
            $resultArray[$option->getId()] = $option->getMessage();
        }
        return $resultArray;
    }
}