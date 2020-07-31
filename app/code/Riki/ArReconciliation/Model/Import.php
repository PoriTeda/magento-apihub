<?php
namespace Riki\ArReconciliation\Model;

class Import extends \Magento\Framework\Model\AbstractModel 
{
    const WELL_NET = 'wellnet';

    const YAMATO = 'cod-yamato';
    
    const ASKUL = 'cod-askul';

    const CREDIT_CARD = 'paygent';
    
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\ResourceModel\Import');
    }

    public function toOptionArray()
    {
        $options=[  
            ['label' => __('-- Please Select --'), 'value' => ''],
            ['label' => __('Wellnet'), 'value' => self::WELL_NET],
            ['label' => __('COD Yamato'), 'value' => self::YAMATO],
            ['label' => __('COD Askul'), 'value' => self::ASKUL],
            ['label' => __('Paygent (Nicos JCB)'), 'value' => self::CREDIT_CARD],
        ];
       
        return $options;
    }
}