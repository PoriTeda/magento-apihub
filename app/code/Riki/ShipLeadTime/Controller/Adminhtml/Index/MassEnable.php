<?php
namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MassEnable extends \Riki\ShipLeadTime\Controller\Adminhtml\Index\AbstractMassAction
{

    /**
     *
     */
    protected function massAction(\Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection)
    {
        $updatedCount = 0;
        /** @var \Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime */
        foreach ($collection->getItems() as $leadTime) {
            $leadTime->setIsActive(1);
            $this->leadTimeRepository->save($leadTime);
            $updatedCount++;
        }

        if ($updatedCount) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were Enabled.', $updatedCount));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
