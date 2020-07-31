<?php
namespace Riki\Catalog\Api;

interface SapProductRepositoryInterface
{
    /**
     * Create product (import from sap)
     *
     * @api
     * @param \Riki\Catalog\Api\Data\SapProductInterface $product
     * @return \Riki\Catalog\Api\Data\SapProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Riki\Catalog\Api\Data\SapProductInterface $product);
}
