<?php
namespace Riki\AdvancedInventory\Plugin\Sales\Block\Adminhtml\Order\Create\Messages;

class OosWarning
{
    /**
     * @var array
     */
    protected $freeAttachments = [];

    /**
     * @var int
     */
    protected $quoteId;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var \Riki\Prize\Api\PrizeManagementInterface
     */
    protected $prizeManagement;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator
     */
    protected $quantityValidator;

    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;

    /**
     * OosWarning constructor.
     *
     * @param \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $quantityValidator
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\Prize\Api\PrizeManagementInterface $prizeManagement
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver,
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $quantityValidator,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\Prize\Api\PrizeManagementInterface $prizeManagement,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        $this->oosCaptureObserver = $oosCaptureObserver;
        $this->quantityValidator = $quantityValidator;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->prizeManagement = $prizeManagement;
        $this->sessionQuote = $sessionQuote;
    }

    /**
     * Add oos messages into block
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Messages $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\Create\Messages $subject,
        $result
    ) {
        $this->quoteId = $this->sessionQuote->getQuoteId();
        if (!$this->quoteId) {
            return $result;
        }

        $block = $subject->getLayout()->getBlock('totals'); // make quote collect totals to collect free gift
        if ($block) {
            $block->toHtml();
        }

        $this->collectFromPrize();
        $this->collectFromPromo();
        $this->collectFromMachine();
        $this->collectFromCumulative();

        foreach ($this->freeAttachments as $i => $attachment) {
            try {
                /** @var \Magento\Quote\Model\Quote $oosQuote */
                $oosQuote = $this->quoteFactory->create();
                $oosQuote->setIsSuperMode(true); // force to always initialize quote item
                $oosQuote->setData('is_generate', 1); // force to always initialize quote item
                $product = $this->productRepository->get($attachment['sku']);
                $quoteItem = $oosQuote->addProduct($product, $attachment['qty']);


                // then validate it again
                $quoteItem->getQuote()->setIsSuperMode(false);
                $eventData = [
                    'item' => $quoteItem
                ];
                $event = new \Magento\Framework\Event($eventData);
                $event->setName('foobar');
                $observer = new \Magento\Framework\Event\Observer();
                $observer->setData(array_merge(['event' => $event], $eventData));
                $this->quantityValidator->validate($observer);

                foreach ($quoteItem->getErrorInfos() as $errorInfo) {
                    if (!isset ($errorInfo['code'])) {
                        continue;
                    }

                    $errorCodes = [
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY
                    ];
                    if (in_array($errorInfo['code'], $errorCodes)) {
                        $subject->addWarning(__('Free attachment product (SKU: %1) is out of stock and will be delivered in a separate order once the stock is available', $attachment['sku']));
                        break;
                    }
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->warning($e);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->warning($e);
            }
        }

        return $result;
    }

    /**
     * Collect free attachment from prize
     *
     * @return void
     */
    public function collectFromPrize()
    {
        if (!$this->quoteId) {
            return;
        }

        foreach ($this->prizeManagement->getPrizeForCart($this->quoteId) as $prize) {
            $this->freeAttachments[$prize->getSku()] = [
                'sku' => $prize->getSku(),
                'qty' => $prize->getQty()
            ];
        }
    }

    /**
     * Collect free attachment from promotion
     *
     * @return void
     */
    public function collectFromPromo()
    {
        if (!$this->quoteId) {
            return;
        }

        foreach ($this->oosCaptureObserver->getOutOfStocks($this->quoteId) as $oos) {
            if (!$oos->getSalesruleId()) {
                continue;
            }

            $this->freeAttachments[$oos->getProductSku()] = [
                'sku' => $oos->getProductSku(),
                'qty' => $oos->getQty()
            ];
        }
    }

    /**
     * Collect free attachment from machine
     *
     * @return void
     */
    public function collectFromMachine()
    {
        if (!$this->quoteId) {
            return;
        }
    }

    /**
     * Collect free attachment from cumulative
     *
     * @return void
     */
    public function collectFromCumulative()
    {
        if (!$this->quoteId) {
            return;
        }
    }
}