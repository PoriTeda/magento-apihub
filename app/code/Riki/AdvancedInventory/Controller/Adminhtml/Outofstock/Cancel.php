<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml\Outofstock;

class Cancel extends \Riki\AdvancedInventory\Controller\Adminhtml\Outofstock
{
    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $returnUrl = $this->getRequest()->getParam('return_url')
            ? $this->urlDecoder->decode($this->getRequest()->getParam('return_url'))
            : $this->getUrl('adminhtml/riki_advancedinventory/outofstock');

        $postValues = $this->getRequest()->getPostValue();
        $id = isset($postValues['id']) ? $postValues['id'] : 0;
        if ($id) {
            $returnUrl = $this->getUrl('adminhtml/riki_advancedinventory/outofstock/edit', ['id' => $id]);
        }
        $ids = isset($postValues['entity_ids']) ? $postValues['entity_ids'] : [$id];
        $result->setUrl($returnUrl);

        $items = $this->searchHelper
            ->getByEntityId($ids)
            ->getAll()
            ->execute($this->outOfStockRepository);

        $successCount = 0;
        /** @var \Magento\Rma\Model\Rma $item */
        foreach ($items as $item) {
            try {
                $item->setData('generated_order_id', 0);
                $this->outOfStockRepository->save($item);
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
                ? __('You canceled the out of stock successfully.')
                : __('You canceled %1 out of stock(s) successfully.', $successCount)
            );
        }

        return $result;
    }

}