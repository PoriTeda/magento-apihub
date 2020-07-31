<?php
namespace Riki\Subscription\Controller\Adminhtml\Profile\Payment\Method;

class Edit extends \Riki\Subscription\Controller\Adminhtml\Profile\AbstractProfile
{
    const ADMIN_RESOURCE = 'Riki_Subscription::edit_payment_method';

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface|bool
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', 0);

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($id);
        if($profile->getData('type') == 'tmp' || ($profile->getData('type') == 'version' and $profile->getData('profile_id') == $profile->getOrigData('profile_id') )){
            $this->_forward('no-route');
            return false;
        }
        if (!$profile->getId()) {
            $this->_forward('no-route');
            return false;
        }

        $result = $this->initPageResult();
        $result->getConfig()->getTitle()->set(__('Changing payment method'));

        /** @var \Riki\Subscription\Block\Frontend\Profile\Payment\Method\Edit $block */
        $block = $this->_view->getLayout()->getBlock('subscriptions.profile.payment.method.edit');
        if ($block instanceof \Riki\Subscription\Block\Frontend\Profile\Payment\Method\Edit) {
            $block->setData('profile', $profile);
        }

        return $result;
    }
}