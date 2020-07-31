<?php
namespace Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Product;

class Grid extends \Riki\Subscription\Block\Adminhtml\Order\Create\Product\Grid
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * Grid constructor.
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param array $data
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\Config $salesConfig,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionModel,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    )
    {
        $this->_courseFactory = $courseFactory;
        $this->_registry = $registry;
        parent::__construct($context, $backendHelper, $productFactory, $catalogConfig, $sessionQuote, $salesConfig, $subscriptionPageHelper,$subscriptionModel,$categoryFactory,$data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        if (($id = $this->getRequest()->getParam('id'))
            && !$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID)
        ) {
            $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $id);
            /** @var \Riki\SubscriptionCourse\Model\Course $course */
            $course = $this->_courseFactory->create()->load($id);
            if ($course->getId() && $course->isHanpukai()) {
                $freq = current($course->getFrequencies());
                if (!$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID) && $freq) {
                    $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $freq);
                }
            }
        }
        if (($freq = $this->getRequest()->getParam('freq'))
            && !$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID)
        ) {
            $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $freq);
        }

        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $return = parent::_prepareColumns();

        $priceColumn = $this->getColumn('price');
        if ($priceColumn)  {
            $priceColumn->setData('renderer', 'Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Search\Grid\Renderer\Price');
        }

        return $return;
    }
}
