<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Model;

use Mirasvit\FraudCheck\Rule\Pool;
use Magento\Variable\Model\VariableFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Magento\Framework\Profiler;
use Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Score extends DataObject
{
    const VARIABLE_CODE = 'fraud_check_score';

    const STATUS_APPROVE = 'accept';
    const STATUS_REVIEW = 'review';
    const STATUS_REJECT = 'reject';

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var VariableFactory
     */
    protected $variableFactory;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Pool $pool,
        Config $config,
        Context $context,
        VariableFactory $variableFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->pool = $pool;
        $this->config = $config;
        $this->context = $context;
        $this->variableFactory = $variableFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->logger = $logger;

        parent::__construct();

        $this->load();
    }

    /**
     * @return \Mirasvit\FraudCheck\Api\Data\RuleInterface[]
     */
    public function getRules()
    {
        $rules = $this->pool->getRules();

        foreach ($rules as $code => $rule) {
            if ($this->getData("rule/$code")) {
                $rule->setImportance($this->getData("rule/$code/importance"));
                $rule->setIsActive($this->getData("rule/$code/is_active"));
            }
        }

        return $rules;
    }

    /**
     * @return \Mirasvit\FraudCheck\Model\Rule[] | \Mirasvit\FraudCheck\Model\ResourceModel\Rule\Collection
     */
    public function getUserRules()
    {
        return $this->ruleCollectionFactory->create()
            ->addFieldToFilter('is_active', 1);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setOrder($order)
    {
        $this->context->extractOrderData($order);

        if ($order->getData('fraud_score') && $order->getData('fraud_status')) {
            $this->setData('fraud_score', $order->getData('fraud_score'));
            $this->setData('fraud_status', $order->getData('fraud_status'));
        }
        if ($order->canHold()
            && (
                ($order->getData('fraud_status') == self::STATUS_REVIEW && $this->config->isHoldOrderOnReview())
                ||
                ($order->getData('fraud_status') == self::STATUS_REJECT && $this->config->isHoldOrderOnReject())
            )
        ) {
            $alreadyHolded = false;
            /** @var \Magento\Sales\Model\Order\Status\History $history */
            foreach ($order->getAllStatusHistory() as $history) {
                if ($history->getStatus() == \Magento\Sales\Model\Order::STATE_HOLDED) {
                    $alreadyHolded = true;
                    break;
                }
            }
            if ($alreadyHolded == false) {
                $order->hold()
                    ->addStatusHistoryComment("Fraud Risk Score too high", \Magento\Sales\Model\Order::STATE_HOLDED);
                $order->save();
            }
        }

        return $this;
    }

    /**
     * @param bool $force
     * @param bool $save
     * @return float
     */
    public function getFraudScore($force = false, $save = true)
    {
        if (!$this->hasData('fraud_score') || $force) {
            $orderId = $this->context->order->getId();
            $score = 0;

            $totalImportance = 0;
            foreach ($this->getRules() as $rule) {
                if ($rule->isActive()) {
                    $totalImportance += pow(2, $rule->getImportance());
                }
            }

            try {
                foreach ($this->getRules() as $code => $rule) {
                    $tsr = microtime(true);
                    if ($rule->isActive()) {
                        $score += $rule->getFraudScore() * (pow(2, $rule->getImportance()) / $totalImportance);
                    }

                    $this->logger->debug($code . ' : ' . round(microtime(true) - $tsr, 2));
                }

                $score += 1;
                $score = 100 - ($score / 2) * 100;
                $score = round($score);

                $this->setData('fraud_score', $score);
                $this->setData('fraud_status', $this->getFraudStatus($score));

                if ($save) {
                    $order = $this->context->order->load($orderId);
                    $order
                        ->setData('fraud_score', $this->getData('fraud_score'))
                        ->setData('fraud_status', $this->getData('fraud_status'))
                        ->save();
                }
            } catch (\Exception $e) {
            }
        }

        return $this->getData('fraud_score');
    }

    /**
     * @param int $score
     * @return string
     */
    public function getFraudStatus($score)
    {
        $status = self::STATUS_APPROVE;

        if ($score >= $this->getReviewThreshold()) {
            $status = self::STATUS_REJECT;
        } elseif ($score >= $this->getAcceptThreshold()) {
            $status = self::STATUS_REVIEW;
        }

        foreach ($this->getUserRules() as $rule) {
            $ruleStatus = $rule->getFraudStatus();

            if ($ruleStatus == self::STATUS_REJECT) {
                $status = self::STATUS_REJECT;
            } elseif ($ruleStatus == self::STATUS_REVIEW && $status != self::STATUS_REJECT) {
                $status = self::STATUS_REVIEW;
            } elseif ($ruleStatus == self::STATUS_APPROVE && $status != self::STATUS_REJECT) {
                $status = self::STATUS_APPROVE;
            }
        }

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key = '', $index = null)
    {
        if (!count($this->_data)) {
            $this->load();
        }

        return parent::getData($key, $index);
    }

    /**
     * @return $this
     */
    public function save()
    {
        $data = \Zend_Json_Encoder::encode($this->_data);

        $var = $this->variableFactory->create()
            ->loadByCode(self::VARIABLE_CODE);

        $var->setCode(self::VARIABLE_CODE)
            ->setPlainValue($data)
            ->save();

        return $this;
    }

    /**
     * @return $this
     * @throws \Zend_Json_Exception
     */
    public function load()
    {
        $var = $this->variableFactory->create()
            ->loadByCode(self::VARIABLE_CODE);

        if ($var->getPlainValue()) {
            $this->_data = \Zend_Json_Decoder::decode($var->getPlainValue());
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptThreshold()
    {
        if ($this->getData("status/accept")) {
            return $this->getData("status/accept");
        }

        return 30;
    }

    /**
     * @return int
     */
    public function getReviewThreshold()
    {
        if ($this->getData("status/review")) {
            return $this->getData("status/review");
        }

        return 80;
    }
}