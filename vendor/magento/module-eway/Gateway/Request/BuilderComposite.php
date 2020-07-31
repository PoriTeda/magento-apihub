<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderComposite as PaymentBuilderComposite;

/**
 * Class BuilderComposite
 */
class BuilderComposite extends PaymentBuilderComposite
{
    /**
     * @inheritdoc
     */
    protected function merge(array $result, array $builder)
    {
        return array_replace_recursive($result, $builder);
    }
}
