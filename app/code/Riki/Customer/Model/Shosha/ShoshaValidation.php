<?php

namespace Riki\Customer\Model\Shosha;

class ShoshaValidation
{
    const SHOSHATYPEINDEX = 10;

    const REQUIRED = 'required';
    const CEDYNAREQUIRED = 'cedyna-required';
    const NONESPECIAL = 'none-special';
    const NUMBER10DIGIT = 'number10digit';
    const ISKATAKANA = 'is-katakana';
    const ISADDRESSKATAKANA = 'is-katakana-for-address';
    const NUMBER2DIGIT = 'number2digit';
    const PHONENUMBER = 'phone-number';
    const CEDYNASPECIALVALUE = 'special-value';
    const CEDYNAPHONE = 'cedyna-phone';
    const LENGTH = 'length';

    const TYPENOTJAPANESE = 0;
    const TYPEZENKAKU = 1;
    const TYPEHANKAKU = 2;
    const TYPEOTHERJAPANESE = 3;
    const TYPEKATAALLOW = 4;
    const TYPELATINTOLOWER = 5;
    const TYPELATINTOUPPER = 6;
    const TYPESYMBOL = 7;

    protected $_validateData;
    protected $_cedynaValidateData;
    protected $_specialCharacters;
    protected $_katakanaSpecialCharacters;
    protected $_katakanaSpecialValues;
    protected $_symbolCharacters;

    protected $_cedynaStartChar = 40; /* ( */
    protected $_cedynaEndChar = 41; /* ) */

    /**
     * @var \Riki\Customer\Model\Shosha\ShoshaCode
     */
    protected $_shoshaCode;

    /**
     * ShoshaValidation constructor.
     *
     * @param \Riki\Customer\Model\Shosha\ShoshaCode $shoshaCode
     */
    public function __construct(
        \Riki\Customer\Model\Shosha\ShoshaCode $shoshaCode
    ){
        $this->_shoshaCode = $shoshaCode;
    }

    /**
     * @return array
     */
    public function getSpecialCharacters()
    {
        if (empty($this->_specialCharacters)) {
            $this->_specialCharacters = [
                /* '∠','⊥','⌒','∂','∇','≡','≒','≪','≫','√', */
                8736,8869,8978,8706,8711,8801,8786,8810,8811,8730,
                /* '∽','∝','∵','∫','∬','∈','∋','⊆','⊇','⊂', */
                8765,8733,8757,8747,8748,8712,8715,8838,8839,8834,
                /* '⊃','∪','∩','∧','∨','￢','⇒','⇔','∀','∃', */
                8835,8746,8745,8743,8744,65506,8658,8660,8704,8707,
                /* 'Å','‰','♯','♭','♪','†','‡','¶','◯','─', */
                8491,8240,9839,9837,9834,8224,8225,182,9711,9472,
                /* '│','┌','┐','┘','└','├','┬','┤','┴','┼', */
                9474,9484,9488,9496,9492,9500,9516,9508,9524,9532,
                /* '━','┃','┏','┓','┛','┗','┣','┳','┫','┻', */
                9473,9475,9487,9491,9499,9495,9507,9523,9515,9531,
                /* '╋','┠','┯','┨','┷','┿','┝','┰','┥','┸', */
                9547,9504,9519,9512,9527,9535,9501,9520,9509,9528,
                /* '╂','凜','熙' */
                9538,20956,29081
            ];
        }

        return $this->_specialCharacters;
    }

    /**
     * @return array
     */
    public function getKatakanaSpecialCharacters()
    {
        if (empty($this->_katakanaSpecialCharacters)) {
            $this->_katakanaSpecialCharacters = [
                /* space, #, &, (, ), -, /, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, \, _ */
                32, 35, 38,40, 41, 45, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 92, 95
            ];
        }

        return $this->_katakanaSpecialCharacters;
    }

