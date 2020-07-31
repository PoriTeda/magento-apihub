<?php

namespace Riki\SalesRule\Plugin;

class LoadProductAttributes
{
    public function beforeValidate(\Magento\SalesRule\Model\Rule\Condition\Product $subject, \Magento\Framework\Model\AbstractModel $model)
    {
        $product = $model->getProduct();

        if ($product && !$product->hasData($subject->getAttribute())) {
            $product->load($product->getId());
            $model->setProduct($product);
        }

        return [$model];
    }
}