<?php
namespace Riki\Subscription\Observer\Emulator;
use Riki\Sales\Model\ResourceModel\Order\OrderAdditionalInformation as AdditionalInformationResourceModel;

class CheckoutSubmitAllAfter extends \Riki\Sales\Observer\CheckoutSubmitAllAfter
{
    public function __construct
    (
        \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollection,
        \Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory $quoteAddressItemCollection,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $toOrderAddress,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper,
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\Subscription\Model\Emulator\Order\Address\ItemFactory $emulatorOrderAddressItemFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Address\CollectionFactory $emulatorQuoteAddressCollection,
        \Riki\Subscription\Model\Emulator\ResourceModel\Address\Item\CollectionFactory $emulatorQuoteAddressItemCollection,
        \Riki\Subscription\Model\Emulator\ResourceModel\Address\ToOrderAddress $emulatorToOrderAddress,
        \Riki\Sales\Helper\Email $riki_sale_email,
        \Magento\Backend\Model\Auth\Session $userAdmin,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership,
        AdditionalInformationResourceModel $orderAdditionalInformationResource
    )
    {
        parent::__construct(
            $quoteSession,
            $customerFactory,
            $request,
            $questionnaireAdminHelper,
            $salesAdminHelper,
            $riki_sale_email,
            $userAdmin,
            $logger,
            $messageManager,
            $membership,
            $orderAdditionalInformationResource
        );
        $this->_orderAddressItemFactory =  $emulatorOrderAddressItemFactory;
        $this->_quoteAddressCollectionFactory = $emulatorQuoteAddressCollection;
        $this->_quoteAddressItemCollectionFactory = $emulatorQuoteAddressItemCollection;
        $this->_toOrderAddressFactory = $emulatorToOrderAddress;

    }

}