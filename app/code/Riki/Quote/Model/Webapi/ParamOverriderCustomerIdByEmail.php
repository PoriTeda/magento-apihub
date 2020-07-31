<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Quote\Model\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Rest\Request\ParamOverriderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\StateException;

/**
 * Replaces a "%cart_id%" value with the current authenticated customer's cart
 */
class ParamOverriderCustomerIdByEmail implements ParamOverriderInterface
{
    protected $logger;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CartManagementInterface
     */
    private $request;

    private $customerRegistry;

    /**
     * Constructs an object to override the cart ID parameter on a request.
     *
     * @param UserContextInterface $userContext
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     */
    public function __construct(
        UserContextInterface $userContext,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        Logger $loggerInterface
    ) {
        $this->userContext = $userContext;
        $this->request = $request;
        $this->customerRegistry = $customerRegistry;
        $this->logger = $loggerInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function getOverriddenValue()
    {
        try {
            if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
                $data = $this->request->getRequestData();

                if (isset($data['customerEmail'])) {
                    $customer = $this->customerRegistry->retrieveByEmail($data['customerEmail']);
                    return $customer->getId();
                }
            }
        } catch (NoSuchEntityException $e) {
            /* do nothing and just return null */
            $this->logger->critical(__("Email customer does not exit."));
            throw new StateException(
                __('Email customer does not exit.')
            );
        }

        return null;
    }
}