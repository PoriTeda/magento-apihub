<?php
namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MassDisable extends \Riki\ShipLeadTime\Controller\Adminhtml\Index\AbstractMassAction
{

    /**
     *
     */
    protected function massAction(\Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection)
    {
        $updatedCount = 0;
        /** @var \Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime */
        foreach ($collection->getItems() as $leadTime) {
            $leadTime->setIsActive(0);
            $this->leadTimeRepository->save($leadTime);
            $updatedCount++;
        }

        if ($updatedCount) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were Disabled.', $updatedCount));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
