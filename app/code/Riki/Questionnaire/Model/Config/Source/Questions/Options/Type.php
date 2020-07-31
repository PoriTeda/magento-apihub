<?php
namespace Riki\Questionnaire\Model\Config\Source\Questions\Options;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_RADIO = 0;
    const TYPE_DROP_DOWN = 1;
    const TYPE_TEXT = 2;
    
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label'=>__('--Please Select--'), 'value'=> ''];
        $options[] = ['label'=>__('Free Text'), 'value'=> self::TYPE_TEXT];
        $options[] = ['label'=>__('Drop down'), 'value'=> self::TYPE_DROP_DOWN];
        $options[] = ['label'=>__('Radio'), 'value'=> self::TYPE_RADIO];
        return $options;
    }

    /**
     * Get Array value option
     * 
     * @return array
     */
    public function getOptionValueArray()
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_DROP_DOWN,
            self::TYPE_RADIO
        ];
    }

}