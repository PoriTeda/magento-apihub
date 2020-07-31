<?php

namespace Riki\MachineApi\Api;


interface ApiCustomerRepositoryInterface
{

    /**
     *  Create and assign customer to cart
     *
     * @param string $cartId
     * @param string $consumerDbId
     * @return mixed
     */
    public function save($cartId , $consumerDbId);

    /**
     * Retrieve customer.
     *
     * @api
     * @param string $email
     * @param int|null $websiteId
     * @return \Riki\MachineApi\Api\Data\ApiCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified email does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($email, $websiteId = null);

    /**
     * Retrieve customer.
     *
     * @api
     * @param int $customerId
     * @return \Riki\MachineApi\Api\Data\ApiCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($customerId);


}
