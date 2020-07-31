<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\QtyRestriction;

use Magento\Backend\Block\Widget;
use \Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions;

class Option extends Widget
{
    /**
     * @var string
     */
    protected $_template = 'qty/maximum_options.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Option constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * Get maximum qty restriction
     *
     * @return array
     */
    public function getMaximumQtyRestrictionData()
    {
        $subscriptionCourse = $this->coreRegistry->registry('subscription_course');
        if ($subscriptionCourse->getCourseId()) {
            $condition = $subscriptionCourse->getData('maximum_qty_restriction');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if ($serializeData['maximum']['option'] == QtyRestrictionOptions::OPTION_VALUE_CUSTOM_ORDER) {
                    if (isset($serializeData['maximum']['qtys'])) {
                        return $serializeData['maximum']['qtys'];
                    } else {
                        return [];
                    }
                }
            }
        }

        return [];
    }
}
