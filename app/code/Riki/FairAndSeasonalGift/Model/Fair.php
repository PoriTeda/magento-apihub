<?php
namespace Riki\FairAndSeasonalGift\Model;
class Fair extends \Magento\Framework\Model\AbstractModel
{
    const TYPE_WINTER = 1;
    const TYPE_SUMMER = 2;
    protected function _construct()
    {
        $this->_init('Riki\FairAndSeasonalGift\Model\ResourceModel\Fair');
    }

    /*
     * get list fair type
     */
    public function getFairType()
    {
        return [self::TYPE_WINTER => __('Winter'), self::TYPE_SUMMER => __('Summer')];
    }
}
