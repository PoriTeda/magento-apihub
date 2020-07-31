<?php
namespace Riki\SubscriptionCourse\Block\Frontend;

class CartInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /* @var \Magento\Checkout\Model\Type\Onepage */
    protected $checkoutModelTypeOnepage;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $subCourseModel;

    /* @var \Riki\Subscription\Model\Frequency\Frequency */
    protected $subFrequencyModel;

    protected $localStorage = [];


    public function __construct(
        \Riki\Subscription\Model\Frequency\Frequency $subFrequencyModel,
        \Riki\SubscriptionCourse\Model\Course $subCourseMode,
        \Magento\Checkout\Model\Type\Onepage $checkoutModelTypeOnepage,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->subFrequencyModel = $subFrequencyModel;
        $this->subCourseModel = $subCourseMode;
        $this->checkoutModelTypeOnepage = $checkoutModelTypeOnepage;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    public function getCourseInfoForCart() {

        if( ! empty($this->localStorage)) {
            return $this->localStorage['quote:courseinfo'];
        }

        try {
            $objQuote = $this->checkoutModelTypeOnepage->getQuote();

            if( ! $objQuote->hasData("riki_course_id")) return ['', ''];

            $intCourseId = (int)$objQuote->getData("riki_course_id");

            if(empty($intCourseId)) return ['', ''];

            $intFrequencyId = (int)$objQuote->getData("riki_frequency_id");

            $objCourse = $this->subCourseModel;
            $objCourse->load($intCourseId);

            $objFrequency = $this->subFrequencyModel;
            $objFrequency->load($intFrequencyId);

            $this->localStorage['quote:courseinfo'] =  [
                $objCourse,
                $objFrequency
            ];

            return $this->localStorage['quote:courseinfo'];
        }
        catch (\Exception $e) {
            return ['', ''];
        }


    }

    public function showTextOnCartDetail()
    {
        list($objCourse, $objFrequency) = $this->getCourseInfoForCart();

        if($objCourse === "") return "";


        return __('Subscription item list: ') . sprintf("%s (%s - %s)", $objCourse->getData("course_name"), $objFrequency->getData("frequency_interval"), $objFrequency->getData("frequency_unit"));
    }
}
