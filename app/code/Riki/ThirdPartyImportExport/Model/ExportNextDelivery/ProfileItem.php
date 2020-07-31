<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileItem implements ProfileItemInterface
{
    /**
     * @var int
     */
    private $profileId;


    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getProfileId()
    {
        return $this->profileId;
    }
}
