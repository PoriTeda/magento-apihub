<?php


namespace Riki\Theme\Plugin\Checkout\CustomerData;


class MiniCart
{

    /**
     * @var \Riki\SubscriptionPage\Block\ViewModel\ProductCategory
     */
    private $viewDataModel;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * MiniCart constructor.
     * @param \Riki\SubscriptionPage\Block\ViewModel\ProductCategory $productCategory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        \Riki\SubscriptionPage\Block\ViewModel\ProductCategory $productCategory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    )
    {
        $this->viewDataModel = $productCategory;
        $this->checkoutSession = $checkoutSession;
        $this->_courseFactory = $courseFactory;
    }

    /**
     * Add link to cart in cart sidebar to view grid with failed products
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $result["m-minicart-data"] = $this->viewDataModel->getCurrentQuoteItems(false);
        $result["rikiHanpukaiQty"] = $this->getQuote()->getData('riki_hanpukai_qty');
        $result["rikiCourseId"] = $this->getQuote()->getData('riki_course_id');
        $result["rikiCourseName"] = '';
        $result["frequencyId"] = $this->getQuote()->getData('riki_frequency_id');
        //case hanpukai get rikiCourseName
        if (!empty($result["rikiCourseId"])) {
            $result["rikiCourseName"] = $this->getRikiCourseName($result["rikiCourseId"]);
        }
        $result["quoteId"] = $this->getQuote()->getData('entity_id');

        return $result;
    }

    /**
     * Get active quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * @param $rikiCourseId
     * @return mixed|string
     */
    private function getRikiCourseName($rikiCourseId)
    {
        if ($rikiCourseId != null) {
            $courseModel = $this->_courseFactory->create()->load($rikiCourseId);
            return $courseModel->getData('course_name');
        }
        return '';
    }
}