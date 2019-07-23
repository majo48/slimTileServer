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

    public  $term;
    private $csvcnt;    // {0,2,3}
    private $items;
    public  $code;      // 200 is OK
    public  $message;   // 'OK' or error message
    private $state;     // current state

    public  $street;
    public  $streetnumber;
    public  $city;
    public  $postcode;
    public  $countrycode;

    /**
     * Build an object based upon the term given by the user.
     * NOTES:
     * 1. The middleware will automatically convert + (or %20) to whitespace.
     * 2. Street names and city names can contain whitespace.
     * 3. Scope: Switzerland and Liechtenstein
     *
     * @param $term the seach term as given by the user
     *
     * Variants:
     * street[ streetnumber][, [postcode ]city][, country[code]]
     * [streetnumber ]street[, [postcode ]city][, country[code]]
     */
    public function __construct($term)
    {
        $this->term = $term;
        $this->items = $this->buildItems($term);
        $this->parseItems();
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

            $digits = $this->countDigits($item);

            if ($this->state===self::STATE_GETSTREET){
                if ($item===','){
                    $this->state = self::STATE_GETCITY;
                }
                elseif ($digits===0){
                    $this->street = (empty($this->street))?
                        $item: $this->street.' '.$item;
                }
                elseif ($digits>0){
                    $this->streetnumber = $item;
                }
            }
            elseif ($this->state===self::STATE_GETCITY){
                if ($item===','){
                    $this->state = self::STATE_GETCOUNTRY;
                }
                elseif ($digits===0){
                    $this->city = (empty($this->city))?
                        $item: $this->city.' '.$item;
                }
                elseif ($digits>0){
                    $this->postcode = $item;
                }
            }
            elseif ($this->state==self::STATE_GETCOUNTRY){
                if ($digits===0){
                    $this->countrycode = $this->setCountryCode($item);
                }
                $this->state = self::STATE_UNDEFINED;
            }
            else{
                $this->code = 500; // internal server error
                $this->message = 'Illegal state encountered!';
            }
        }
    }

    /**
     * Count the number of numeric digits in string.
     * NOTE:
     * 1. Street numbers can contain trailing letters (A,B..)
     * 2. Postcodes too (e.g. NL)
     *
     * @param string $item
     * @return int with the number of digits(0..9)
     */
    private function countDigits($item)
    {
        if (ctype_digit(substr($item, 0, 1))){
            return preg_match_all( "/[0-9]/", $item );
        }
        return 0; // does not start with digit
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
}