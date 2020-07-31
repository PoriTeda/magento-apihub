<?php
namespace Riki\Subscription\CustomerData;
use Magento\Customer\CustomerData\SectionSourceInterface;

class MultipleCategoryCampaign implements SectionSourceInterface
{
    protected $sessionManager;

    public function __construct(\Magento\Framework\Session\SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        if ($selectedProducts = $this->sessionManager->getMulltipleCategoryCampaignSelectedProduct()) {
            $selectedProducts = json_decode($selectedProducts, true);

            return ['selected_products'=> $selectedProducts] ;
        }
        return ['selected_products'=> (object) []];
    }
}