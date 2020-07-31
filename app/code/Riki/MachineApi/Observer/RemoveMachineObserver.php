<?php
namespace Riki\MachineApi\Observer;

use Magento\Framework\Event\ObserverInterface;
class RemoveMachineObserver implements ObserverInterface
{
    protected $helperMachine;
    protected $messageManager;
    protected $responseFactory;
    protected $url;
    protected $machineTypeFactory;

    /**
     * RemoveMachineObserver constructor.
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param \Magento\Framework\UrlInterface $url
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $
     */
    public function __construct(
        \Riki\MachineApi\Helper\Machine $helperMachine,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
    ) {
        $this->messageManager = $messageManager;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->machineTypeFactory = $machineTypeFactory;
        $this->helperMachine = $helperMachine;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();
        $data = $this->helperMachine->getMainProductAndMachineFromQuote($quote);

        $mainProduct = isset($data['product']) ? $data['product'] : [];
        $machines = isset($data['machine']) ? $data['machine'] : [];

        if (!empty($machines) && !empty($mainProduct)) {
            $machineTypeOfProduct = [];
            foreach ($mainProduct as $product) {
                $machineTypeOfProduct = array_merge($machineTypeOfProduct, explode(',', $product->getMachineCategories()));
            }
            $machineTypeOfProduct = array_unique($machineTypeOfProduct);

            foreach ($machines as $machineId => $machine) {
                $productMachineModel = isset($machine['product']) ? $machine['product'] : false;
                if (!is_object($productMachineModel) && !$productMachineModel->getId()) {
                    continue;
                }
                $arrListMachiType = $this->helperMachine->getMachineTypeOfMachine($productMachineModel->getId());
                if (empty(array_intersect($arrListMachiType, $machineTypeOfProduct))) {
                    $machineType = $this->machineTypeFactory->create()->load($machine['machine_type_id']);
                    $message = __(
                        'Machine %1 does not belong to type %2',
                        $productMachineModel->getSku(),
                        $machineType->getTypeCode()
                    );
                    $this->messageManager->addError($message);
                    $cartUrl = $this->url->getCurrentUrl();
                    $this->responseFactory->create()->setRedirect($cartUrl)->sendResponse();
                }
            }
        }
    }
}