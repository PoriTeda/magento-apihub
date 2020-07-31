<?php

namespace Riki\Subscription\Block\Html;

use Magento\Framework\View\Element\Template;
use Riki\Subscription\Model\Profile\ProfileFactory;

class HanpukaiTitle extends \Magento\Theme\Block\Html\Title
{
    /* @var \Riki\Subscription\Model\Profile\ProfileFactory */
    protected $modelProfile;

    public function __construct(
        ProfileFactory $profileFactory,
        Template\Context $context,
        array $data = []
    ){
        $this->modelProfile = $profileFactory;
        parent::__construct($context, $data);
    }

    public function setPageTitle($title)
    {
        $profileId = $this->getRequest()->getParam('id');
        if ($profileId) {
            $subModel = $this->modelProfile->create()->load($profileId);
            $this->pageTitle = $subModel->getData('course_name');
        } else {
            $this->pageTitle = $title;
        }
    }
}