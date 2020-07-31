<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\User\Block;

/**
 * User block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class User extends \Magento\User\Block\User
{
    protected function _construct(){
        $this->addButton(
            'export',
            [
                'label' => __('Export CSV'),
            ]
        );
        $this->buttonList->update('export', 'onclick', 'setLocation(\''.$this->getUrl('riki_user/export/index').'\')');
        parent::_construct();
    }

}
