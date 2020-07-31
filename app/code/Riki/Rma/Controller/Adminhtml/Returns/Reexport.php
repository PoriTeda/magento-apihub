<?php
namespace Riki\Rma\Controller\Adminhtml\Returns;

use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;
use Magento\Ui\Component\MassAction\Filter;

class Reexport extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_export_to_sap';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        $id = $request->getParam('id', 0);
        if ($id) {
            $result->setUrl($this->getUrl('adminhtml/rma/edit', ['id' => $id]));
        } else {
            $result->setUrl($this->getUrl('adminhtml/rma/'));
        }
        $ids = $request->getParam('entity_ids', [$id]);
        $items = $this->searchHelper
            ->getByIsExportedSap(ShipmentApi::EXPORTED_TO_SAP)
            ->getByEntityId($ids)
            ->getAll()
            ->execute($this->rmaRepository);

        if ($items) {
            $successCount = 0;
            /** @var \Riki\Rma\Model\Rma $item */
            foreach ($items as $item) {
                try {
                    $item->setData('is_exported_sap', ShipmentApi::WAITING_FOR_EXPORT);
                    $item->setData('export_sap_date', null);
                    $this->rmaRepository->save($item);
                    $successCount++;
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError(__('Cannot re-export RMA #%1, error detail: %2', $item->getIncrementId(), $e->getMessage()));
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('An error occurred when processing item %1, please try again!', $item->getIncrementId()));
                    $this->logger->critical($e);
                }
            }

            $this->messageManager->addSuccess(
                __('Total of %1 RMAs have been set to SAP waiting export.', $successCount)
            );
        } else {
            $this->messageManager->addError(__('No item was exported to SAP found'));
        }

        return $result;
    }

    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return_actions_export_to_sap');
    }
}