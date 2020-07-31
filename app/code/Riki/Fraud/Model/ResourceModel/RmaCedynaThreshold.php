<?php
namespace Riki\Fraud\Model\ResourceModel;
class RmaCedynaThreshold extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_rma_cedyna_threshold','id');
    }
}
