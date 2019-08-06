<?php


namespace App\api\v1;


class SearchTerm
{
    const STATE_GETSTREET = 0;
    const STATE_GETCITY = 1;
    const STATE_GETCOUNTRY = 2;
    const STATE_UNDEFINED = -1;

    const COUNTRY_SWITZERLAND = 'CH';
    const COUNTRY_LIECHTENSTEIN = 'LI';

    const RESULT_TYPE_INTERPOLED = 1; // street
    const RESULT_TYPE_APPROXIMATE = 2; // city
    const RESULT_TYPE_ROOFTOP = 3; // building (entrance)
    const RESULT_TYPE_NA = 0; // none of the above

    public  $term;
    private $csvcnt;     // {0,2,3}
    private $items;
    public  $code;       // 200 is OK
    public  $message;    // 'OK' or error message
    private $state;      // current state
    public  $resultType; // what to expect

    public  $street;
    public  $streetnumber;
    public  $city;
    public  $postcode;
    public  $countrycode;

    /**
     * Build an object based upon the term given by the user.
     * NOTES:
     * 1. The middleware will automatically convert + (or %20) to whitespace.
     * 2. Street names and city names can contain numbers, characters & whitespace.
     * 3. Street numbers contain numbers and/or chars or null, but no whitespace.
     * 4. Scope: Switzerland and Liechtenstein, postcodes '1000'..'9999'
     *
     * @param $term the seach term as given by the user
     *
     * Variants:
     * street[ streetnumber][, [postcode ]city][, country[code]]
     * streetnumber, postcode, city, country[code] are optional
     * comma seperated values
     */
    public function __construct($term)
    {
        $this->term = $term;
        $this->items = $this->buildItems($term);
        $this->parseItems();
        $this->resultType = $this->getResultType();
    }

    /**
     * Build an array with the text items of the term entered by the user.
     *
     * @param string $term
     * @return array
     */
    private function buildItems($term)
    {
        $csv = explode(',', $term);
        $this->csvcnt = count($csv);
        if ($this->csvcnt>0){
            $items = array();
            foreach ($csv as $item){
                $texts = explode(' ', $item);
                foreach ($texts as $text){
                    if (!empty($text)){
                        $items[] = $text;
                    }
                }
                $items[] = ',';
            }
            array_pop($items); // remove last item(komma)
        }
        else{
            $items = explode(' ', $norepeat);
        }
        return $items;
    }

    /**
     * Parse the $items array,
     * initialize (output) the search objects $street .. $countrycode
     * if errors are found, add message to $errormessage (not null)
     */
    private function parseItems()
    {
        $this->state = self::STATE_GETSTREET;
        $this->code = 200; // optimistic approach
        $this->message = 'OK';

        foreach ($this->items as $key => $item){

            if ($this->state===self::STATE_GETSTREET){
                $ligs = $this->isLastItemGetStreet($key);
                if ($item===','){
                    $this->state = self::STATE_GETCITY;
                }
                elseif ($ligs===false){
                    $this->street = (empty($this->street))?
                        $item: $this->street.' '.$item;
                }
                elseif ($ligs===true){
                    if ($this->hasNumber($item)){
                        $this->streetnumber = $item;
                    }
                    else{
                        $this->street = (empty($this->street))?
                            $item: $this->street.' '.$item;
                    }
                }
            }
            elseif ($this->state===self::STATE_GETCITY){
                $isPostcode = $this->isPostcode($item);
                if ($item===','){
                    $this->state = self::STATE_GETCOUNTRY;
                }
                elseif ($isPostcode===false){
                    $this->city = (empty($this->city))?
                        $item: $this->city.' '.$item;
                }
                elseif ($isPostcode===true){
                    $this->postcode = $item;
                }
            }
            elseif ($this->state==self::STATE_GETCOUNTRY){
                $this->countrycode = $this->setCountryCode($item);
                $this->state = self::STATE_UNDEFINED;
            }
            else{
                $this->code = 500; // internal server error
                $this->message = 'Illegal state encountered!';
            }
        }
    }

    /**
     * Check to see if an item is the last part of the address segment
     * @param int $key
     * @return bool true:end of address segment
     */
    private function isLastItemGetStreet($key)
    {
        if (array_key_exists($key+1,$this->items)){
            if ($this->items[$key+1]===','){
                return true; // next item is a comma
            }
        }
        else{
            return true; // $this->items[$key] is last item in array
        }
        return false;
    }

    /**
     * Check to see if an item is a postcode (1000..9999).
     * @param string $item
     * @return bool true:postcode, false: not a postcode
     */
    private function isPostcode($item)
    {
        return ((strlen($item)===4)&&(ctype_digit($item))&&($item>='1000'));
    }

    /**
     * @param $item
     * @return bool true: has number, false: not a number in there!
     */
    private function hasNumber($item)
    {
        return preg_match( '/\d/', $item );
    }

    /**
     * Parse the country(code) and return the 'offical' value for Switzerland
     * or Liechtenstein.
     *
     * @param string $item country or countrycode
     * @return string {CH,LI,X1,X2}
     */
    private function setCountryCode($item)
    {
        try{
            $code = strtolower($item);
            if (($code==='ch')||
                (substr($code, 0, 3)==='sch')){
                // Schweiz
                return self::COUNTRY_SWITZERLAND;
            }
            elseif (($code==='li')||
                (substr($code, 0, 3)==='lie')){

                return self::COUNTRY_LIECHTENSTEIN;
            }
        }
        catch (\Exception $e){
            // do nothing
        }
        $this->code = 500; // internal server error
        $this->message = 'Illegal country(code)!';
        return 'X2';
    }

    /**
     * Define the result type to be expected from the input terms
     * @return int
     */
    private function getResultType()
    {
        $hasStreet = !empty($this->street);
        $hasNumber = !empty($this->streetnumber);
        $hasCity = !empty($this->city);
        if ($hasStreet&&$hasNumber&&$hasCity){
            return self::RESULT_TYPE_ROOFTOP; // find exact match
        }
        if ($hasStreet){
            return self::RESULT_TYPE_INTERPOLED; // number, city: don't care
        }
        if(!$hasStreet&&!$hasNumber&&$hasCity){
            return self::RESULT_TYPE_APPROXIMATE; // postcode: don't care
        }
        return self::RESULT_TYPE_NA;
    }
}