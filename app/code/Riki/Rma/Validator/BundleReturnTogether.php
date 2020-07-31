<?php

namespace Riki\Rma\Validator;

class BundleReturnTogether extends \Magento\Framework\Validator\AbstractValidator
{
    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * BundleReturnTogether constructor.
     * @param \Magento\Rma\Helper\Data $helperData
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Rma\Helper\Data $helperData,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getFullItemMessage()
    {
        return __('Please return full items of bundle products');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getQtyMessage()
    {
        return __('Qty requested of bundle item must valid');
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function isValid($rma)
    {

        if (!$rma->getIncrementId()) { // only validate for create case

            $bundleItems = [];
            $availableItems = $this->helperData->getOrderItems($rma->getOrderId());

            /** @var \Magento\Sales\Model\Order\Item $availableItem */
            foreach ($availableItems as $availableItem) {

                if ($availableItem->getHasChildren()) {
                    /** @var \Magento\Sales\Model\Order\Item $childrenItem */
                    foreach ($availableItem->getChildrenItems() as $childrenItem) {
                        $bundleOptions = $childrenItem->getProductOptionByCode('bundle_selection_attributes');
                        try {
                            $bundleOptions = $this->serializer->unserialize($bundleOptions);
                        } catch (\Exception $e) {
                            return false;
                        }

                        $bundleItems[$availableItem->getId()][$childrenItem->getId()] = $bundleOptions['qty'];
                    }
                }
            }

            $rmaItems = [];
            $items = $rma->getItems();

            /** @var \Magento\Rma\Model\Item $rmaItem */
            foreach ($items as $rmaItem) {
                $rmaItems[$rmaItem->getOrderItemId()]   =  $rmaItem->getQtyRequested();
            }

            $processedBundles = [];

            foreach ($rmaItems as $orderItemId  =>  $qtyRequested) {
                foreach ($bundleItems as $bundleId  =>  $bundleChildren) {
                    if (
                        !isset($processedBundles[$bundleId]) &&
                        array_key_exists($orderItemId, $bundleChildren)
                    ) {

                        if ($qtyRequested % $bundleChildren[$orderItemId] == 0) {

                            $returnBundleQty = $qtyRequested / $bundleChildren[$orderItemId];

                            if (!$this->validateFullBundle($rmaItems, $bundleChildren, $returnBundleQty)) {
                                return false;
                            }
                        } else {
                            $this->_addMessages(['return bundle product'    =>  $this->getQtyMessage()]);
                            return false;
                        }

                        $processedBundles[] = $bundleId;
                        continue 2;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array $rmaItemsData
     * @param array $bundleChildren
     * @param $returnBundleQty
     * @return bool
     */
    protected function validateFullBundle(array $rmaItemsData, array $bundleChildren, $returnBundleQty)
    {
        foreach ($bundleChildren as $orderItemId    =>  $bundleQty) {

            if (array_key_exists($orderItemId, $rmaItemsData)) {
                if ($rmaItemsData[$orderItemId] == $bundleQty * $returnBundleQty) {
                    continue;
                } else {
                    $this->_addMessages(['return bundle product'    =>  $this->getQtyMessage()]);
                    return false;
                }
            } else {
                $this->_addMessages(['return bundle product'    =>  $this->getFullItemMessage()]);
                return false;
            }
        }

        return true;
    }
}
