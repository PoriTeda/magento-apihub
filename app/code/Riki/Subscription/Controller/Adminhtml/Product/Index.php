<?php
namespace Riki\Subscription\Controller\Adminhtml\Product;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Riki\AdvancedInventory\Helper\Inventory as HelperInventory;
use \Magento\Catalog\Model\Product\Type as ProductType;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var array
     */
    protected $applicationLimitData = [];

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelper;
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockStateRepository;
    /**
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;
    /**
     * @var HelperInventory
     */
    protected $_helperInventory;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $helperData;


    public function __construct(
        HelperInventory $helperInventory,
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateRepository,
        StockRegistryProviderInterface $stockRegistryProvider,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionPage\Helper\Data $subPageHelper,
        \Riki\Subscription\Helper\Data $helperData,
        \Magento\Catalog\Helper\Product $productHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_sessionQuote = $sessionQuote;
        $this->subPageHelper = $subPageHelper;
        $this->_stockStateRepository = $stockStateRepository;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->_helperInventory = $helperInventory;
        $this->courseFactory = $courseFactory;
        $this->helperData = $helperData;
        $productHelper->setSkipSaleableCheck(true);
    }

    /**
     *
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('isAjax') == true) {
            $course_id = $this->getRequest()->getParam('id');
            $quote = $this->_sessionQuote->getQuote();
            $result  = [];
            if($this->_sessionQuote->getOrderId()) {
                $order = $this->_sessionQuote->getOrder();
                $oldSubscriptionInfo = $this->helperData->getSubscriptionInfo($order);
                if(isset($oldSubscriptionInfo['course_id']) and $oldSubscriptionInfo['course_id'] != $course_id) {
                    $result['success'] = false;
                    $result['message'] = __('Only one subscription allowed in the shopping cart');
                    return $this->getResponse()->setBody(json_encode($result));
                }
            }
            $quote_riki_course_id = $quote->getRikiCourseId();
            $customerId = $quote->getCustomerId();
            if ($quote_riki_course_id != null && $quote_riki_course_id != $course_id) {
                $result['success'] = false;
                $result['message'] = __('Only one subscription allowed in the shopping cart');
            } else {
                $quote = $this->_sessionQuote->getQuote();
                $arrProductId = [];
                foreach ($quote->getAllVisibleItems() as $allVisibleItem) {
                    $buyRequest = $allVisibleItem->getBuyRequest();
                    if (isset($buyRequest['options']['ampromo_rule_id'])) {
                        continue;
                    }
                    if ($allVisibleItem->getData('is_riki_machine') == 1) {
                        continue;
                    }
                    $arrProductId[] = $allVisibleItem->getProductId();
                }
                $errorCode = $this->isNotValid($course_id, $arrProductId, $customerId);
                $courseModel = $this->courseFactory->create()->load($course_id);
                $courseName = null;
                if ($courseModel->getId()) {
                    $courseName = $courseModel->getData('course_name');
                }
                switch ($errorCode) {
                    case 1: /*Spot*/
                        $result['success'] = false;
                        $result['message'] = __('shopping cart cannot contain SPOT and subscription at the same time');
                        break;
                    default:
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        /* @var $collection \Riki\SubscriptionCourse\Model\ResourceModel\Course */
                        $storeId = $this->_sessionQuote->getQuote()->getStore()->getId();
                        $product_course = $objectManager->create('Riki\SubscriptionCourse\Model\ResourceModel\Course')->getAllProductByCoursePieceCase($course_id, $storeId);
                        if (!$product_course || empty($product_course->getItems())) {
                            $result['success'] = false;
                            $result['message'] = __('This course does not have any products, please select another course');
                        } else {
                            $outOfStock = $this->checkOutOfStockHanpukai($course_id, $product_course);
                            if ($outOfStock) {
                                $result['success'] = false;
                                $result['message'] = __('This Hanpukai course is out of stock');
                            } else {
                                $response = $this->resultPageFactory->create();
                                $layout = $response->addHandle('sales_order_create_index')->getLayout();
                                $result['success'] = true;
                                $result['message'] = $layout->getBlock('product_course')->toHtml();
                                $result['course_id'] = $course_id;
                                $result['hanpukai'] = $this->subPageHelper->getSubscriptionType($course_id) == 'hanpukai' ? true : false;
                            }
                        }
                }

            }

            $this->getResponse()->setBody(json_encode($result));
        }
    }
    protected function isNotValid($courseId, $arrProductId,$customerId)
    {
        $objCourse = $this->_objectManager->create('Riki\SubscriptionCourse\Model\Course');
        $objCourse->load($courseId);

        if(empty($objCourse->getId())) { // not yet set course_id in quote
            return 0;
        }
        // Check product must belong course
        $objCourseHelper = $this->_objectManager->get('Riki\SubscriptionCourse\Helper\Data');
        $errorCode = $objCourseHelper->checkCartIsValidForCourse($arrProductId, $courseId);

        if($errorCode === 1){
            return 1;
        } // have spot
        return 0;
    }
    public function checkOutOfStockHanpukai($courseId,$productCourse){
        if($this->subPageHelper->getSubscriptionType($courseId) == 'hanpukai'){
            foreach ($productCourse as $product){
                /* @var \Magento\Catalog\Model\Product $product */
                if ($product->getTypeId() != ProductType::TYPE_BUNDLE) {
                    $stockItem = $this->stockRegistryProvider->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    if ($stockItem->getManageStock()) {

                        if($stockItem->getIsInStock()){
                            if(!$stockItem->getBackorders()){
                                return $stockItem < $product->getData('fix_qty');
                            }
                        }else{
                            return true;
                        }
                    }
                } else {
                    if ($this->_helperInventory->checkWarehouseBundle($product, $product->getData('fix_qty')) == false) {
                        return true;
                    }
                }
            }
            return false;
        }
        return false;
    }
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::product_course');
    }
}
