<?php

namespace Riki\Subscription\Controller\Profile;

use \Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

class LoadAdditionalCategoriesProducts extends Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * LoadAdditionalCategoriesProducts constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Framework\Registry $registry
     * @param RawFactory $resultRawFactory
     * @param Context $context
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\Registry $registry,
        RawFactory $resultRawFactory,
        Context $context
    ) {
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        $this->profileHelper = $profileHelper;
        $this->profileFactory = $profileFactory;
        $this->resultRawFactory = $resultRawFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $profile = $this->initProfile();

        if ($profile
            && $this->profileHelper->isHaveViewProfilePermission(
                $this->customerSession->getCustomerId(),
                $profile->getId()
            )
        ) {
            $this->registry->register('current_profile', $profile);
        }

        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_LAYOUT);
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);

        if($resultLayout->getLayout()->getOutput()) {
            $resultResponse = json_encode($resultLayout->getLayout()->getOutput());
        } else {
            $resultResponse = '';
        }

        $result->setData($resultResponse);
        return $result;
    }

    /**
     * @return bool
     */
    protected function initProfile()
    {
        $profileId = (int)$this->getRequest()->getPost('profile_id');

        $profile = $this->profileFactory->create()->load($profileId);

        if (!$profile || !$profile->getId()) {
            return false;
        }

        return $profile;
    }
}
