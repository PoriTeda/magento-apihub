<?php

namespace Riki\AdvancedInventory\Plugin\Quote\Model\Quote;

class OosTotalsCollector
{
    const OOS_COLLECT_TOTALS = 'advanced_inventory_oos_collect_totals';

    protected $proceedQuoteIds = [];

    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /** @var \Magento\Catalog\Helper\Product */
    protected $productHelper;

    /**
     * OosTotalsCollector constructor.
     *
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
     * @param \Magento\Catalog\Helper\Product $productHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver,
        \Magento\Catalog\Helper\Product $productHelper
    ) {
        $this->dbTransaction = $dbTransaction;
        $this->oosCaptureObserver = $oosCaptureObserver;
        $this->productHelper = $productHelper;
    }

    /**
     * Keep collect totals data on oos item
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Closure $proceed
     *
     * @return mixed
     */
    public function aroundCollectTotals(
        \Magento\Quote\Model\Quote $subject,
        \Closure $proceed
    ) {
        if (!$subject->getId()) {
            $this->firstProcessCollectTotals($subject, $proceed);
            return $proceed();
        }

        if ($subject->getTotalsCollectedFlag()) {
            return $proceed();
        }

        if (!$this->oosCaptureObserver->getOutOfStocks($subject->getId())) {
            $this->firstProcessCollectTotals($subject, $proceed);
            return $proceed();
        }

        if (isset($this->proceedQuoteIds[$subject->getId()])) { // just only run once to get collect totals for oos normal product
            return $proceed();
        }

        $this->proceedQuoteIds[$subject->getId()] = $subject->getId();

        try {
            //so just make sure no any unexpected db changes on this collect totals
            $this->dbTransaction->beginTransaction();

            $subject->setData(static::OOS_COLLECT_TOTALS, true);

            $currentSkipSaleableCheck = $this->productHelper->getSkipSaleableCheck();

            $this->productHelper->setSkipSaleableCheck(true);

            $this->firstProcessCollectTotals($subject, $proceed);

            $this->productHelper->setSkipSaleableCheck($currentSkipSaleableCheck);

            $subject->unsetData(static::OOS_COLLECT_TOTALS);

            $this->dbTransaction->rollback();
        } catch (\Exception $e) {
            $subject->unsSkipCollectDiscountFlag();
            $subject->unsetData(static::OOS_COLLECT_TOTALS);
            $this->dbTransaction->rollback();
            throw $e;
        }

        return $proceed();
    }

    /**
     * First Process CollectTotals
     *
     * @param $subject
     * @param $proceed
     */
    public function firstProcessCollectTotals($subject, $proceed)
    {
        $couponCode = $subject->getCouponCode();
        $isCollectAddressTotal = $subject->getShippingAddress()->getCollectShippingRates();
        $subject->setSkipCollectDiscountFlag(true);
        $proceed();
        $subject->setTotalsCollectedFlag(false);
        $subject->unsSkipCollectDiscountFlag();
        if($isCollectAddressTotal && !$subject->getShippingAddress()->getCollectShippingRates()) {
            $subject->getShippingAddress()->setCollectShippingRates(true);
        }
        $subject->setCouponCode($couponCode);
    }
}