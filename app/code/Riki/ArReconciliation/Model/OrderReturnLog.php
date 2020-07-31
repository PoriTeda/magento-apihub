<?php
namespace Riki\ArReconciliation\Model;
class OrderReturnLog extends \Magento\Framework\Model\AbstractModel
{
    const TYPE_MANUALLY = 'manually';
    const TYPE_IMPORT = 'import';

    const CHANGE_TYPE_AMOUNT = 1;
    const CHANGE_TYPE_DATE = 2;
    const CHANGE_TYPE_BOTH = 3;

    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog');
    }

    public function toOptionArray()
    {
        $options=[
            //['label' => __('-- Please Select --'), 'value' => ''],
            ['label' => __('Manually'), 'value' => self::TYPE_MANUALLY],
            ['label' => __('Auto Import'), 'value' => self::TYPE_IMPORT]
        ];

        return $options;
    }
}
