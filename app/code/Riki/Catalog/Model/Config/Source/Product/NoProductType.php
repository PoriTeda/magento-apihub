<?php
namespace Riki\Catalog\Model\Config\Source\Product;

/**
 * Product option types mode source
 */
class NoProductType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const TYPE_SUBSCRIPTION = 1;
    const TYPE_HANPUKAI = 2;
    const TYPE_CAMPAIGN = 3;

    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $options = [
            self::TYPE_SUBSCRIPTION   =>  'Subscription',
            self::TYPE_HANPUKAI   =>  'Hanpukai',
            self::TYPE_CAMPAIGN   =>  'Campaign'
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
