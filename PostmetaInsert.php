<?php
namespace Anonym;

class PostmetaInsert
{

    private $attr = [
        'estate_attr_bedrooms' => 'myhome_estate_attr_bedrooms',
        'estate_attr_bathrooms' => 'myhome_estate_attr_bathrooms',
        'estate_attr_price' => 'myhome_estate_attr_price',
        'estate_gallery' => 'myhome_estate_gallery',
        'estate_location' => 'myhome_estate_location',
    ];

    private $data = [
        'bedrooms' => 'estate_attr_bedrooms',
        'bathrooms' => 'estate_attr_bathrooms',
        'price' => 'estate_attr_price',
        'location' => 'estate_location',
        'images' => 'estate_gallery',
    ];

    private $terms = [
        'features',
        'city',
        // 'neighborhood',
        'street',
        'zip-code',
        'property-type',
        'offer-type',
        //['property-type']
    ];

    private $offerTypes = [
        1 => 'For Sale',
        2 => 'For Rent'
    ];


    private $propertyTypes = [
        'Houses' => [1, 2, 3, 4, 5, 6, 21, 22, 23, 24, 27, 30, 95, 128, 131],
        'Apartments' => [7, 8, 9, 10, 11, 28, 29, 44, 56, 59, 142, 143, 144, 511],
        'Character Property' => [43, 52, 53, 62, 65, 68, 71, 74, 77, 118, 119, 120, 121, 113, 116, 140, 141],
        'Commercial Property' => [],
        'Land' => [110, 107, 20, 125],
        'Garage / Parking' => [45],
        'Retirement Property' => [46, 47],
        'Mobile / Park Homes' => [16, 50, 117],
        'House / Flat Share' => [48, 49]
    ];


    public function __construct()
    {

        $this->propertyTypes['Commercial Property'] = array_merge([80, 83, 86, 92, 101, 104, 134, 137, 307, 310], range(128, 301));


    }


    public function insert($post_id, $data)
    {

        if (!\function_exists('add_post_meta')) {
            return false;
        }

        $return = [

        ];

        $data['location'] = [
            'address' => sprintf('%s %s', $data['street'], $data['city']),
            'lng' => $data['lng'],
            'lat' => $data['lat']
        ];

        // find the right offer-type, etc: For Sale, For Rent
        $data['offer-type'] = $this->offerTypes[$data['offer-type']];


        $data['property-type'] = $this->findPropertyType($data['type']);


        //$data['location'] = $this->getAddress($data['address']);
        // now we add default tables

        foreach ($this->data as $index => $attr) {
            $selected = $data[$index];
            $_attr = $this->attr[$attr];

            $_attr_id = add_post_meta($post_id, '_' . $attr, $_attr);
            $return[] = [
                'id' => $_attr_id,
                'key' => '_' . $attr,
                'value' => $_attr
            ];

            if ($index === 'location' || $index === "images") {
                $selected = serialize($selected);
            }


            $attr_id = add_post_meta($post_id, $attr, $selected);

            $return[] = [
                'id' => $attr_id,
                'key' => $attr,
                'value' => $selected
            ];
        }

        add_post_meta($post_id, 'estate_views', "1");

        foreach ($this->terms as $term) {

            $selectedTerm = $data[$term];
            wp_set_post_terms($post_id, $selectedTerm, $term);

        }

        return $return;
    }

    /**
     * finds the property type
     *
     * @param $type
     * @return int|string
     */
    private function findPropertyType($type)
    {
        foreach ($this->propertyTypes as $index => $types) {
            if (\in_array($type, $types, false)) {
                return $index;
            }
        }

        return 'Not Speciied';
    }

    public function getAddress($address)
    {
        $ch = curl_init('http://maps.google.com/maps/api/geocode/json?address=' . urlencode($address));

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept-Language' => 'en-US,en;q=0.5'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);

        $result = json_decode($data, true);

        $results = $result["results"][0];

        $geometry = $results["geometry"]["location"];
        $formatted = $results["formatted_address"];

        $geometry["address"] = $formatted;

        return $geometry;
    }

}