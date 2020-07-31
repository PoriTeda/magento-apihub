<?php
namespace Riki\Rma\Model;

class Grid extends \Magento\Rma\Model\Grid implements \Riki\Rma\Api\Data\GridInterface
{
    /**
     * {@inheritdoc}
     */
    protected function _init($resourceModel)
    {
        parent::_init($resourceModel);

        $this->_collectionName = 'Riki\Rma\Model\ResourceModel\Grid\Collection';
    }
}
