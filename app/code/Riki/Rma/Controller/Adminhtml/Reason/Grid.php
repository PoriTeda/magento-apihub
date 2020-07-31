<?php
namespace Riki\Rma\Controller\Adminhtml\Reason;

class Grid extends \Riki\Rma\Controller\Adminhtml\Reason
{

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }
}