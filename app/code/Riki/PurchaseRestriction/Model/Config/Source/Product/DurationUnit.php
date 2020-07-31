<?php
namespace Riki\PurchaseRestriction\Model\Config\Source\Product;

/**
 * Product option types mode source
 */
class DurationUnit extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const UNIT_MONTH = 'month';
    const UNIT_WEEK = 'week';
    const UNIT_DAY = 'day';

    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $options = [
            self::UNIT_MONTH   =>  'Month',
            self::UNIT_WEEK   =>  'Week',
            self::UNIT_DAY   =>  'Day'
        ];

        foreach($options as $key    =>  $value){
            $this->_options[] = [
                'value'   =>  $key,
                'label'     =>  $value
            ];
        }

        return $this->_options;
    }
}
