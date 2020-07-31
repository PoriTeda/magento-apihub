<?php
namespace Riki\Checkout\Helper;

class PromotionCollector extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STATUS = 'status';
    const STATUS_START = 1;
    const STATUS_STOP = 2;

    const RESULT = 'result';


    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\Framework\Helper\Container\Request
     */
    protected $_requestHelper;

    /**
     * PromotionCollector constructor.
     * @param \Riki\Framework\Helper\Container\Request $requestHelper
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Container\Request $requestHelper,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->_requestHelper = $requestHelper;
        $this->_requestHelper->setIdentifier(get_class($this));
        parent::__construct($context);


    }

    /**
     * Trigger collect
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     */
    public function collect(\Magento\Quote\Model\Quote $quote)
    {
        $this->startCollect();
        $quote->setTotalsCollectedFlag(false)
            ->collectTotals();
        $this->stopCollect();

        return $this;
    }


    /**
     * Mark collector is start
     *
     * @return $this
     */
    public function startCollect()
    {
        if ($this->isCollectStarted()) {
            return $this;
        }

        $this->_requestHelper->setData(self::STATUS, self::STATUS_START);
        return $this;
    }

    /**
     * Check collector is started?
     *
     * @return bool
     */
    public function isCollectStarted()
    {
        return $this->_requestHelper->getData(self::STATUS) == self::STATUS_START;
    }

    /**
     * Mark collector is stopped
     *
     * @return $this
     */
    public function stopCollect()
    {
        $this->_requestHelper->setData(self::STATUS, self::STATUS_STOP);
        return $this;
    }

    /**
     * Check collector is stopped?
     *
     * @return bool
     */
    public function isCollectStopped()
    {
        return $this->_requestHelper->getData(self::STATUS) == self::STATUS_STOP;
    }

    /**
     * Resolve a rule
     *
     * @param $rule
     * @return $this
     */
    public function resolve($rule)
    {
        if ($rule instanceof \Magento\SalesRule\Model\Rule
            || $rule instanceof \Magento\SalesRule\Model\Data\Rule
        ) {
            $ruleData = [
                'type' => 'sales_rule',
                'id' => $rule->getId(),
                'name' => trim($rule->getStoreLabel() ?: $rule->getName())
            ];
        }

        if (isset($ruleData)) {
            $key = $ruleData['type'] . '_' . $ruleData['id'];
            $this->_requestHelper->pushData(self::RESULT, [$key => $ruleData]);
        }

        return $this;
    }

    /**
     * Get collected result
     *
     * @return array
     */
    public function getResult()
    {
        return $this->_requestHelper->getData(self::RESULT);
    }
}