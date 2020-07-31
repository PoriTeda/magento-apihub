<?php
namespace Riki\GoogleTagManager\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;

class Ga extends \Magento\GoogleTagManager\Block\Ga
{
    /**
     * Config paths for using throughout the code
     */
    const XML_PATH_TRACKER_NUMBER = 'google/analytics/tracker_number';
    const XML_PATH_OPTIMIZE_TRACKER = 'google/analytics/optimize_tracker';
    const XML_PATH_OPTIMIZE_TIMEOUT = 'google/analytics/optimize_timeout';

    /** @var \Riki\Sales\Helper\Data  */
    protected $_salesHelper;

    /** @var \Riki\Questionnaire\Helper\Data  */
    protected $_questionnaireHelper;

    /** @var \Riki\Customer\Helper\CustomerHelper  */
    protected $_rikiCustomerHelper;

    /** @var \Bluecom\PaymentFee\Helper\Data  */
    protected $paymentFeeHelper;

    /** @var \Riki\Catalog\Helper\Data  */
    protected $rikiCatalogHelperData;

    /** @var \Magento\Tax\Model\Config  */
    protected $taxConfig;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    /** @var \Magento\Catalog\Helper\Data  */
    protected $catalogHelper;

    /** @var \Riki\Subscription\Api\ProfileRepositoryInterface  */
    protected $profileRepository;

    /** @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface  */
    protected $courseRepository;

