<?php

namespace Riki\Preorder\Block\Product\View\Preorder;

class ProductBundle extends \Magento\Catalog\Block\Product\View\AbstractView
{
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    protected $_bundleOptionsData;
    protected $_bundleSelectionsData;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Riki\Preorder\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $arrayUtils, $data);
    }



    public function getBundleSelectionsData()
    {
        if (is_null($this->_bundleSelectionsData)) {
            $this->prepareBundleData();
        }
        return $this->_bundleSelectionsData;
    }

    public function getBundleOptionsData()
    {
        if (is_null($this->_bundleOptionsData)) {
            $this->prepareBundleData();
        }
        return $this->_bundleOptionsData;
    }

    protected function prepareBundleData()
    {
        $this->_bundleSelectionsData = array();
        $this->_bundleOptionsData = array();

        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $this->getProduct()->getTypeInstance();

        $optionIds = $typeInstance->getOptionsIds($this->getProduct());
        $options = $typeInstance->getOptions($this->getProduct());
        foreach($options as $option) {
            /** @var $option \Magento\Bundle\Model\Option */
            $this->_bundleOptionsData[$option->getId()] = array(
                'isSingle' => null,
                'isRequired' => (bool) $option->getRequired(),
                'selectionCount' => 0, // for a while
                'isPreorder' => null,
                'message' => null,
                'selectionId' => 0,
            );
        }
        /*foreach ($optionIds as $optionId) {
            $this->_bundleOptionsData[$optionId] = array(
                'isSingle' => null,
                'selectionCount' => 0, // for a while
                'isPreorder' => null,
                'message' => null,
            );
        }*/

        $selections = $typeInstance->getSelectionsCollection($optionIds, $this->getProduct());
        $productIds = [];
        foreach ($selections as $selection) {
            $productIds[] = $selection->getProductId();
        }
        $products = $this->getProduct()->getCollection()->addFieldToFilter('entity_id', $productIds);
        foreach($selections as $selection) {
            /** @var \Magento\Catalog\Model\Product $product */
            /** @var \Magento\Bundle\Model\Selection $selection */
            $product = $products->getItemById($selection->getProductId());

            $isPreorder = $this->helper->getIsProductPreorder($product);
            
            $note = $this->helper->getProductPreorderNote($product);
            $cartLabel = $this->helper->getProductPreorderCartLabel($product);

            $this->_bundleSelectionsData[$selection->getSelectionId()] = array(
                'isPreorder' => $isPreorder,
                'note' => $note,
                'cartLabel' => $cartLabel,
                'optionId' => $selection->getOptionId(),
            );

            // Update option record
            $optionRecord = &$this->_bundleOptionsData[$selection->getOptionId()];
            $optionRecord['selectionCount']++;
            $optionRecord['isSingle'] = $optionRecord['selectionCount'] == 1;

            if ($optionRecord['isSingle']) {
                $optionRecord['isPreorder'] = $isPreorder;
                $optionRecord['message'] = $note;
                $optionRecord['selectionId'] = $selection->getSelectionId();
            } else {
                // Have to analyze selections on frontend in order to find out
                $optionRecord['isPreorder'] = null;
                $optionRecord['message'] = null;
            }
        }
    }

    public function getMap()
    {
        $selectionsPreorderMap = [];
        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $this->getProduct()->getTypeInstance();
        $optionIds = $typeInstance->getOptionsIds($this->getProduct());
        $selections = $typeInstance->getSelectionsCollection($optionIds, $this->getProduct());
        $productIds = [];
        foreach ($selections as $selection) {
            $productIds[] = $selection->getProductId();
        }
        $products = $this->getProduct()->getCollection()->addFieldToFilter('entity_id', $productIds);
        foreach($selections as $selection) {
            /** @var \Magento\Catalog\Model\Product $product */
            /** @var \Magento\Bundle\Model\Selection $selection */
            $product = $products->getItemById($selection->getProductId());

            $isPreorder = $this->helper->getIsProductPreorder($product);
            if(!$isPreorder) {
                continue;
            }
            $selectionsPreorderMap[$selection->getOptionId().'-'.$selection->getSelectionId()] = [
                'note' => $this->helper->getProductPreorderNote($product),
            ];
        }
        return $selectionsPreorderMap;
    }

    public function getCartLabel()
    {
        return $this->helper->getDefaultPreorderCartLabel();
    }
}
