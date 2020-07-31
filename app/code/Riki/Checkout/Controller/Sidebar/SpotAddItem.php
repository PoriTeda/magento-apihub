<?php

namespace Riki\Checkout\Controller\Sidebar;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Sidebar;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Checkout\Model\Cart;

class SpotAddItem extends Action
{
    /**
     * @var Sidebar
     */
    protected $sidebar;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelperData;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subCourseModelFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelectionConfig;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        Cart $cart,
        Context $context,
        Sidebar $sidebar,
        LoggerInterface $logger,
        Data $jsonHelper,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\DataObject\Factory $objectFactory
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->scopeConfig = $scopeConfigInterface;
        $this->catalogHelper = $catalogHelper;
        $this->urlInterface = $context->getUrl();
        $this->categoryRepository = $categoryRepositoryInterface;
        $this->subCourseModelFactory = $courseFactory;
        $this->subCourseHelperData = $subCourseHelper;
        $this->cart = $cart;
        $this->sidebar = $sidebar;
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->stockState = $stockState;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->objectFactory = $objectFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $request = $this->getRequest();
            $id = (int)$request->getParam('item_id');
            $quote = $this->cart->getQuote();
            $storeId = $this->storeManager->getStore()->getId();

            $product = $this->productRepository->getById($id, false, $storeId);

            foreach($this->cart->getItems() as $item){
                if($item->getProduct()->getId() == $id){
                    $this->cart->removeItem($item->getId());
                }
            }
            $params = [
                'product' => $product->getId(),
                'qty' => (int)$request->getParam('item_qty')
            ];
            if ($product->getTypeId() == "bundle") {
                $productsArray = $this->getBundleOptions($product);
                $params['bundle_option'] = $productsArray;
            }

            $params = $this->objectFactory->create($params);

            $this->cart->addProduct($product, $params);
            
            $disableChangeDeliveryDate = $this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig();
            if ($disableChangeDeliveryDate) {
                $quote->setData('allow_choose_delivery_date', 0);
            }

            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            return $this->resultJsonFactory->create()->setData([
                'success' => true,
                'id' => $quote->getItemByProduct($product)->getId()
            ]);
        } catch (\Exception $e){
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Get Store Config
     *
     * @param $path
     *
     * @return string
     */
    public function getConfig($path)
    {

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Calculate Total Qty Info
     *
     * @param $arrChangeItemInfo
     *
     * @return int
     */
    public function calculateTotalQtyInFo($arrChangeItemInfo)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $totalQty = 0;
        $quote = $this->cart->getQuote();
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($item->getProduct()->getId());
                if ($arrChangeItemInfo['item_id'] == $item->getId()) {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($arrChangeItemInfo['qty'] / $unitQty);
                    }else {
                        $totalQty = $totalQty + $arrChangeItemInfo['qty'];
                    }
                } else {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($item->getQty() / $unitQty);
                    }else {
                        $totalQty = $totalQty + $item->getQty();
                    }
                }
            }
        }

        return $totalQty;
    }

    /**
     * Compile JSON response
     *
     * @param string $error
     * @return Http
     */
    protected function jsonResponse($error = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($this->sidebar->getResponseData($error))
        );
    }

    protected function jsonErrorWhenUpdateQty($error, $itemId, $itemValue, $isCase = '')
    {
        $response = [
            'success' => false,
            'error_message' => $error,
            'type' => 'updateQty',
            'itemId' => $itemId,
            'itemValue' => $itemValue,
            'is_case' => $isCase
        ];
        return $this->getResponse()->representJson( $this->jsonHelper->jsonEncode($response));
    }

    /**
     * get all the selection products used in bundle product
     * @param $product
     * @return mixed
     */
    private function getBundleOptions($product)
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
