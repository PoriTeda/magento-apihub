<?php

namespace Riki\Subscription\Plugin\SalesRule\Model\Rule\Action\Discount;

class CalculatorFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    protected $classByType = [
        \Amasty\Promo\Model\Rule::SAME_PRODUCT  => 'Riki\Subscription\Model\Rule\Action\Discount\SameProduct',
        \Amasty\Promo\Model\Rule::PER_PRODUCT   => 'Riki\Subscription\Model\Rule\Action\Discount\Product',
        \Amasty\Promo\Model\Rule::WHOLE_CART    => 'Riki\Subscription\Model\Rule\Action\Discount\Cart',
        \Amasty\Promo\Model\Rule::SPENT         => 'Riki\Subscription\Model\Rule\Action\Discount\Spent',
    ];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_objectManager = $objectManager;
        $this->_registry = $registry;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $subject
     * @param \Closure $proceed
     * @param $type
     * @return mixed
     */
    public function aroundCreate(
        \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $subject,
        \Closure $proceed,
        $type
    ) {

        if(
            $this->_registry->registry(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME) &&
            isset($this->classByType[$type])
        ){
            return $this->_objectManager->create($this->classByType[$type]);
        }

        /**
         * For import order csv
         */
        if(
            $this->_registry->registry(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG) &&
            isset($this->classByType[$type])
        ){
            return $this->_objectManager->create($this->classByType[$type]);
        }

        return $proceed($type);
    }
}
