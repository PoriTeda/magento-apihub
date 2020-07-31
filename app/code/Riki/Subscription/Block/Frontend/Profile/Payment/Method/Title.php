<?php
namespace Riki\Subscription\Block\Frontend\Profile\Payment\Method;

class Title extends \Riki\Subscription\Block\Html\Title
{
    /**
     * {@inheritdoc}
     *
     * @param string $title
     */
    public function setPageTitle($title)
    {
        $profileId = $this->getRequest()->getParam('id');
        if ($profileId) {
            $subModel = $this->modelProfile->create()->load($profileId);
            $this->pageTitle = sprintf(__('%s delivery number %s delivery information of the times'),
                $subModel->getData('course_name'), ($subModel->getData('order_times') + 1));
        } else {
            $this->pageTitle = $title;
        }
    }
}