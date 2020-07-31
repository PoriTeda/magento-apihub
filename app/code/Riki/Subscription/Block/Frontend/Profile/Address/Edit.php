<?php
namespace Riki\Subscription\Block\Frontend\Profile\Address;
use Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit;
use Riki\Subscription\Model\Profile\Profile;


/**
 * Customer giftregistry edit block
 */
class Edit extends \Magento\Customer\Block\Address\Edit
{
    public function getSaveUrl()
    {
        $this->setData("riki_do_not_use_after", true);
        return $this->_urlBuilder->getUrl(
            'subscriptions/profile/saveAddress',
            ['_secure' => true, 'id' => $this->getAddress()->getId()]
        );
    }
    public function getConfirmUrl(){
        $this->setData("riki_do_not_use_after", true);
        return $this->_urlBuilder->getUrl(
            'subscriptions/profile/saveAddress',
            ['_secure' => true, 'id' => $this->getAddress()->getId()]
        );
    }

}
