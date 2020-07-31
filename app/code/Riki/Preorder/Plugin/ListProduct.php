<?php

namespace Riki\Preorder\Plugin;

class ListProduct
{
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    public function __construct(\Riki\Preorder\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function aroundGetReviewsSummaryHtml(
        \Magento\Catalog\Block\Product\ListProduct $subject,
        \Closure $closure,
        \Magento\Catalog\Model\Product $product,
        $templateType = false,
        $displayIfNoReviews = false
    ) {
        $htmlPreorder = '';
        if($this->helper->getIsProductPreorder($product)) {
            $htmlPreorder = $subject->getLayout()->createBlock('Riki\Preorder\Block\Product\ListProduct\Preorder')->setProduct($product)->setTemplate('product/list/preorder.phtml')->toHtml();
        }

        return $htmlPreorder.$closure($product, $templateType, $displayIfNoReviews);
    }
}