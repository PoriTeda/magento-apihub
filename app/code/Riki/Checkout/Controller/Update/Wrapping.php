<?php

namespace Riki\Checkout\Controller\Update;

class Wrapping extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Quote\Api\GuestCartRepositoryInterface
     */
    protected $guestQuoteRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * Wrapping constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\GiftWrapping\Helper\Data $helperData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\GuestCartRepositoryInterface $guestQuoteRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\GiftWrapping\Helper\Data $helperData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\GuestCartRepositoryInterface $guestQuoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductFactory $productFactory
    )
    {
        parent::__construct($context);

        $this->resultJson = $resultJson;
        $this->quoteRepository = $quoteRepository;
        $this->wrappingRepository = $wrappingRepository;
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->customerSession = $customerSession;
        $this->guestQuoteRepository = $guestQuoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->productFactory = $productFactory;
    }

    /**
     * Execute
     *
     * @return mixed
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $cartId = $params['cart_id'] ?? $this->checkoutSession->getQuoteId();
        $quote = $this->checkoutSession->getQuote();
        $item = $quote->getItemById($params['item_id']);
        if(!$item){
            $item = $quote->getItemByProduct($this->productFactory->create()->load($params['item_id']));
        }
        $result = $this->updateWrappingItem($cartId, $params['gw_id'], $item->getItemId());
        $json = ['success' => $result];

        if (!$result) {
            $json['msg'] = __('An error occurred.');
        }

        $resultJson = $this->resultJson->create();
        return $resultJson->setData($json);
    }

    /**
     * @param $cartId
     * @param $wrappingId
     * @param $itemId
     *
     * @return bool
     */
    public function updateWrappingItem($cartId, $wrappingId, $itemId)
    {
        if ($this->helperData->isGiftWrappingAvailableForItems()) {
            try {
                if ($this->customerSession->isLoggedIn()) {
                    $quote = $this->quoteRepository->getActive($cartId);
                } else {
                    try{
                        $quote = $this->quoteRepository->getActive($cartId);
                    } catch(\Exception $e){
                        $quote = $this->guestQuoteRepository->get($cartId);
                    }
                }

                $item = $quote->getItemById($itemId);
                if (!$item) {
                    return false;
                }

                if ($wrappingId == -1) {
                    $gwId = '';
                    $gCode = '';
                    $sapCode = '';
                    $gw = '';
                } else {
                    $wrapping = $this->wrappingRepository->get($wrappingId);
                    if (!$wrapping) {
                        return false;
                    }
                    $gwId = $wrapping->getId();
                    $gCode = $wrapping->getGiftCode();
                    $sapCode = $wrapping->getSapCode();
                    $gw = $wrapping->getGiftName();
                }

                $item->setGwId($gwId)
                    ->setGiftCode($gCode)
                    ->setSapCode($sapCode)
                    ->setGiftWrapping($gw);

                if (empty($gwId)) {
                    $item->setGwPrice(0)
                        ->setGwBasePrice(0)
                        ->setGwBaseTaxAmount(0)
                        ->setGwTaxAmount(0);
                }

                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return false;
            }
        }

        return true;
    }
}
