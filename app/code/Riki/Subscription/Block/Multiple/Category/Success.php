<?php

namespace Riki\Subscription\Block\Multiple\Category;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->stdTimezone = $stdTimezone;
        parent::__construct($context, $data);
    }

    /**
     * Set page title
     *
     * @return mixed
     */
    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Change complete'));
        return parent::_prepareLayout();
    }

    /**
     * Get success data from session manager
     *
     * @return mixed
     */
    public function getSuccessData()
    {
        return $this->registry->registry('success_data');
    }

    /**
     * Get profile id
     *
     * @return mixed
     */
    public function getProfileId()
    {
        $successData = $this->getSuccessData();

        if (isset($successData['profile_id'])) {
            return $successData['profile_id'];
        }

        return false;
    }

    /**
     * Get course code
     *
     * @return mixed
     */
    public function getCourseCode()
    {
        $successData = $this->getSuccessData();

        if (isset($successData['course_code'])) {
            return $successData['course_code'];
        }

        return false;
    }

    /**
     * Get total amount of added products
     *
     * @return mixed
     */
    public function getTotalAmountOfAddedProducts()
    {
        $successData = $this->getSuccessData();
        $totalAmount = 0;

        if (isset($successData['total_amount'])) {
            return $successData['total_amount'];
        }

        return $totalAmount;
    }

    /**
     * Get current timestamp
     *
     * @return string
     */
    public function getTimestamp()
    {
        return $this->stdTimezone->date()->format('YmdHis');
    }

    /**
     * Get added products
     *
     * @return array
     */
    public function getAddedProducts()
    {
        $successData = $this->getSuccessData();

        if (isset($successData['added_products'])) {
            return $successData['added_products'];
        }

        return [];
    }
}
