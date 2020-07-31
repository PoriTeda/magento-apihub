<?php

namespace Riki\Promo\Model\Rule;

class FormDataProvider extends \Magento\SalesRule\Model\Rule\DataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($items as $rule) {
            $rule->load($rule->getId());
            $rule->setDiscountAmount($rule->getDiscountAmount() * 1);
            $rule->setDiscountQty($rule->getDiscountQty() * 1);

            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->loadedData[$rule->getId()]['ampromorule'] = [
                'sku' => $this->loadedData[$rule->getId()]['sku'],
                'type' => $this->loadedData[$rule->getId()]['type'],
                'att_visible_cart' => $this->loadedData[$rule->getId()]['att_visible_cart'],
                'att_visible_user_account' => $this->loadedData[$rule->getId()]['att_visible_user_account']
            ];
        }

        return $this->loadedData;
    }
}