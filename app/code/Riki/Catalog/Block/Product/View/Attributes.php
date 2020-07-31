<?php
namespace Riki\Catalog\Block\Product\View;

use Magento\Catalog\Model\Product;

/**
 * Class Attributes
 * @package Riki\Catalog\Block\Product\View
 */
class Attributes extends \Magento\Catalog\Block\Product\View\Attributes{


    /**
     * @param $_data
     * @return string
     */
    public function getProductDimension($_data){
        $locale ="ja_JP";
        $dimension = $depth = $width = $height = '';
        if(isset($_data['depth'])){
            $depth = isset($_data['depth']['value']) ? $_data['depth']['value'] :"" ;
        }
        if(isset($_data['width'])){
            $width = isset($_data['width']['value']) ? $_data['width']['value'] :"" ;
        }
        if(isset($_data['height'])){
            $height = isset($_data['height']['value']) ? $_data['height']['value'] :"" ;
        }
        if($height !=''){
            $dimension.= __('Height')." ". \Zend_Locale_Format::getNumber($height,['locale'=> $locale,'precision'=> 0]). (isset($_data['dimension_unit']['value']) ? $_data['dimension_unit']['value'] : "cm "). ' ';
        }
        if($width !=""){
            $dimension.= __('Width')." ". \Zend_Locale_Format::getNumber($width,['locale'=> $locale,'precision'=> 0]). (isset($_data['dimension_unit']['value']) ? $_data['dimension_unit']['value'] : "cm "). ' ';
        }
        if($width !=""){
            $dimension.= __('Depth')." ". \Zend_Locale_Format::getNumber($depth,['locale'=> $locale,'precision'=> 0]). (isset($_data['dimension_unit']['value']) ? $_data['dimension_unit']['value'] : "cm "). ' ';
        }
        return $dimension;
    }

    /**
     * @param $_data
     * @return string
     */
    public function getDesRecom($_data){
        $desRecom = '';
        if(isset($_data['desc_explanation_recom'])) {
            $desRecom = isset($_data['desc_explanation_recom']['value']) ? $_data['desc_explanation_recom']['value'] :"";
        }
        return $desRecom;
    }

    /**
     * @param $_data
     * @return string
     */
    public function getDesMandatory($_data){
        $desManda = '';
        if(isset($_data['desc_allergen_mandatory'])){
            $desManda = isset($_data['desc_allergen_mandatory']['value'])? $_data['desc_allergen_mandatory']['value']:'';
        }
        return $desManda;
    }

    /**
     * @param $_data
     * @return string
     */
    public function getDesNutrition($_data){
        $desNutrition = '';
        if(isset($_data['desc_nutrition'])){
            $desNutrition = isset($_data['desc_nutrition']['value'])? $_data['desc_nutrition']['value']:'';
        }
        return $desNutrition;
    }

    /**
     * @param $_data
     * @return string
     */
    public function getDescIngredient($_data){
        $desIngredient = '';
        if(isset($_data['desc_ingredient'])){
            $desIngredient = isset($_data['desc_ingredient']['value'])? $_data['desc_ingredient']['value']:'';
        }
        return $desIngredient;
    }

    /**
     * @param $_data
     * @return string
     */
    public function getDescontent($_data){
        $desContent = '';
        if(isset($_data['desc_content'])){
            $desContent = isset($_data['desc_content']['value'])? $_data['desc_content']['value']:'';
        }
        return $desContent;
    }
    
    /**
     * Set default template
     * @return string
     */
    protected function _toHtml()
    {
        $this->setModuleName($this->extractModuleName('Magento\Catalog\Block\Product\View\Attributes'));
        return parent::_toHtml();
    }
}