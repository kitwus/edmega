<?php

namespace MyHomeCore\Estates\Prices;


use MyHomeCore\Estates\Estate;
use MyHomeCore\Terms\Term;

/**
 * Class Price
 * @package MyHomeCore\Estates
 */
class Price
{

    /**
     * @var float[]
     */
    private $values;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Term|null
     */
    private $offer_type;

    /**
     * @var Estate
     */
    private $estate;

    /**
     * Price constructor.
     *
     * @param \float[] $values
     * @param Currency $currency
     * @param Term|null $offer_type
     * @param Estate $estate
     */
    public function __construct($values, Currency $currency, Term $offer_type = null, Estate $estate = null)
    {
        $this->values = $values;
        $this->currency = $currency;
        $this->offer_type = $offer_type;
        $this->estate = $estate;
    }

    /**
     * @return float[]
     */
    public function get_values()
    {
        return $this->values;
    }

    /**
     * @return bool
     */
    public function is_range()
    {
        return count($this->values) > 1;
    }

    /**
     * @return string
     */
    public function get_formatted()
    {
        $price_formatted = $this->currency->get_formatted_price($this->values);

        if (!is_null($this->offer_type)) {
            $before_price = $this->offer_type->get_before_price();
            $after_price = $this->offer_type->get_after_price();

            if (!empty($before_price)) {
                $price_formatted = $before_price . ' ' . $price_formatted;
            }


            if ($this->estate && $this->estate->get_post()->to_array()) {
                $type = $this->estate->get_post()->to_array()['after_post_type'];


                $price_formatted .= ' /' . $type;
            } else {
                if (!empty($after_price)) {
                    $price_formatted .= ' ' . $after_price;
                }
            }
        }

        return $price_formatted;
    }

}