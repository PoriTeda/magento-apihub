<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Api\ExportNextDelivery;

/**
 * Interface ProfileItemInterface
 */
interface ProfileItemInterface
{
    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return int $profileId
     */
    public function getProfileId();
}
