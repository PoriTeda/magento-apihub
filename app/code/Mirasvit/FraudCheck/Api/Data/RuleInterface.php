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


namespace Mirasvit\FraudCheck\Api\Data;

interface RuleInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return IndicatorInterface[]
     */
    public function getIndicators();

    /**
     * @return void
     */
    public function collect();

    /**
     * From 1 - not important to 10 - very important
     * @return int
     */
    public function getImportance();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return float
     */
    public function getFraudScore();
}