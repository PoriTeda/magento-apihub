<?php
namespace Riki\SubscriptionProfileDisengagement\Plugin\Subscription\Model\Profile\WebApi;

use Riki\Subscription\Model\Profile\ProfileFactory as ProfileFactory;

class ProfileRepository
{
    /**
     * @var ProfileFactory
     */
    protected $_profileFactory;

    /**
     * @param ProfileFactory $profileFactory
     */
    public function __construct(
        ProfileFactory $profileFactory
    ){
        $this->_profileFactory = $profileFactory;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\WebApi\ProfileRepository $subject
     * @param \Closure $proceed
     * @param $profileData
     * @param $method
     * @return mixed
     */
    public function aroundPrepareProfileType(
        \Riki\Subscription\Model\Profile\WebApi\ProfileRepository $subject,
        \Closure $proceed,
        $profileData,
        $method
    ) {

        $method = $proceed($profileData, $method);

        if ($method != 'type_2'){
            /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
            $profileModel = $this->_profileFactory->create()->load($profileData->getData('profile_id'));

            if($profileModel->isWaitingToDisengaged())
                $method = 'type_2';
        }

        return $method;
    }
}
