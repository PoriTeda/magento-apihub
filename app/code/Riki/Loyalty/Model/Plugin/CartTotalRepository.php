<?php

namespace Riki\Loyalty\Model\Plugin;

use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Cart\CartTotalRepository as TotalRepository;
use Magento\Quote\Model\Quote;

class CartTotalRepository
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var TotalsExtensionFactory
     */
    protected $totalsExtensionFactory;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalsExtensionFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalsExtensionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->totalsExtensionFactory = $totalsExtensionFactory;
    }

    /**
     * @param TotalRepository $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @return TotalsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGet(TotalRepository $subject, \Closure $proceed, $cartId)
    {
        /** @var TotalsInterface $totals */
        $totals = $proceed($cartId);

        /** @var Quote  $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var \Magento\Quote\Api\Data\TotalsExtensionInterface $extensionAttributes */
        $extensionAttributes = $totals->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
        }
        $extensionAttributes->setUsedPoint($quote->getUsedPoint());
        $extensionAttributes->setUsedPointAmount($quote->getUsedPointAmount());
        $extensionAttributes->setBaseUsedPointAmount($quote->getBaseUsedPointAmount());
        $extensionAttributes->setBonusPointAmount($quote->getBonusPointAmount());
        $totals->setExtensionAttributes($extensionAttributes);
        if ($totals->getGrandTotal() == 0) {
            $totals->setGrandTotal($totals->getBaseGrandTotal());
        }
        return $totals;
    }
}
