<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Magento\Catalog\Model\Product;
use Nestle\Purina\Api\ProductInfoInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Message\Manager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
/**
 * Class ProductInfoCheck
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class ProductInfoCheck implements ProductInfoInterface
{
    /**
     * Quote repository
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Quote
     *
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Product repository
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Message manager
     *
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * Stock registry
     *
     * @var StockRegistryInterface
     */
    protected $stockItem;

    /**
     * Rest Request
     *
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * ProductInfoCheck constructor.
     *
     * @param CartRepositoryInterface    $quoteRepository   quote_repository
     * @param QuoteFactory               $quoteFactory      quote
     * @param ProductRepositoryInterface $productRepository product repository
     * @param Manager                    $messageManager    message
     * @param Request                    $request           request
     * @param StockRegistryInterface     $stockItem         product stock
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        ProductRepositoryInterface $productRepository,
        Manager $messageManager,
        Request $request,
        StockRegistryInterface $stockItem,
        \Magento\Framework\DataObject\Factory $objectFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
        $this->stockItem = $stockItem;
        $this->request = $request;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Gather product information
     *
     * @param mixed|null $cartItem cart_id
     *
     * @return array|bool|\Magento\Catalog\Api\Data\ProductInterface|mixed|string
     *
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductInfo(
        $cartId = null
    ) {
        $output = [];
        $this->request->setParam('from_purina_api', '1');
        $this->request->setParam('call_spot_order_api', 'call_spot_order_api');
        $failedCase = [];
        if (!empty($cartId)) {
            $quote = $this->quoteRepository->getActive($cartId);
            if ($quote->hasItems()) {
                $quote->removeAllItems();
            }
            $requestData = $this->request->getRequestData();
            if (array_key_exists("productItems", $requestData)) {
                foreach ($requestData['productItems'] as $item) {
                    $product = $this->checkProductBeforeAdd(
                        $item['sku'],
                        $item['qty']
                    );
                    if (is_string($product) !== true) {
                        if ($product->getTypeId() == 'bundle') {
                            $productsArray = $this->getBundleOptions($product);
                            $params = [
                                'product' => $product->getId(),
                                'bundle_option' => $productsArray,
                                'qty' => $item['qty']
                            ];
                        } else {
                            $params = [
                                'qty' => $item['qty']
                            ];
                        }
                        $params = $this->objectFactory->create($params);
                        try {
                            $quote->addProduct($product, $params);
                        } catch (\Exception $e) {
                            $failedCase['failed'][$item['sku']] = "Failed! add product to cart, sku: " . $item['sku'];
                        }
                    } else {
                        $failedCase['failed'][$item['sku']] = $product;
                    }
                }
                try {
                    $quote->collectTotals();
                    $this->quoteRepository->save($quote);
                    foreach ($quote->getAllItems() as $quoteItem) {
                        $productObject['item_id'] = $quoteItem->getId();
                        $productObject['quote_id'] = $quoteItem->getQuoteId();
                        $productObject['sku'] = $quoteItem->getSku();
                        $productObject['qty'] = $quoteItem->getQty();
                        $productObject['name'] = $quoteItem->getName();
                        $productObject['price'] = round($quoteItem->getRowTotalInclTax());
                        $productObject['product_type'] = $quoteItem->getProductType();
                        $output[] = $productObject;
                    }
                    if (!empty($failedCase)) {
                        $output[] = $failedCase;
                    }
                    return $output;
                } catch (\Exception $e) {
                    throw new CouldNotSaveException(__('Cannot create quote'));
                }
            }
        }
        return false;
    }

    /**
     * Check product before add to cart
     *
     * @param string $sku        product sku
     * @param int    $requestQty quantity
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|string
     */
    protected function checkProductBeforeAdd($sku, $requestQty)
    {
        try {
            $product = $this->productRepository->get($sku);
            if($product->getTypeId() != 'bundle') {
                $productStock = $this->stockItem->getStockItem($product->getId());
                $productQty = $productStock->getQty();
            } else {
                $productQty = false;
                $selectionCollection = $product->getTypeInstance()
                ->getSelectionsCollection(
                    $product->getTypeInstance()->getOptionsIds($product),
                    $product
                );
                foreach ($selectionCollection as $option) {
                    $product_id = $option->getProductId();
                    $stock = $this->stockItem->getStockItem($product_id);
                    if ($productQty === false) {
                        $productQty = $stock->getQty();
                    } else {
                        $productQty = min(array($productQty, $stock->getQty()));
                    }
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return "Product not found";
        }
        if ($productQty < $requestQty) {
            return "Insufficient Stock";
        }
        if ($product->getStatus() == Status::STATUS_DISABLED
        ) {
            return "Disabled Product";
        }
        return $product;
    }

    /**
     * get all the selection products used in bundle product
     * @param $product
     * @return mixed
     */
    private function getBundleOptions(Product $product)
    {
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );
        $bundleOptions = [];
        foreach ($selectionCollection as $selection) {
            $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
        }
        return $bundleOptions;
    }
}
