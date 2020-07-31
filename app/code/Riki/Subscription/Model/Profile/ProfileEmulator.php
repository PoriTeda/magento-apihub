<?php
namespace Riki\Subscription\Model\Profile;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Api\Data\ProfileEmulatorInterface;

class ProfileEmulator
    implements ProfileEmulatorInterface{

    /**
     * @var \Riki\Subscription\Model\Emulator\Cart $emulateCartModelFactory
     */
    protected $emulateCartModel;
    /**
     * @var  \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    protected $productRepository;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     */
    protected $orderAddressRepository;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Address\Collection $orderAddresscollection
     */
    protected $orderAddressCollection;

    /**
     * @var \Riki\Subscription\Model\Emulator\TableManager $tableManager
     */
    protected $tableManager;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;


    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Framework\Data\Collection $orderAddresscollection ,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Subscription\Model\Emulator\CartFactory $emulateCartModelFactory,
        \Riki\Subscription\Model\Emulator\TableManager $tableManager,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    )
    {
        $this->emulateCartModelFactory = $emulateCartModelFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderAddressCollection = $orderAddresscollection;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->tableManager = $tableManager;
        $this->storeManager = $storeManager;
        $this->_construct();
    }

    public function _construct(){
        /* try to create all table you need */
        try {
            $this->tableManager->createTemporaryTables();
        }
        catch (\Exception $exception){
            throw new LocalizedException(__("Could not instance needed temporary tables , detail: ".$exception->getMessage()));
        }
    }
    /**
     * @inheritdoc
     */
    public function emulateProfileCart(
        \Riki\Subscription\Api\Data\ProfileInterface $profile
    ){

        /* init some helper and validator */
        $greaterThanValidator = new \Zend_Validate_GreaterThan(array('min' => 0));
        $notEmptyValidator = new \Zend_Validate_NotEmpty();
        $billingAddressId = null;
        try {
            /** @var \Magento\Store\Model\Store $storeViewForProfile */
            $storeViewForProfile = $this->storeManager->getStore($profile->getData('store_id'));
        }
        catch (\Exception $exception){
            throw new LocalizedException(__("Could not get store"));
        }
        /* try to load customer before execute the next thing */

        if(!$customerId = $profile->getCustomerID()){
            throw new NoSuchEntityException(__("Profile does not assign to any customer"));
        }
        /** @var $customerModel \Magento\Customer\Api\Data\CustomerInterface  */
        try {
            $customerModel = $this->customerRepository->getById($customerId);
        }
        catch (LocalizedException $exception){
            throw new LocalizedException(__("Could not get customer for this profile"));
        }

        /* try to get product cart data */
        try {
            /** @var \Riki\Subscription\Model\ProductCart\ProductCart[] $productCartCollection */
            $productCartCollection = $profile->getProductCart();
        }
        catch (\Exception $exception){
            throw new LocalizedException(__("Could not get product cart data for this profile"));
        }

        $productCartSize = count($productCartCollection);

        /* try to load billing address from product cart item */
        if(!$greaterThanValidator->isValid($productCartSize)){
            /* profile is empty product cart */
            throw new LocalizedException(__("Product cart is empty or could not get"));
        }

        /* at first we will try to create cart tmp object */
        /** @var \Riki\Subscription\Model\Emulator\Cart $cart */
        $cart = $this->emulateCartModelFactory->create();

        /* get store from profile*/


        $cart->setStore($storeViewForProfile);
        $cart->assignCustomer($customerModel);
        $cart->setCurrency();


        /* get product cart from profile */
        /**
         * @var  int $index
         * @var  \Riki\Subscription\Model\ProductCart\ProductCart $productCartItem
         */
        foreach ($productCartCollection as $index => $productCartItem){
            try {
                $productModel = $this->productRepository->getById($productCartItem->getProductID());
            }
            catch (\Magento\Framework\Exception\NoSuchEntityException $exception){
                throw  new NoSuchEntityException(__("Could not load product from product cart , detail: " . $exception->getMessage()));
            }

            /* check product qty before add to quote */
            if(!$greaterThanValidator->isValid((int) $productCartItem->getProductQty())){
                throw new LocalizedException(__("Product qty is not valid for this profile"));
            }

            try {
                $cartItem = $cart->addProduct(
                    $productModel ,
                    (int) $productCartItem->getProductQty()
                );
            }
            catch (LocalizedException $exception){
                throw new LocalizedException(__("Unable to add product to quote , detail: " . $exception->getMessage()));
            }

            /** try to load shipping address and add to item */
            $shippingAddressId = $productCartItem->getData('shipping_address_id');

            if($notEmptyValidator->isValid($shippingAddressId) && $greaterThanValidator->isValid($shippingAddressId)){
                $shippingAddressModel = $this->orderAddressCollection->getItemById($shippingAddressId);
                if( $notEmptyValidator->isValid($shippingAddressModel)
                && $shippingAddressModel instanceof \Magento\Sales\Api\Data\OrderAddressInterface
                ){
                    /**
                     * @var \Magento\Sales\Model\Order\Address $shippingAddressModel
                     * @var \Magento\Quote\Model\Quote\Address $cartShippingAddress
                     */
                    $cartShippingAddress = $cart->getShippingAddress()->addData($shippingAddressModel->toArray());
                    $cartShippingAddress->addItem($cartItem);
                }else{
                    /** @var \Magento\Sales\Model\Order\Address $shippingAddressModel */
                    try {
                        $shippingAddressModel = $this->orderAddressRepository->get($shippingAddressId);
                    }
                    catch (NoSuchEntityException $exception){
                        throw new NoSuchEntityException(__("Could not get shipping address model , detail: " . $exception->getMessage()));
                    }
                    catch (\Exception $exception){
                        throw new NoSuchEntityException(__("Could not get shipping address model , detail: ". $exception->getMessage()));
                    }
                    $this->orderAddressCollection->addItem($shippingAddressModel);
                    /** @var \Magento\Quote\Model\Quote\Address $cartShippingAddress */
                    $cartShippingAddress = $cart->getShippingAddress()->addData($shippingAddressModel->toArray());
                    $cartShippingAddress->addItem($cartItem);
                }
            }else{
                throw new LocalizedException(__("Shipping address id is not valid"));
            }
        }

        /* try to get billing address from billing address id from frist item of product cart item */
        try {
            /** @var \Riki\Subscription\Model\ProductCart\ProductCart $firstProductCartItem */
            $firstProductCartItem = reset($productCartCollection);
            $billingAddressId =   $firstProductCartItem->getData('billing_address_id');
            if(!$notEmptyValidator->isValid($billingAddressId)){
                throw new LocalizedException(__("Billing address is empty for subscription profile"));
            }
            /** @var \Magento\Sales\Model\Order\Address $bilingAddressModel */
            $billingAddressModel = $this->orderAddressRepository->get($billingAddressId);
        }
        catch (NoSuchEntityException $exception){
            throw new NoSuchEntityException(__("Could not load billing address"));
        }
        catch (\Exception $exception){
            throw new NoSuchEntityException(__("Could not load billing address"));
        }
        try {
            $cart->getBillingAddress()->addData($billingAddressModel->toArray());
        }
        catch (\Exception $exception){
            throw new LocalizedException(__("Could not set billing address for cart , detail: " . $exception->getMessage()));
        }
        /* Not effect to inventory */
        $cart->setInventoryProcessed(true);
        /* process payment method */
        $paymentMethod = $profile->getPaymentMethod();
        $cart->setPaymentMethod($paymentMethod);
        $cart->collectTotals()->save();

        return $cart;
    }

}