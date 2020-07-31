<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Api;

interface RuleFactoryPoolInterface
{

    const DEFAULT_RULE_BOOL = 'Boolean';
    const DEFAULT_RULE_LITERAL = 'Literal';

    /**
     * @param $ruleId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRule($ruleId);

    /**
     * @param $ruleId
     * @return bool
     */
    public function hasRule($ruleId);
}
