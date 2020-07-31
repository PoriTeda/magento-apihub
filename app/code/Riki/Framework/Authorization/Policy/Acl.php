<?php
/**
 * Uses ACL to control access. If ACL doesn't contain provided resource,
 * permission for all resources is checked
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Framework\Authorization\Policy;


class Acl extends \Magento\Framework\Authorization\Policy\Acl
{

    /**
     * Check whether given role has access to give id
     *
     * @param string $roleId
     * @param string $resourceId
     * @param string $privilege
     * @return bool
     */
    public function isAllowed($roleId, $resourceId, $privilege = null)
    {
        try {
            if ($this->_aclBuilder->getAcl()) {
                return $this->_aclBuilder->getAcl()->isAllowed($roleId, $resourceId, $privilege);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            try {
                if (!$this->_aclBuilder->getAcl()->has($resourceId)) {
                    return $this->_aclBuilder->getAcl()->isAllowed($roleId, null, $privilege);
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
}
