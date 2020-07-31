<?php

namespace Riki\TagManagement\Block;

class CartIndex extends \Magento\Checkout\Block\Cart\AbstractCart
{

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * CartIndex constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        array $data = []
    )
    {
        $this->profileFactory = $profileFactory;
        $this->courseFactory = $courseFactory;
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * @return mixed|string
     */
    public function getCodeProfileSubscription()
    {
        $quote = $this->getQuote();
        if ($quote->hasData('riki_course_id') && $quote->getData('riki_course_id')) {
            $courseId = $quote->getData('riki_course_id');
            $course = $this->courseFactory->create()->load($courseId);
            if ($course->getId()) {
                return $course->getData('course_code');
            }
        }
        return false;
    }

    public function getListProductInQuote()
    {
        $getQuoteItems = $this->getItems();
        $products = [];
        $subscriptionCode = $this->getCodeProfileSubscription();
        $dimension24 = 'SPOT Product Purchase';
        if ($subscriptionCode) {
            $dimension24 = 'Subscription Product Purchase';
        }

        /**
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        $pos = 1;
        foreach ($getQuoteItems as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $quantity = $item->getQty();
            if ($item->getUnitQty() != 0) {
                $quantity = intval($item->getQty() / $item->getUnitQty());
            }

            $dimension40 = 'NO';
            $dimension41 = 'NO';

            $price = intval($item->getPriceInclTax());

            if ($item->getUnitCase() == 'CS') {
                $price = $price * $item->getUnitQty();
            }
            $priceDisplay = number_format(intval($price), 2, '.', '');

            if ($item->getUnitQty() != 0) {
                $quantity = intval($item->getQty() / $item->getUnitQty());
            }

            if ($item->getData('ampromo_rule_id')) {
                $dimension40 = 'YES';
            }
            if ($item->getData('is_riki_machine')) {
                $dimension41 = 'YES';
            }

            $dimension60 = $item->getProductType();

            $categories = $this->_getCategoryNames($item);
            $categories = mb_strcut($categories, 0, 120);
            $categories = addslashes($categories);

            $productName = mb_strcut($item->getName(), 0, 120);
            $productName = addslashes($productName);
            $products[] = "{
                                        'name':   '" .$productName . "' ,
                                        'id': '" . $item->getSku() . "',
                                        'dimension24': '" . $dimension24 . "',
                                        'dimension40': '" . $dimension40 . "',
                                        'dimension41': '" . $dimension41 . "',
                                        'dimension60': '" . $dimension60 . "',
                                        'quantity': " . $quantity . ",
                                        'category': '" .$categories . "',
                                        'variant': ' . $subscriptionCode . ',
                                        'brand': '',
                                        'price': '" . $priceDisplay . "',
                                        'position' : $pos
                           }";
            $pos++;
        }
        return implode(",", $products);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    private function _getCategoryNames(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection->addFieldToSelect('name');
        $categoryCollection->setCurPage(1);
        $categoryCollection->setPageSize(1);
        $categoryCollection->getFirstItem();
        $catNames = array();
        if ($categoryCollection->getSize()) {
                $catNames[] = $categoryCollection->getFirstItem()->getName();
        }
        return implode(',', $catNames);
    }

    /**
     * @return null
     */
    public function getMemberShipId()
    {
        if ($this->_customerSession->getCustomerId() != null) {
            $customer = $this->_customerSession->getCustomer();
            if ($customer->getData('consumer_db_id')) {
                return $customer->getData('consumer_db_id');
            }
        }
        return null;
    }
}