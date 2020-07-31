<?php
namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Riki\ShipLeadTime\Controller\Adminhtml\Index\AbstractMassAction
{

    /**
     *
     */
    protected function massAction(\Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection)
    {
        $deletedCount = 0;
        /** @var \Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime */
        foreach ($collection->getItems() as $leadTime) {
            $this->leadTimeRepository->delete($leadTime);
            $deletedCount++;
        }

        if ($deletedCount) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were Deleted.', $deletedCount));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
