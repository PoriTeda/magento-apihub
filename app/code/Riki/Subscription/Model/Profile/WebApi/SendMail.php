<?php

namespace Riki\Subscription\Model\Profile\WebApi;
/* Use exception namespace */
use Magento\Framework\DataObject;
class SendMail {

    protected $simulator;
    
    protected $emailHelper;
    
    protected $logger;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator $profileEmulator
     */
    protected $profileEmulatorHelper = null;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;


    /**
     * SendMail constructor.
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Sales\Helper\Email $emailSaleHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Sales\Helper\Email $emailSaleHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry
    )
    {
        $this->simulator = $simulator;
        $this->emailSaleHelper = $emailSaleHelper;
        $this->logger = $logger;
        $this->_registry = $registry;

    }

    /**
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $message
     * @throws \Exception
     */
    public function execute(\Riki\Subscription\Api\GenerateOrder\ProfileEmailBuilderInterface $message)
    {
        
        foreach ($message->getItems() as $profileObject) {
            
            $profileId = $profileObject->getProfileId();

            $profileData =  \Zend_Json::decode($profileObject->getProfileData());
            $frequencyId = $this->emailSaleHelper->getSubProfileFrequencyID($profileData['frequency_unit'], $profileData['frequency_interval']);
            $profileData = new DataObject($profileData);
            
            $productCart = $this->emailSaleHelper->convertProductData($profileData->getProductCart());
            unset($profileData['product_cart']);

            $profileData['product_cart'] = $productCart;
        }
        if(isset($profileId) && $profileData){
            try {
                $courseId = $profileData->getData('course_id');
                // Reset data in message
                $this->_registry->unregister('subscription_profile_obj');
                $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
                $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);

                // Set data
                $this->_registry->register('subscription_profile_obj', $profileData);
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
                $nextOrder = $this->simulator->createSimulatorOrderHasData($profileData);
                if($nextOrder != false){
                    //send mail order change for subscription
                    $this->emailSaleHelper->sendMailSubscriptionChange($nextOrder,$profileId,'order_change_subscription');
                }
            }
            catch (\Exception $e){
                $this->logger->critical($e->getMessage());
                throw $e;
            }
        }
    }

}