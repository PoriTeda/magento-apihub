<?php

namespace Riki\BackOrder\Plugin\Sales\Model\AdminOrder;

use \Riki\BackOrder\Helper\Data as BackOrderHelper;
use \Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;
use Riki\SubscriptionCourse\Model\CourseFactory;

class Create
{
    protected $_helper;

    protected $_adminHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CourseFactory
     */
    protected $courseFactory;


    /**
     * Create constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param BackOrderHelper $helper
     * @param \Riki\BackOrder\Helper\Admin $adminHelper
     * @param CourseFactory $courseFactory
     * @param \Magento\Framework\Message\ManagerInterface $message
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        BackOrderHelper $helper,
        \Riki\BackOrder\Helper\Admin $adminHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Message\ManagerInterface $message
    ){
        $this->scopeConfig = $scopeConfigInterface;
        $this->_helper = $helper;
        $this->_adminHelper = $adminHelper;
        $this->courseFactory = $courseFactory;
        $this->_messageManager = $message;
    }

    /**
     * Validate total qty of cart
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param callable $proceed
     * @param $products
     *
     * @return bool|\Exception|LocalizedException
     *
     * @throws LocalizedException
     */
    public function aroundAddProducts(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Closure $proceed,
        $products
    ) {
        $quote = $subject->getQuote();
        if (!$quote->getId()) {
            return $proceed($products);
        }
        //don't use back order with subscription multiple machine
        if ($courseId = $quote->getRikiCourseId()) {
            $course = $this->courseFactory->create()->load($courseId);
            if ($course->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                return $proceed($products);
            }
        }
        $requestData = [];
        $totalQtyBuy = 0;
        foreach ($products as $productId => $config) {
            if (!isset($requestData[$productId])) {
                $requestData[$productId] = 0;
            }
            $totalQtyBuy = $totalQtyBuy + $config['qty'];

            $requestData[$productId] += $config['qty'];
        }
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $totalQtyBuy > $maximumOrderQtyConfig) {
            $messageError = __('I am sorry. <br> In the Nestle Online shopping online shop, we have restricted the maximum number of items as %1 at one order. Sorry to trouble you, but please change the number of items in the cart to %2 pieces or less.', $maximumOrderQtyConfig,  $maximumOrderQtyConfig);
            throw new LocalizedException($messageError);
        }
        $validate = $this->_adminHelper->validateForAddAction($requestData);

        if ($validate !== true) {
            return $validate;
        }

        return $proceed($products);
    }

    /**
     * apply back-order validation rule for re-order action
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null $qty
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    public function beforeInitFromOrderItem(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qty = null
    ){
        if ($orderItem->getId()) {

            $validateResult = $this->_adminHelper->validateItem($orderItem, $qty);

            if(is_string($validateResult)){
                $this->_messageManager->addError(__($validateResult));
                $orderItem->setId(null);
            }
        }

        return [$orderItem, $qty];
    }

    /**
     * Validate Maximum Order Qty
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param callable $proceed
     * @param $items
     * @return mixed
     *
     * @throws LocalizedException
     */
    public function aroundUpdateQuoteItems(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Closure $proceed,
        $items
    ) {
        $quote = $subject->getQuote();
        if (is_array($items)) {
            $totalQtyBuy = 0;
            foreach ($items as $itemId => $info) {
                $quoteItem = $quote->getItemById($itemId);
                if($quoteItem == null) {
                    unset($items[$itemId]);
                }else {
                    $totalQtyBuy = $totalQtyBuy + $info['qty'];
                }
            }
            $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
            if ($maximumOrderQtyConfig > 0 && $totalQtyBuy > $maximumOrderQtyConfig) {
                $messageError = __('I am sorry. <br> In the Nestle Online shopping online shop, we have restricted the maximum number of items as %1 at one order. Sorry to trouble you, but please change the number of items in the cart to %2 pieces or less.', $maximumOrderQtyConfig, $maximumOrderQtyConfig);
                throw new LocalizedException($messageError);
            }
        }
        return $proceed($items);

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
}