<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Profile;

class Disengage extends \Magento\Backend\App\Action
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    protected $_profileFactory;
    protected $_reasonFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    protected $profileCacheRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory
     * @param \Psr\Log\LoggerInterface $loggerInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {

        $this->_profileFactory = $profileFactory;
        $this->_reasonFactory = $reasonFactory;
        $this->_logger = $loggerInterface;
        $this->helperProfileData =  $helperProfileData;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->_initProfile();
        $profileId = (int)$this->getRequest()->getParam('id', 0);
        if($this->helperProfileData->isTmpProfileId($profileId)){
            $profileId = $this->helperProfileData->getMainFromTmpProfile($profileId);
        }
        $result->setUrl($this->getUrl('profile/profile/edit', ['id' => $profileId]));

        if($profile){
            $reason = $this->_reasonFactory->create()->load($this->getRequest()->getParam('reason', 0));

            if($reason->getId() && $reason->getStatus()){
                try{
                    $profile->disengage($reason->getId());
                    $profileId = $profile->getId();
                    $this->profileCacheRepository->removeCache($profileId);

                    if(count($profile->getOrders()) == 1){
                        $this->messageManager->addWarning(__('Need to disengage Subscription Profile as well'));
                    }
                    $this->messageManager->addSuccess(__('The subscription profile is waiting to be disengaged, please add penalty fee or disengage without penalty fee.'));
                }catch (\Magento\Framework\Exception\LocalizedException $e){
                    $this->messageManager->addError(__('The disengagement profile process has got error, message: %1', $e->getMessage()));
                }catch (\Exception $e){
                    $this->_logger->critical($e);
                    $this->messageManager->addError(__('We can not disengage this subscription profile now.'));
                }

            }else{
                $this->messageManager->addError(__('The request reason does not exit.'));
            }
        }else{
            $this->messageManager->addError(__('The subscription profile does not exit.'));
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected function _initProfile(){
        $id = $this->getRequest()->getParam('id', 0);

        if($id){
            $profile = $this->_profileFactory->create()->load($id);

            if($profile->getId()){
                return $profile;
            }
        }

        return null;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::profile_disengage');
    }
}