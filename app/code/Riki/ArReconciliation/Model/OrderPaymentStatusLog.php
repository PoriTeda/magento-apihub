<?php
namespace Riki\ArReconciliation\Model;
class OrderPaymentStatusLog extends \Magento\Framework\Model\AbstractModel
{
    const TYPE_MANUALLY = 'manually';
    const TYPE_IMPORT = 'import';
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\ResourceModel\OrderPaymentStatusLog');
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
