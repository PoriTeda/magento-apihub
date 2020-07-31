<?php
namespace Riki\FairAndSeasonalGift\Model\Options;

class FairType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Riki\FairAndSeasonalGift\Model\Fair
     */
    protected $_fair;

    /**
     * Constructor
     *
     * @param \Riki\FairAndSeasonalGift\Model\Fair $fair
     */
    public function __construct(\Riki\FairAndSeasonalGift\Model\Fair $fair)
    {
        $this->_fair = $fair;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->_fair->getFairType();
        $options = [];
        $options[] = [
            'label' => __('Select'),
            'value' => ''
        ];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key
            ];
        }
        return $options;
    }

    /**
     * @param $type :int
     * return string
     */
    public function getFairTypeValue($type)
    {
        $typeOption = $this->_fair->getFairType();
        if( !empty($typeOption[$type]) ){
            return $typeOption[$type];
        } else {
            return false;
        }
    }
}
