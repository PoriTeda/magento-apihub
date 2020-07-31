<?php

namespace Riki\Subscription\Block\Html;

use Magento\Framework\View\Element\Template;
use Riki\Subscription\Model\Profile\ProfileFactory;

class Title extends \Magento\Theme\Block\Html\Title
{
    /* @var \Riki\Subscription\Model\Profile\ProfileFactory */
    protected $modelProfile;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    public function __construct(
        ProfileFactory $profileFactory,
        Template\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        array $data = []
    ){
        $this->modelProfile = $profileFactory;
        $this->_profileData = $profileData;
        parent::__construct($context, $data);
    }

    public function setPageTitle($title)
    {
        $profileId = $this->getRequest()->getParam('id');
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        if ($profileId) {
            $subModel = $this->modelProfile->create()->load($profileId);
            $this->pageTitle = sprintf(__('%s delivery number %s delivery information of the times'),
                $subModel->getData('course_name'), ($subModel->getData('order_times') + 1));
        } else {
            $this->pageTitle = $title;
        }
    }
}