    /**
     * Ga constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection
     * @param \Magento\GoogleTagManager\Helper\Data $googleAnalyticsData
     * @param \Magento\Cookie\Helper\Cookie $cookieHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Riki\Sales\Helper\Data $salesHelper
     * @param \Riki\Questionnaire\Helper\Data $questionnaireHelper
     * @param \Riki\Customer\Helper\CustomerHelper $customerHelper
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     * @param \Riki\Catalog\Helper\Data $rikiCatalogHelperData
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection,
        \Magento\GoogleTagManager\Helper\Data $googleAnalyticsData,
        \Magento\Cookie\Helper\Cookie $cookieHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Riki\Sales\Helper\Data $salesHelper,
        \Riki\Questionnaire\Helper\Data $questionnaireHelper,
        \Riki\Customer\Helper\CustomerHelper $customerHelper,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        \Riki\Catalog\Helper\Data $rikiCatalogHelperData,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        array $data = []
    ) {
        $this->taxConfig = $taxConfig;
        $this->catalogHelper = $catalogHelper;
        $this->_salesHelper = $salesHelper;
        $this->_questionnaireHelper = $questionnaireHelper;
        $this->_rikiCustomerHelper = $customerHelper;
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->rikiCatalogHelperData = $rikiCatalogHelperData;
        $this->categoryRepository = $categoryRepository;
        $this->profileRepository = $profileRepository;
        $this->courseRepository = $courseRepository;

        parent::__construct(
            $context,
            $salesOrderCollection,
            $googleAnalyticsData,
            $cookieHelper,
            $jsonHelper,
            $data
        );
    }

    /**
     * Render information about specified orders and their items
     * @return string
     */
    public function getOrdersData()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }
        $collection = $this->_salesOrderCollection->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        $result = [];
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection as $order) {
            $paymentFeeExclTax = intval($this->paymentFeeHelper->getFeeExcludeTax($order->getData('base_fee')));

            $actionField['id'] = $order->getIncrementId();
            $actionField['affiliation'] = 'shop.nestle.jp';
            $actionField['revenue'] = $order->getBaseSubtotal() +
                $paymentFeeExclTax +
                $order->getData('gw_items_base_price');
            $actionField['tax'] = (string)number_format($order->getData('tax_riki_total'), 2, '.', '');
            $actionField['shipping'] = (string)number_format($order->getBaseShippingAmount(), 2, '.', '');
            $actionField['coupon'] = (string)$order->getCouponCode();

            $hasFreeGift = false;

            $discountExclTax = $this->getShippingDiscountAmountBeforeTax($order);

            $variant = '';

            if ($profileId = $order->getData('subscription_profile_id')) {
                try {
                    $profile = $this->profileRepository->get($profileId);

                    if ($courseId = $profile->getCourseId()) {
                        $course = $this->courseRepository->get($courseId);

                        $variant = $course->getCode();
                    }
                } catch (\Exception $e) {
                    $variant = '';
                }
            }

            $products = [];
            /** @var \Magento\Sales\Model\Order\Item $item*/
            foreach ($order->getAllVisibleItems() as $item) {
                $product['id'] = $item->getSku();
                $product['name'] = $this->rikiCatalogHelperData->subStr($item->getName(), 100);
                $product['dimension24'] = $order->getData('subscription_profile_id')
                    ? 'Subscription Product Purchase' : 'SPOT Product Purchase';
                $product['dimension40'] = $this->_salesHelper->isSpotFreeGift($item) ? 'YES' : 'NO';
                $product['dimension41'] = ($item->getData('is_riki_machine') && $item->getData('price') == 0)
                    ? 'YES' : 'NO';
                $product['dimension56'] = ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE)
                    ? 'Bundle' : 'Simple';
                $product['quantity'] = intval($item->getQtyOrdered());
                $product['price'] = (string)number_format(
                    $item->getBasePriceInclTax(),
                    2,
                    '.',
                    ''
                );
                $product['metric2'] = (string)abs($item->getData('base_discount_amount'));
                $product['category'] = $this->rikiCatalogHelperData->subStr(
                    $this->getCategoryName($item->getProduct()),
                    100
                );
                $product['variant'] = $this->rikiCatalogHelperData->subStr(
                    $variant,
                    100
                );
                $product['brand'] = '';
                $product['coupon'] = '';
                $products[] = $product;

                if ($this->_salesHelper->isFreeGift($item)) {
                    $hasFreeGift = true;
                }

                $discountExclTax += $item->getData('discount_amount_excl_tax');
            }

            $actionField['revenue'] = number_format(
                round($actionField['revenue'] - abs($discountExclTax)),
                2,
                '.',
                ''
            );

            $json['event'] = 'purchase';

            $memberShipId = $this->_rikiCustomerHelper->getConsumerIdByCustomerId($order->getCustomerId());

            $json['membershipID'] = $memberShipId? $memberShipId : '';
            $json['transactionTotal'] = (string)intval($order->getBaseGrandTotal());
            $json['userPointsUsed'] = $order->getData('used_point')? 'YES':'NO';
            $json['userPointsUsedAmount'] = (string)intval($order->getData('used_point'));
            $json['orderDiscountCouponAmount'] = (string)abs($discountExclTax);

            $paymentMethod = $order->getPayment();

            if ($paymentMethod instanceof \Magento\Payment\Model\InfoInterface) {
                $json['paymentMethod'] = $this->rikiCatalogHelperData->subStr(
                    $paymentMethod->getMethodInstance()->getTitle(),
                    100
                );
            } else {
                $json['paymentMethod'] = '';
            }

            $json['freeProductIncluded'] = $hasFreeGift? 'YES' : 'NO';
            $json['reasonForPurchase'] = $this->getReasonPurchase($order);
            $json['checkoutStepName'] = 'Complete Order';
            $json['paymentFee'] = (string)$paymentFeeExclTax;
            $json['giftWrappingFee'] = (string)intval($order->getData('gw_items_base_price'));

            $json['ecommerce']['purchase']['actionField'] = $actionField;
            $json['ecommerce']['purchase']['products'] = $products;
            $json['ecommerce']['currencyCode'] = $this->getStoreCurrencyCode();


            $result[] = 'dataLayer.push(' . json_encode($json) . ");\n";
        }
        return implode("\n", $result);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getCategoryName(\Magento\Catalog\Model\Product $product){

        $categoryIds = $product->getCategoryIds();

        if (count($categoryIds)) {
            try {
                $category = $this->categoryRepository->get(array_shift($categoryIds));

                return $category->getName();
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getReasonPurchase(\Magento\Sales\Model\Order $order)
    {
        $replies = $this->_questionnaireHelper->getRepliesByAnswerOrder($order);

        foreach ($replies as $reply) {
            if ($reply['parent_choice_id'] == 0) {
                return $reply['label'];
            }
        }

        return '';
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float|null
     */
    public function getShippingDiscountAmountBeforeTax(\Magento\Sales\Model\Order $order)
    {

        $store = $order->getStoreId();

        $shippingAddress = $order->getShippingAddress();

        $price = $order->getShippingDiscountAmount();

        $pseudoProduct = new \Magento\Framework\DataObject();
        $pseudoProduct->setTaxClassId($this->taxConfig->getShippingTaxClass($store));

        $billingAddress = false;
        if ($shippingAddress && $shippingAddress->getQuote() && $shippingAddress->getQuote()->getBillingAddress()) {
            $billingAddress = $shippingAddress->getQuote()->getBillingAddress();
        }

        $price = $this->catalogHelper->getTaxPrice(
            $pseudoProduct,
            $price,
            false,
            $shippingAddress,
            $billingAddress,
            null,
            $store,
            true
        );

        return $price;
    }
}
