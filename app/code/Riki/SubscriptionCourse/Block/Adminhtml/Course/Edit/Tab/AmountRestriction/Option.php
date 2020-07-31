<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\AmountRestriction;

use Magento\Backend\Block\Widget;

class Option extends Widget
{
    /**
     * @var string
     */
    protected $_template = 'amount/minimum_options.phtml';
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
     * get minimum amount restriction
     * @return array
     */
    public function getMinimumAmountRestrictionData()
    {
        $subscriptionCourse = $this->coreRegistry->registry('subscription_course');
        if ($subscriptionCourse->getCourseId()) {
            $condition = $subscriptionCourse->getData('oar_condition_serialized');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if ($serializeData['minimum']['option']==2) {
                    if(isset($serializeData['minimum']['amounts'])) {
                        return $serializeData['minimum']['amounts'];
                    } else {
                        return [];
                    }
                }
            }
        }
        return [];
    }
}
