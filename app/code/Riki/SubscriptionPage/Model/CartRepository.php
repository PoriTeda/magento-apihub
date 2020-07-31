<?php
namespace Riki\SubscriptionPage\Model;

use Riki\Subscription\Model\Constant;

class CartRepository implements \Riki\SubscriptionPage\Api\CartRepositoryInterface
{
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    protected $emulation;

    protected $helperMachine;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * CartRepository constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Store\Model\App\Emulation $emulation,
        \Riki\MachineApi\Helper\Machine $helperMachine,
        \Magento\Checkout\Model\Cart $cart,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->registry = $registry;
        $this->simulator = $simulator;
        $this->emulation = $emulation;
        $this->helperMachine = $helperMachine;
        $this->cart = $cart;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $cartData
     *
     * @return string
     */
    public function emulate($cartData)
    {
        $data = \Zend_Json::decode($cartData);
        if (isset($data[Constant::RIKI_COURSE_ID])) {
            $this->registry->unregister(Constant::RIKI_COURSE_ID);
            $this->registry->register(Constant::RIKI_COURSE_ID, $data[Constant::RIKI_COURSE_ID]);
        }
        if (isset($data[Constant::RIKI_FREQUENCY_ID])) {
            $this->registry->unregister(Constant::RIKI_FREQUENCY_ID);
            $this->registry->register(Constant::RIKI_FREQUENCY_ID, $data[Constant::RIKI_FREQUENCY_ID]);
        }

        $quote = $this->simulator->createMageQuote($data, true);

        $response = [
            'grand_total' => $quote->getData('grand_total')
        ];

        return \Zend_Json::encode($response);
    }

    /**
     * @param int $courseId
     * @param string[] $selectedMain
     * @param int|bool $storeId
     * @return mixed
     */
    public function automaticallyMachine($courseId, $selectedMain, $storeId = null)
    {
        // Use for validate maximum qty restriction in checkout subscription multiple machine page.
        $dataPost = $selectedMain;
        $selectedMain = array_keys($selectedMain);

        $productCartIds = [];
        $hasMachineInCart = false;
        $quote = $this->cart->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('is_riki_machine') == 1) {
                $hasMachineInCart = true;
                continue;
            }
            $productCartIds[] = $item->getProductId();
        }
        $selectedMain = array_unique(array_merge($selectedMain, $productCartIds));

        if (!$courseId || !is_array($selectedMain) || !$selectedMain) {
            $response = [
                'is_valid' => true,
                'message' => __('Please select your desired machine and product'),
                'data' => ''
            ];
            $this->messageManager->addErrorMessage(__('Please select your desired machine and product'));
            return \Zend_Json::encode($response);
        }

        /** Validate maximum qty restriction */
        $prepareData = $this->subscriptionValidator->prepareProductDataForMultipleMachine($dataPost, $quote);
        if (!$prepareData) {
            $response = [
                'is_valid' => true,
                'message' => __('The selected product no longer exists'),
                'data' => ''
            ];
            $this->messageManager->addErrorMessage(__('The selected product no longer exists'));
            return \Zend_Json::encode($response);
        }

        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($courseId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error']) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximumQty['product_errors'],
                $validateMaximumQty['maxQty']
            );

            $response = [
                'is_valid' => true,
                'message' => $message,
                'data' => ''
            ];
            $this->messageManager->addErrorMessage($message);
            return \Zend_Json::encode($response);
        }

        $result = $this->helperMachine->buildDataMachineType($courseId, $selectedMain);

        $response = [
            'is_valid' => false,
            'message' => '',
            'skip_choose_machine' => $hasMachineInCart,
            'data' => $result
        ];
        return \Zend_Json::encode($response);
    }

    /**
     * @param string $request
     * @return mixed|void
     */
    public function loadMoreMachine($request)
    {
        $data = json_decode($request, true);
        $typeId = $data['id'];
        $currentPage = $data['current_page'];
        $result = $this->helperMachine->getLoadMoreMachines($typeId, $currentPage);

        $response = [
            'is_valid' => false,
            'message' => '',
            'data' => $result
        ];
        return \Zend_Json::encode($response);
    }

}