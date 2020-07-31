<?php
namespace Riki\ProductStockStatus\Observer;

class UpdateProductStockStatus implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\ProductStockStatus\Helper\StockData|\Riki\ProductStockStatus\Helper\StockData\Proxy
     */
    protected $stockHelper;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * UpdateProductStockStatus constructor.
     * @param \Riki\ProductStockStatus\Helper\StockData\Proxy $stock
     * @param \Magento\Catalog\Api\ProductRepositoryInterface\Proxy $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\ProductStockStatus\Helper\StockData\Proxy $stock,
        \Magento\Catalog\Api\ProductRepositoryInterface\Proxy $productRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry
    ) {
        $this->stockHelper = $stock;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('skip_validate_by_oos_order_generating')) {
            return $this;
        }
        $collection = $observer->getData('collection');
        if (!$collection instanceof \Magento\Quote\Model\ResourceModel\Quote\Item\Collection) {
            return;
        }
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($collection as $item)
        {
            $product = $this->getProductById($item->getData('product_id'));
            if(is_null($product) || !$product->getId() || $product->getId() != $item->getData('product_id')){
                continue;
            }
            $stockMessageArr = $this->stockHelper->getStockStatusMessage($product);
            if (array_key_exists('class', $stockMessageArr)
                && array_key_exists('message', $stockMessageArr)
            ) {
                $classMessage = $stockMessageArr['class'];
                $textMessage = $stockMessageArr['message'];
            } else {
                $classMessage = '';
                $textMessage = $this->stockHelper->getOutStockMessageByProduct($product);
            }
            $item->setData('product_stock_class', $classMessage);
            $item->setData('product_stock_message', $textMessage);
        }
    }
    /**
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId)
    {
        try{
            return $this->productRepository->getById($productId);
        }catch (\Exception $e)
        {
            $this->logger->critical($e);
            return null;
        }
    }
}