    /**
     * @return array
     */
    public function getSymbolCharacters()
    {
        if (empty($this->_symbolCharacters)) {
            $this->_symbolCharacters = [
                /* ~, !, @, #, $, %, ^, &, *, ( */
                126, 33, 64, 35, 36, 37, 94, 38, 42, 40,
                /* ), _, +, {, }, :, ", <, >, ? */
                41, 95, 43, 123, 125, 58, 34, 60, 62, 63,
                /* `, -, =, [, ], ;, ', ",", ., /, \, | */
                96, 45, 61, 91, 93, 59, 39, 44, 46, 47, 92, 124
            ];
        }

        return $this->_symbolCharacters;
    }

    /**
     * @return array
     */
    public function getKatakanaSpecialValues()
    {
        if (empty($this->_katakanaSpecialValues)) {
            $this->_katakanaSpecialValues = [
                /* (ﾞ) */
                [40, 65438, 41],
                /* (ﾟ) */
                [40, 65439, 41],
                /* (･) */
                [40, 65381, 41],
                /* (｡) */
                [40, 65377, 41],
                /*(.)*/
                [40, 46, 41],
            ];
        }
        return $this->_katakanaSpecialValues;
    }

    /**
     * @return array
     */
    public function getValidateData()
    {
        if (empty($this->_validateData)) {
            $this->_validateData = [
                'COMPANY_CODE' => ['index' => 0, 'validate' => [ self::REQUIRED, self::NONESPECIAL, self::NUMBER10DIGIT] ],
                'COMPANY_NAME' => ['index' => 1, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL]],
                'COMPANY_NAME_KANA' => ['index' => 2, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL, self::ISKATAKANA]],
                'COMPANY_POST_NAME' => ['index' => 3, 'validate' => [ self::NONESPECIAL]],
                'COMPANY_CHARGE_NAME' => ['index' => 4, 'validate' => [ self::NONESPECIAL]],
                'COMPANY_ADDRESS1' => ['index' => 5, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL]],
                'COMPANY_ADDRESS2' => ['index' => 6, 'validate' => [ self::NONESPECIAL]],
                'COMPANY_ADDRESS_KANA1' => ['index' => 7, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL, self::ISADDRESSKATAKANA]],
                'COMPANY_ADDRESS_KANA2' => ['index' => 8, 'validate' => [ self::NONESPECIAL, self::ISADDRESSKATAKANA]],
                'COMPANY_PHONE_NUMBER' => ['index' => 9, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL, self::PHONENUMBER ]],
                'CORPORATION_CODE' => ['index' => 10, 'validate' => [ self::REQUIRED, self::NONESPECIAL]],
                'FIRST_CODE' => ['index' => 11, 'validate' => [ self::REQUIRED, self::NONESPECIAL]],
                'SECONDARY_CODE' => ['index' => 12, 'validate' => [ self::REQUIRED, self::NONESPECIAL]],
                'COMMISSION_TYPE' => ['index' => 13, 'validate' => [ self::REQUIRED, self::NONESPECIAL, self::NUMBER2DIGIT]],
                'COMPANY_POST_NAME_KANA' => ['index' => 23, 'validate' => [ self::NONESPECIAL, self::ISKATAKANA]],
                'COMPANY_CHARGE_NAME_KANA' => ['index' => 24, 'validate' => [ self::NONESPECIAL, self::ISKATAKANA]],
                'COMPANY_POSTAL_CODE' => ['index' => 25, 'validate' => [ self::CEDYNAREQUIRED, self::NONESPECIAL]]
            ];
        }
        return $this->_validateData;
    }

    /**
     * @return array
     */
    public function getCedynaValidateData()
    {
        if (empty($this->_cedynaValidateData)) {
            $this->_cedynaValidateData = [
                [
                    'index' => 8,
                    'validate' => self::CEDYNASPECIALVALUE,
                    'key' => 'COMPANY_ADDRESS_KANA2'
                ],
                [
                    'index' => 9,
                    'validate' => self::CEDYNAPHONE,
                    'key' => 'COMPANY_PHONE_NUMBER'
                ],
                [
                    'index' => [9],
                    'validate' => self::LENGTH,
                    'length' => 12,
                    'key' => 'COMPANY_PHONE_NUMBER'
                ],
                [
                    'index' => [1],
                    'validate' => self::LENGTH,
                    'length' => [74],
                    'key' => 'COMPANY_NAME'
                ],
                [
                    'index' => [2],
                    'validate' => self::LENGTH,
                    'length' => 74,
                    'key' => 'COMPANY_NAME_KANA'
                ],
                [
                    'index' => [ 1, 3, 4],
                    'validate' => self::LENGTH,
                    'length' => 72,
                    'key' => 'COMPANY_NAME + COMPANY_POST_NAME + COMPANY_CHARGE_NAME'
                ],
                [
                    'index' => [ 1, 3],
                    'validate' => self::LENGTH,
                    'length' => 73,
                    'key' => 'COMPANY_NAME + COMPANY_POST_NAME'
                ],
                [
                    'index' => [ 1, 4],
                    'validate' => self::LENGTH,
                    'length' => 73,
                    'key' => 'COMPANY_NAME + COMPANY_CHARGE_NAME'
                ],
                [
                    'index' => [ 2, 23, 24],
                    'validate' => self::LENGTH,
                    'length' => 72,
                    'key' => 'COMPANY_NAME_KANA + COMPANY_POST_NAME_KANA + COMPANY_CHARGE_NAME_KANA'
                ],
                [
                    'index' => [ 2, 23],
                    'validate' => self::LENGTH,
                    'length' => 73,
                    'key' => 'COMPANY_NAME_KANA + COMPANY_POST_NAME_KANA'
                ],
                [
                    'index' => [ 2, 24],
                    'validate' => self::LENGTH,
                    'length' => 73,
                    'key' => 'COMPANY_NAME_KANA + COMPANY_CHARGE_NAME_KANA'
                ],
                [
                    'index' => [ 5, 6],
                    'validate' => self::LENGTH,
                    'length' => 65,
                    'key' => 'COMPANY_ADDRESS1 + COMPANY_ADDRESS2'
                ],
                [
                    'index' => [ 7, 8],
                    'validate' => self::LENGTH,
                    'length' => 65,
                    'key' => 'COMPANY_ADDRESS_KANA1 + COMPANY_ADDRESS_KANA2'
                ],
            ];
        }
        return $this->_cedynaValidateData;
    }

    /**
     * @param $rowData
     * @return array
     */
    public function validateRowData($rowData)
    {
        $errorMessage = [];

        $validateRule = $this->getValidateData();

        foreach ($validateRule as $key => $value) {
            $errorMessage = $this->getValidateErrorMessage( $errorMessage, $rowData, $key, $value );
        }

        /*validate for Cedyna Data*/
        if (empty($errorMessage)) {
            $shoshaCode = $this->_shoshaCode->convertCodeToValue($rowData[10]);
            if ($shoshaCode == \Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA) {
                $errorMessage = $this->getCedynaValidateErrorMessage($errorMessage, $rowData);
            }
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $rowData
     * @param $key
     * @param $value
     * @return mixed
     */
    public function getValidateErrorMessage($errorMessage, $rowData, $key, $value)
    {
        $validateValue = !empty($rowData[ $value['index'] ]) ? $rowData[ $value['index'] ] : '';

        //if value is 0,it's not empty
        $validateValue =  is_numeric($rowData[ $value['index'] ])?$rowData[ $value['index'] ]:$validateValue;

        $shoshaType = !empty($rowData[ self::SHOSHATYPEINDEX ]) ? $rowData[ self::SHOSHATYPEINDEX ] : '';

        foreach ($value['validate'] as $val ) {
            switch ($val) {
                case self::REQUIRED:
                    $errorMessage = $this->checkRequiredData($errorMessage, $key, $validateValue);
                    break;
                case self::CEDYNAREQUIRED:
                    $errorMessage = $this->checkCedynaRequiredData($errorMessage, $key, $validateValue, $shoshaType);
                    break;
                case self::NONESPECIAL:
                    $errorMessage = $this->checkNoneSpecialCharacter($errorMessage, $key, $validateValue);
                    break;
                case self::ISKATAKANA:
                    $errorMessage = $this->checkIsKatakanaCharacter($errorMessage, $key, $validateValue);
                    break;
                case self::ISADDRESSKATAKANA:
                    $errorMessage = $this->checkIsAddressKatakanaCharacter($errorMessage, $key, $validateValue);
                    break;
                case self::NUMBER2DIGIT:
                    $errorMessage = $this->checkIsPercentage($errorMessage, $key, $validateValue);
                    break;
                case self::NUMBER10DIGIT:
                    $errorMessage = $this->checkIsNumber($errorMessage, $key, $validateValue, 10);
                    break;
                case self::PHONENUMBER:
                    $errorMessage = $this->checkIsPhoneNumber($errorMessage, $key, $validateValue);
                    break;
            }
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $rowData
     * @return mixed
     */
    public function getCedynaValidateErrorMessage($errorMessage, $rowData)
    {
        $cedynaRule = $this->getCedynaValidateData();

        foreach ($cedynaRule as $value) {
            switch ($value['validate']) {
                case self::CEDYNASPECIALVALUE:
                    $errorMessage = $this->checkCedynaSpecialValue($errorMessage, $value, $rowData);
                    break;
                case self::CEDYNAPHONE:
                    $errorMessage = $this->checkCedynaPhone($errorMessage, $value, $rowData);
                    break;
                case self::LENGTH:
                    $errorMessage = $this->checkLength($errorMessage, $value, $rowData);
                    break;
            }
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkRequiredData($errorMessage, $key, $validateValue)
    {
        if(is_numeric($validateValue)){
            return $errorMessage;
        }

        if (empty($validateValue)) {
            array_push( $errorMessage, __("column %1 is empty", $key) );
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @param $shoshaType
     * @return mixed
     */
    public function checkCedynaRequiredData($errorMessage, $key, $validateValue, $shoshaType)
    {
        if (!empty($shoshaType)) {
            $shoshaCode = $this->_shoshaCode->convertCodeToValue($shoshaType);
            if ($shoshaCode == \Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA && empty($validateValue)) {
                array_push( $errorMessage, __("column %1 is empty", $key) );
            }
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkNoneSpecialCharacter($errorMessage, $key, $validateValue)
    {
        if (!empty($validateValue) && $this->hasSpecialCharacter($validateValue)) {
            array_push( $errorMessage, __("column %1 has contains special character", $key) );
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkIsKatakanaCharacter($errorMessage, $key, $validateValue)
    {
        if (!empty($validateValue) && !$this->isKatakana($validateValue)) {
            array_push( $errorMessage, __("column %1 must be Katakana character", $key) );
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkIsAddressKatakanaCharacter($errorMessage, $key, $validateValue)
    {
        if (!empty($validateValue) && !$this->isAddressKatakana($validateValue)) {
            array_push( $errorMessage, __("column %1 must be Katakana character, ASCII character, number and symbol", $key) );
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @param $length
     * @return mixed
     */
    public function checkIsNumber($errorMessage, $key, $validateValue, $length)
    {
        if (!empty($validateValue) && ( !preg_match('/^[0-9]*$/', $validateValue) || strlen($validateValue) < $length )) {
            array_push( $errorMessage, __("column %1 must be %2 digits and only number", $key, $length) );
        }
        return $errorMessage;
    }

    /**
     * CheckIsPercentage
     *
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkIsPercentage($errorMessage, $key, $validateValue)
    {
        if (( !preg_match('/^(\d{1,2})(?:\.\d{1,2})?$/', $validateValue))) {
            array_push( $errorMessage, __("column %1 must be valid commission", $key) );
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $key
     * @param $validateValue
     * @return mixed
     */
    public function checkIsPhoneNumber($errorMessage, $key, $validateValue)
    {
        if (!empty($validateValue) && !preg_match('/^[0-9\-]*$/', $validateValue)) {
            array_push( $errorMessage, __("column %1 must only number and \"-\"", $key) );
        }
        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $value
     * @param $rowData
     * @return mixed
     */
    public function checkCedynaSpecialValue($errorMessage, $value, $rowData)
    {
        if (!empty( $rowData[ $value['index'] ] )) {

            $validateValue = $rowData[ $value['index'] ];

            if (!empty( $validateValue )) {

                $unicode = $this->utf8ToUnicode($validateValue);

                if (!empty( $unicode )) {

                    if (in_array($unicode, $this->getKatakanaSpecialValues())) {
                        array_push( $errorMessage, __( "column %1 must not include only %2", $value['key'], "(&#65438;)(&#65439;)(&#65377;)") );
                    }

                    $startChar = $unicode[0];
                    $endChar = $unicode[ sizeof($unicode) - 1 ];

                    if ($startChar == $this->_cedynaStartChar || $endChar == $this->_cedynaEndChar) {
                        array_push( $errorMessage, __( "column %1 must not end with \")\" nor start with \"(\" ", $value['key']) );
                    }
                }
            }
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $value
     * @param $rowData
     * @return mixed
     */
    public function checkCedynaPhone($errorMessage, $value, $rowData)
    {
        if (!empty( $rowData[ $value['index'] ] )) {
            $validateValue = $rowData[ $value['index'] ];

            if (!empty( $validateValue ) && ( !preg_match('/^[0-9]*$/', $validateValue) || substr($validateValue, 0, 1) != 0 )) {
                array_push( $errorMessage, __( "column %1 must not include \"-\" and must start from 0", $value['key']) );
            }
        }

        return $errorMessage;
    }

    /**
     * @param $errorMessage
     * @param $value
     * @param $rowData
     * @return mixed
     */
    public function checkLength($errorMessage, $value, $rowData)
    {
        $strLength = 0;

        foreach ($value['index'] as $index) {

            if (!empty( $rowData[$index] )) {
                $strLength += $this->getStringLength( $rowData[$index] );
            }
        }

        if ($strLength > 0 && $strLength > $value['length']) {
            array_push( $errorMessage, __( "Length of %1 must be equal or less than %2 digits", $value['key'], $value['length']) );
        }

        return $errorMessage;
    }

    /**
     * @param $str
     * @return bool
     */
    public function hasSpecialCharacter($str)
    {
        $unicode = $this->utf8ToUnicode($str);
        $specialCharacter = $this->getSpecialCharacters();
        $res = false;
        foreach ($unicode as $uni) {
            if ( in_array($uni, $specialCharacter) ) {
                $res = true;
                break;
            }
        }
        return $res;
    }

    /**
     * @param $str
     * @return array
     */
    public function utf8ToUnicode($str)
    {
        $unicode = [];
        $values = [];
        $lookingFor = 1;

        /*get string length*/
        $strLen = strlen($str);

        for ($i = 0; $i < $strLen; $i++ ) {

            $thisValue = ord( $str[$i] );

            if ( $thisValue < 128 ) {
                $unicode[] = $thisValue;
            } else {

                if ( count( $values ) == 0 ) {
                    $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
                }

                $values[] = $thisValue;

                if (count( $values ) == $lookingFor) {

                    if ($lookingFor == 3) {
                        $number = ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 );
                    } else {
                        $number = ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
                    }

                    $unicode[] = $number;
                    $values = [];
                    $lookingFor = 1;
                }
            }
        }

        return $unicode;
    }

    /**
     * Returns if a given unicode value is a japanese character
     * Returns  0 if not japanese
     *          1 if Zen Kaku
     *          2 if Han Kaku
     *          3 if Not Han Kaku but Japanese Character (Hiragana, Kanji, etc)
     *
     * @param $unicodeVal
     * @return int japanese
     */
    public function characterType($unicodeVal)
    {
        $ret = 0;
        //unicodeVal is a single value only
        if ($unicodeVal == 8221) {
            //right double quotation
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 12288 && $unicodeVal <= 12351) {
            //Japanese Style Punctuation
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 12352 && $unicodeVal <= 12447) {
            //Hiragana
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 12448 && $unicodeVal <= 12543) {
            //Katakana
            $ret = self::TYPEOTHERJAPANESE;
        } elseif($unicodeVal >= 12784 && $unicodeVal <= 12799) {
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 12800 && $unicodeVal <= 13054) {
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 65280 && $unicodeVal <= 65376) {
            //full width roman character (Zen Kaku)
            $ret = self::TYPEZENKAKU;
        } elseif ($unicodeVal >= 65377 && $unicodeVal <= 65439) {
            //half width character (Han Kaku)
            $ret = self::TYPEHANKAKU;
        } elseif ($unicodeVal >= 65504 && $unicodeVal <= 65510) {
            //full width character (Zen Kaku)
            $ret = self::TYPEZENKAKU;
        } elseif ($unicodeVal >= 65512 && $unicodeVal <= 65518) {
            //half width character (Han Kaku)
            $ret = self::TYPEHANKAKU;
        } elseif ($unicodeVal >= 19968 && $unicodeVal <= 40879) {
            //common and uncommon kanji
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 13312 && $unicodeVal <= 19903) {
            //Rare Kanji
            $ret = self::TYPEOTHERJAPANESE;
        } elseif ($unicodeVal >= 97 && $unicodeVal <= 122) {
            /*latin alphabet characters - lowercase*/
            $ret = self::TYPELATINTOLOWER;
        } elseif ($unicodeVal >= 65 && $unicodeVal <= 90) {
            /*latin alphabet characters - uppercase*/
            $ret = self::TYPELATINTOUPPER;
        } else {
            $katakanaSpecialCharacter = $this->getKatakanaSpecialCharacters();
            if (in_array($unicodeVal, $katakanaSpecialCharacter)) {
                $ret = self::TYPEKATAALLOW;
            } else {
                $symbolCharacters = $this->getSymbolCharacters();
                if (in_array($unicodeVal, $symbolCharacters)) {
                    $ret = self::TYPESYMBOL;
                }
            }
        }

        return $ret;
    }

    /**
     * @param $str
     * @return bool
     */
    public function isKatakana($str)
    {
        $unicode = $this->utf8ToUnicode($str);

        $res = true;

        /*character allow for katakana type*/
        $addressAllow = [
            /*katakana character*/
            self::TYPEHANKAKU,
            /*number and (, ), -, #*/
            self::TYPEKATAALLOW,
            /*latin alphabet characters - uppercase*/
            self::TYPELATINTOUPPER,
            /*latin alphabet characters - lowercase*/
            self::TYPELATINTOLOWER,
            /*symbol*/
            self::TYPESYMBOL
        ];

        foreach ($unicode as $uni) {
            $charType = $this->characterType($uni);
            if ( !in_array($charType, $addressAllow)) {
                $res = false;
                break;
            }
        }

        return $res;
    }

    /**
     * @param $str
     * @return bool
     */
    public function isAddressKatakana($str)
    {
        /*convert string to unicode value*/
        $unicode = $this->utf8ToUnicode($str);

        $res = true;

        /*character allow for address type*/
        $addressAllow = [
            /*katakana character*/
            self::TYPEHANKAKU,
            /*number and (, ), -, #*/
            self::TYPEKATAALLOW,
            /*latin character - lowercase*/
            self::TYPELATINTOLOWER,
            /*latin character - uppercase*/
            self::TYPELATINTOUPPER,
            /*symbol*/
            self::TYPESYMBOL
        ];

        foreach ($unicode as $uni) {
            /*get character type by unicode value*/
            $charType = $this->characterType($uni);

            if ( !in_array($charType, $addressAllow) ) {
                $res = false;
                break;
            }
        }

        return $res;
    }

    /**
     * @param $str
     * @return int
     */
    public function getStringLength($str)
    {
        return mb_strlen( $str, 'UTF-8' );
    }
}