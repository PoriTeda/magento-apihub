<?php
namespace Riki\Subscription\Api;
/**
 * @api
 */
interface ProfileProductCartRepositoryInterface
{
    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return mixed
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);

    /**
     * @param $id
     * @return mixed
     */
    public function get($id);

    /**
     * @param Data\ApiProductCartInterface $cartId
     * @return mixed
     */
    public function validate(\Riki\Subscription\Api\Data\ApiProductCartInterface $cartId);

    /**
     * @param Data\ApiProductCartInterface $cartId
     * @return mixed
     */
    public function save(\Riki\Subscription\Api\Data\ApiProductCartInterface $cartId);

    /**
     * @param Data\ApiProductCartInterface $productCart
     * @return mixed
     */
    public function delete(\Riki\Subscription\Api\Data\ApiProductCartInterface $productCart);

    /**
     * @param $cartId
     * @return mixed
     */
    public function deleteById($cartId);
}