<?php

namespace Riki\Quote\Model\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Replaces a "%cart_id%" value with the current authenticated customer's cart
 */
class ParamOverriderCartId extends \Magento\Quote\Model\Webapi\ParamOverriderCartId
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ParamOverriderCartId constructor.
     * @param UserContextInterface $userContext
     * @param CartManagementInterface $cartManagement
     * @param QuoteFactory $quoteFactory
     * @param CartRepositoryInterface $cartRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        UserContextInterface $userContext,
        CartManagementInterface $cartManagement,
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $userContext,
            $cartManagement
        );

        $this->userContext = $userContext;
        $this->quoteFactory = $quoteFactory;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getOverriddenValue()
    {
        $currentCartId = parent::getOverriddenValue();

        if (!$currentCartId) {
            try {
                if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
                    $customerId = $this->userContext->getUserId();

                    $currentCachedQuote = $this->cartRepository->getForCustomer($customerId);

                    /** @var Quote $currentWithoutCachedQuote */
                    $currentWithoutCachedQuote = $this->quoteFactory->create();
                    $currentWithoutCachedQuote->setStoreId($this->storeManager->getStore()->getId())
                        ->loadByCustomer($customerId);

                    if (!$currentCachedQuote->getId() || !$currentCachedQuote->getIsActive()) {
                        $this->log(
                            'Current cached quote #'
                            . $currentCachedQuote->getId()
                            . ' has been deleted or inactive, customer ID: '
                            . $customerId
                        );
                    }

                    if ($currentWithoutCachedQuote->getId()
                        && $currentCachedQuote->getId() != $currentWithoutCachedQuote->getId()
                    ) {
                        $this->log(
                            'Load wrong current quote from cache, wong quote id:'
                            . $currentCachedQuote->getId()
                            . ', quote id: '
                            . $currentWithoutCachedQuote->getId()
                            . ', customer ID: ' . $customerId
                        );

                        $currentCartId = $currentWithoutCachedQuote->getId();
                    }
                }
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        }

        return $currentCartId;
    }

    /**
     * @param $message
     */
    private function log($message)
    {
        $writer = new \Zend\Log\Writer\Stream(\Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\App\Filesystem\DirectoryList::class
        )->getPath('log') . '/ned-1096.log');

        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info($message);
    }
}
