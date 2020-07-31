<?php

namespace Riki\Shipment\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Riki\SapIntegration\Model\ResourceModel\ShipmentSapExported\CollectionFactory as CollectionFactory;
use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;

class Resend extends \Magento\Backend\App\Action
{
    /**
     * @var CollectionFactory
     */
    protected $shipmentSapExportedCollectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
     */
    protected $shipmentSapExportedRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Resend constructor.
     *
     * @param Action\Context $context
     * @param CollectionFactory $shipmentSapExportedCollectionFactory
     * @param Filter $filter
     * @param \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        CollectionFactory $shipmentSapExportedCollectionFactory,
        Filter $filter,
        \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->shipmentSapExportedCollectionFactory = $shipmentSapExportedCollectionFactory;
        $this->filter = $filter;
        $this->shipmentSapExportedRepository = $shipmentSapExportedRepository;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        $shipmentSapExportedCollection = $this->shipmentSapExportedCollectionFactory->create();
        $shipmentSapExportedCollection->addFieldToFilter('is_exported_sap', ['eq' => ShipmentApi::EXPORTED_TO_SAP]);
        $shipmentSapExported = $this->filter->getCollection($shipmentSapExportedCollection);

        if ($shipmentSapExported->getSize()) {
            $totalSuccess = 0;
            /** @var \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $sapExported */
            foreach ($shipmentSapExported as $sapExported) {
                $sapExported->setIsExportedSap(ShipmentApi::WAITING_FOR_EXPORT);
                $sapExported->setExportSapDate(null);
                try {
                    $this->shipmentSapExportedRepository->save($sapExported);
                    $totalSuccess++;
                } catch (\Exception $e) {
                    $this->logger->info('Cannot change Sap flag for shipment #'.$sapExported->getShipmentIncrementId());
                    $this->logger->critical(
                        'Shipment #'.$sapExported->getShipmentIncrementId().' error: '.$e->getMessage()
                    );
                }
            }
            $this->messageManager->addSuccess(
                __('Total of %1 shipments was set to waiting export to SAP', $totalSuccess)
            );
        } else {
            $this->messageManager->addError(__('No item was exported to SAP found'));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/shipment/index/');
    }
}