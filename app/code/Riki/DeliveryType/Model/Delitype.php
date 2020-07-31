<?php

namespace Riki\DeliveryType\Model;

use \Magento\Framework\Model\AbstractModel;

class Delitype extends AbstractModel
{
    const NORMAl = 'normal';
    const COOL = 'cool';
    const DM = 'direct_mail';
    const COLD = 'cold';
    const CHILLED = 'chilled';
    const COSMETIC = 'cosmetic';
    const COOL_NORMAL_DM = 'CoolNormalDm';
    const DELIVERY_TYPE_FLAG = 'delivery_type_flag';


    /**
     * Initialize resource model
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\DeliveryType\Model\ResourceModel\Delitype');
    }


}

