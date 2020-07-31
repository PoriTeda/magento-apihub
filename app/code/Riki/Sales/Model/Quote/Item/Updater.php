<?php
namespace Riki\Sales\Model\Quote\Item;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Class Updater
 */
class Updater extends \Magento\Quote\Model\Quote\Item\Updater
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|null
     */
    private $serializer;

    public function __construct(
        ProductFactory $productFactory,
        FormatInterface $localeFormat,
        ObjectFactory $objectFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($productFactory, $localeFormat, $objectFactory, $serializer);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }


    /**
     * Unset custom_price data for quote item
     *
     * @param Item $item
     * @return void
     */
    protected function unsetCustomPrice(Item $item)
    {
        /** @var \Magento\Framework\DataObject $infoBuyRequest */
        $infoBuyRequest = $item->getBuyRequest();
        if ($infoBuyRequest->hasData('custom_price')) {
            $infoBuyRequest->unsetData('custom_price');

            $infoBuyRequest->setValue($this->serializer->serialize($infoBuyRequest->getData()));
            $infoBuyRequest->setCode('info_buyRequest');
            $infoBuyRequest->setProduct($item->getProduct());
            $item->addOption($infoBuyRequest);
        }

        $item->setCustomPrice(null);
        $item->setOriginalCustomPrice(null);
    }
}
