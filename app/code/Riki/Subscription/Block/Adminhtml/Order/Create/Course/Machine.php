<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Course;

use Magento\Framework\View\Element\Template;

class Machine extends Template
{
    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subCourseHelper;

    /**
     * @var \Riki\SubscriptionPage\Model\PriceBox
     */
    protected $_priceBox;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    public function __construct(
        Template\Context $context,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\SubscriptionPage\Model\PriceBox $priceBox,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\Session\Quote $quote,
        array $data = []
    ) {
        $this->_priceBox = $priceBox;
        $this->_subCourseHelper = $subCourseHelper;
        $this->_courseModel = $courseModel;
        $this->_coreRegistry = $coreRegistry;
        $this->_sessionQuote = $quote;
        parent::__construct($context, $data);
    }

    /**
     * Get Machine list of current sub course
     *
     * @return array|bool|\Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getMachineOption()
    {
        $courseId = $this->getRequest()->getParam('id');

        $course = $this->_courseModel->load($courseId);
        if ($course->getId()) {
            // hanpukai, we need to get machine with discount
            //if ($course->getData('subscription_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                $courseId = $course->getId();
                $frequencyId = $course->getFrequencies();
                $this->_coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId[0]);
                $this->_coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
            //}
        }

        return $this->_subCourseHelper->getMachineOption($courseId);
    }

    public function getFullPrice($product)
    {
        $product->setQty(1);
        $fullPrice = $this->_priceBox->getFinalProductPrice($product);
        return $this->stripTags($fullPrice[1]);
    }

    /**
     * Get machine selected on previous time
     *
     * @return bool|mixed
     */
    public function getMachineSelected(){
        $request = $this->_sessionQuote->getQuote();
        foreach ($request->getAllItems() as $quoteItem){
            if($quoteItem->getData('is_riki_machine') == 1){
                return $quoteItem->getProductId();
            }
        }
        return false;
    }
}