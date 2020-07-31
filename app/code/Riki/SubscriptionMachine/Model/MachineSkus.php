<?php

namespace Riki\SubscriptionMachine\Model;

use Magento\Framework\Model\Context;

class MachineSkus extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Catalog\Model\Product|null
     */
    protected $_product;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * MachineSkus constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry, null, null, $data);
        $this->_productRepository  = $productRepository;
    }

    protected function _construct()
    {
        $this->_init('Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus');
    }

    /**
     * Validate data before save
     *
     * @return array|bool
     */
    public function validate()
    {
        $errors = [];
        if (!$this->getProduct()) {
            $errors[] = __('Product %1 is not existed', $this->getData('sku'));
        }
        if (!is_numeric($this->getData('priority'))) {
            $errors[] = __('Priority must is a numeric');
        }
        if (!sizeof($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Get product relation by sku
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if ($this->_product) {
            return $this->_product;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->_productRepository->get($this->getData('sku'));
        $this->_product  = $product;
        return $this->_product;
    }

    /**
     * Check if we can delete this winner prize
     *
     * @return bool
     */
    public function canDelete()
    {
        if (!$this->getId()) {
            return false;
        }
        return true;
    }
}
