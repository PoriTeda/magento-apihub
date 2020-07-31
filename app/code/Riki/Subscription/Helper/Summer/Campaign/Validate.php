<?php


namespace Riki\Subscription\Helper\Summer\Campaign;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\App\Action\Context;
use Riki\Subscription\Helper\Profile\CampaignHelper;
use Riki\Subscription\Model\Constant;

class Validate
{
    protected $resultRedirect;

    protected $url;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\Cache
     */
    protected $multipleCategoryCache;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockData;

    protected $backOrderHelper;

    /**
     * Validate constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache
     * @param \Riki\ProductStockStatus\Helper\StockData $stockData
     * @param \Riki\BackOrder\Helper\Data $backOrderHelper
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache,
        \Riki\ProductStockStatus\Helper\StockData $stockData,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        Context $context
    )
    {
        $this->request = $request;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->profileData = $profileData;
        $this->helperProfile = $helperProfile;
        $this->productFactory = $productFactory;
        $this->registry = $registry;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->sessionManager = $sessionManager;
        $this->multipleCategoryCache = $multipleCategoryCache;
        $this->stockData = $stockData;
        $this->backOrderHelper = $backOrderHelper;
        $this->messageManager = $context->getMessageManager();
    }

    public function setResultRedirect($resultRedirect)
    {
        $this->resultRedirect = $resultRedirect;

        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function validateFormKey()
    {
        if (!$this->formKeyValidator->validate($this->request) || !$this->request->isPost()) {
            $this->messageManager->addError(__('Form key is not valid.'));

            return $this->resultRedirect->setPath($this->url);
        }
    }

    public function validateCustomerLogin()
    {
        if (!$this->customerSession->isLoggedIn()) {

            return $this->resultRedirect->setPath('customer/account/login');
        }
    }

    public function validateRequestData($reqDataValue)
    {
        if (empty($reqDataValue)) {
            $this->messageManager->addErrorMessage(__('The request data is invalid. Please try again'));

            return $this->resultRedirect->setPath('customer/account');
        }
    }

    public function clearQuote($dataProductSkus)
    {
        $quote = $this->checkoutSession->getQuote();
        foreach ($quote->getAllItems() as $quoteItem) {
            foreach ($dataProductSkus as $sku) {
                $item = $this->productFactory->create()->loadByAttribute('sku', $sku);
                if ($quoteItem->getData('product_id') == $item->getId()) {
                    $quote->deleteItem($quoteItem);
                    break;
                }
            }
        }
    }

    public function checkMainProfile($profileId)
    {
        if ($this->profileData->isTmpProfileId($profileId)) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 is not main profile', $profileId));

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function checkProfileExistOnCustomer($profile, $profileId)
    {
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 no longer exists', $profileId));

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function checkProductExistInProductListOfCourse($profile, $dataProductSkus, $subCourse)
    {
        $subscriptionCourseResourceModel = $this->helperProfile->getSubscriptionCourseResourceModel();
        $productsByCourse = $subscriptionCourseResourceModel->getAllProductByCourse(
            $profile->getCourseId(), $profile->getStoreId());

        $productByCourseSkus = array_column($productsByCourse->getData(), 'sku');
        $validateProduct = array_diff($dataProductSkus, $productByCourseSkus);
        if ($validateProduct) {
            $skuError = array_values($validateProduct)[0];
            $productError = $this->productFactory->create()->loadByAttribute('sku', $skuError);
            if ($productError) {
                $this->messageManager->addErrorMessage(__('%1 cannot be purchased on your scheduled regular course %2. Please choose another product.', $productError->getName(), $subCourse->getName()));
            } else {
                $this->messageManager->addErrorMessage(__('The product with sku %1 does\'n exist', $skuError));
            }

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function checkProfileBelongToCustomer($profileId, $customerId)
    {
        $validate = $this->profileData->checkProfileBelongToCustomer($profileId, $customerId);
        if (!$validate) {
            $this->messageManager->addErrorMessage(__('Profile Id %1 not belong to your account'), $profileId);

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function setRegistryToApplyCatalogRule($profile)
    {
        $this->registry->register(Constant::RIKI_COURSE_ID, $profile->getCourseId());
        $this->registry->register(Constant::RIKI_FREQUENCY_ID, $profile->getSubProfileFrequencyID());
        $this->registry->register('subscription_profile_obj', $profile);
    }

    public function validateMinimumAmount($simulatedOrder, $profile, $subCourse)
    {
        $validateResults = $this->subOrderHelper->validateMinimumAmountRestriction(
            $simulatedOrder,
            $subCourse,
            $profile
        );
        if (!$validateResults['status']) {
            $this->messageManager->addErrorMessage(__($validateResults['message']));

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function validateMaximumQty($prepareData, $profileId)
    {
        $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();
        if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximumQty['product_errors'],
                $validateMaximumQty['maxQty']
            );
            $this->messageManager->addErrorMessage($message);

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function saveSimulatorForCache($simulatedOrder)
    {
        if ($this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID)) {
            $identifier = $this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID);
        } else {
            $identifier = $this->multipleCategoryCache->getCacheIdentifier();
            $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID, $identifier);
        }
        $this->multipleCategoryCache->saveCache($simulatedOrder, $identifier);
    }

    public function checkCcProductIsInNotCcProfile($profile, $dataProductSkus, $subCourse)
    {
        $profilePaymentMethod = $profile->getPaymentMethod();

        foreach ($dataProductSkus as $sku) {
            $product = $this->productFactory->create()->loadByAttribute('sku', $sku);
            if ($product->getId() && $product->getCreditCardOnly() && $profilePaymentMethod != \Bluecom\Paygent\Model\Paygent::CODE) {
                if ($subCourse->getAllowChangePaymentMethod()) {
                    $this->messageManager->addErrorMessage(__('To add %1, please change your payment method for your scheduled subscription course %2 to a credit card', $product->getName(), $subCourse->getName()));
                } else {
                    $this->messageManager->addErrorMessage(__('%1 cannot be purchased on your scheduled regular course %2. Please choose another product.', $product->getName(), $subCourse->getName()));
                }

                return $this->resultRedirect->setUrl($this->url);
            }
        }
    }

    public function validateMinimumOrderQty($prepareData, $subCourse, $profile)
    {
        $profileProductCarts = $profile->getProductCart();
        $totalQtyProductPost = $this->formatQtyPieceCase($prepareData);
        $totalQtyProfile = $this->formatQtyPieceCase($profileProductCarts);
        $totalQty = $totalQtyProductPost + $totalQtyProfile;
        $minimumOrderQty = (int)$subCourse->getData('minimum_order_qty');
        if ($totalQty < $minimumOrderQty) {
            $this->messageManager->addErrorMessage(__("In %1, the total number of items in the shopping cart have at least %2 quantity", $subCourse->getName(), $minimumOrderQty));

            return $this->resultRedirect->setUrl($this->url);
        }
    }

    public function formatQtyPieceCase($products)
    {
        $totalQty = 0;

        foreach ($products as $product) {
            if ($product->getData('unit_case') == 'CS') {
                $qty = $product->getData('qty') / $product->getData('unit_qty');
            } else {
                $qty = $product->getData('qty');
            }

            $totalQty += $qty;
        }

        return $totalQty;
    }

    public function checkProductOutOfStock($dataProductSkus, $profile)
    {
        foreach ($dataProductSkus as $sku) {
            $product = $this->productFactory->create()->loadByAttribute('sku', $sku);
            $stockStatus = $this->getStockStatus($product, $profile);
            if ($stockStatus == __('Out of stock')) {
                $this->messageManager->addErrorMessage(__("I'm sorry. We are currently out of stock of %1, so we cannot accept your order in the quantity you requested. Sorry to trouble you, but please reduce the purchase quantity and try again.", $product->getName()));

                return $this->resultRedirect->setUrl($this->url);
            }
        }
    }

    /**
     * Get stock status
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return string
     */
    public function getStockStatus($product, $profile)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        if ($product->getTypeId() != ProductType::TYPE_BUNDLE) {
            $stock = $this->stockData->getStockStatusByEnv(
                $product,
                \Riki\ProductStockStatus\Helper\StockData::ENV_BO
            );

            $storeId = $profile->getData('store_id');

            if ($stock == __('Out of stock')
                && $this->backOrderHelper->isConfigBackOrder($product->getId(), $storeId)
            ) {
                if ($product->getIsSalable()) {
                    $stock = __('In stock');
                }
            }
        } else {
            if ($product->getIsSalable() == true) {
                $stock = __('In stock');
            } else {
                $stock = __('Out of stock');
            }
        }

        return $stock;
    }
}