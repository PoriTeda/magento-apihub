<?php

namespace Riki\Rma\Controller\Adminhtml\Reason;

class Delete extends \Riki\Rma\Controller\Adminhtml\Reason
{
    const ADMIN_RESOURCE = 'Riki_Rma::reason_delete';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();
        $result = $this->initRedirectResult();
        $result->setUrl($this->getUrl('*/reason'));

        $id = $request->getParam('reason');
        if (!$id) {
            return $result;
        }
        $ids = $request->getParam('reason', [$id]);
        $items = $this->searchHelper
            ->getById($ids)
            ->getAll()
            ->execute($this->reasonRepository);

        $successCount = 0;
        foreach ($items as $item) {
            try {
                $this->reasonRepository->save($item->setData('deleted', 1));
                $successCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError(__($e->getMessage()));
                $this->logger->critical($e);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred when processing item %1, please try again!', $item->getId()));
                $this->logger->critical($e);
            }
        }
        if ($successCount) {
            $this->messageManager->addSuccess($successCount == 1
                ? __('You deleted the reason successfully.')
                : __('You deleted %1 reason(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}
