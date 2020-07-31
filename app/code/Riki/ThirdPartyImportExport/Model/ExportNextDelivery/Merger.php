<?php
namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Magento\Framework\MessageQueue\MergerInterface;

class Merger implements MergerInterface
{
    public function merge(array $messages)
    {
        return $messages;
    }
}