<?php

namespace Riki\Preorder\Model;

class PreOrderValidator
{

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    protected $productRepository;

    /** @var \Riki\Preorder\Helper\Data  */
    protected $helper;

    /**
     * PreOrderValidator constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Preorder\Helper\Data $helper
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Preorder\Helper\Data $helper
    )
    {
        $this->productRepository = $productRepository;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateBeforeSubmit(\Magento\Quote\Model\Quote $quote)
    {
        $items = $quote->getAllItems();

        $normalCount = 0;
        $preOrderCount  = 0;

        $checkedIds = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {

            $productId = $item->getProductId();

            if (
                $item->getParentItemId() ||
                in_array($productId, $checkedIds) // multiple shipping case
            ) {
                continue;
            }

            $checkedIds[] = $productId;

            $product = $this->productRepository->getById($productId);

            if ($this->helper->getIsProductPreorder($product)) {

                $preOrderCount++;

            } else {
                $normalCount++;
            }
        }

        if ($preOrderCount > 0) {

            if ($normalCount > 0 || $preOrderCount > 1) {
                throw new \Magento\Framework\Exception\LocalizedException($this->helper->cartMultiTypeProductMessage());
            }
        }

        return $this;
    }
}
