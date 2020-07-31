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



namespace Mirasvit\FraudCheck\Rule;

use Magento\Framework\DataObject;
use Mirasvit\FraudCheck\Api\Data\RuleInterface;
use Mirasvit\FraudCheck\Model\Context;

abstract class AbstractRule extends DataObject implements RuleInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;

        parent::__construct();
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportance()
    {
        if ($this->hasData('importance')) {
            return $this->getData('importance');
        }

        return $this->getDefaultImportance();
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        if ($this->hasData('is_active')) {
            return $this->getData('is_active');
        }

        return true;
    }


    /**
     * @var array
     */
    protected $indicators;

    /**
     * @param float $score
     * @param string $label
     *
     * @return $this
     */
    public function addIndicator($score, $label)
    {
        $this->indicators[] = $this->context->getIndicatorFactory()->create()
            ->setScore($score)
            ->setLabel($label);

        return $this;
    }

    /**
     * @return \Mirasvit\FraudCheck\Api\Data\IndicatorInterface[]
     */
    public function getIndicators()
    {
        $this->indicators = [];
        $this->collect();

        return $this->indicators;
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudScore()
    {
        return $this->calculateFraudScore(-1, 1);
    }

    /**
     * @param float $min
     * @param float $max
     * @return float -1..1
     */
    public function calculateFraudScore($min, $max)
    {
        $totalScore = 0;
        foreach ($this->getIndicators() as $indicator) {
            $totalScore += $indicator->getScore();
        }

        $p = ($totalScore + abs($min)) / (abs($min) + abs($max));

        return -1 + $p * 2;
    }
}