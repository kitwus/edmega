<?php
namespace Anonym;

class Parser
{


    private $rentFrequency = [
        'week',
        'month',
        'quarter',
        'annual',
    ];


    private $prorities;

    /**
     * @param $dom
     * @param $name
     * @return bool
     */
    public function getElement($dom, $name, $check = true)
    {
        $element = isset($dom->getElementsByTagName($name)[0]) ? $dom->getElementsByTagName($name)[0]->textContent : false;


        if ($check && $element && $element === '') {
            return false;
        }

        return $element;
    }

    public function getAddress($dom)
    {
        $poscode = $this->getElement($dom, 'POSTCODE1') . " " . $this->getElement($dom, 'POSTCODE2');
        $address = $this->getElement($dom, 'ADDRESS_1') . " " . $this->getElement($dom, 'ADDRESS_2');

        return $address . " " . $poscode;
    }


    public function getAfterPrice($dom)
    {
        $f = $this->getElement($dom, 'LET_RENT_FREQUENCY');

        return isset($this->rentFrequency[$f]) ? $this->rentFrequency[$f] : 'month';
    }

    public function getSummary($dom)
    {
        return $this->getElement($dom, 'SUMMARY');
    }

    public function getDescription($dom)
    {
        return $this->getElement($dom, 'DESCRIPTION');
    }

    public function getPropertyType($dom)
    {
        return $this->getElement($dom, 'PROP_SUB_ID');
    }

    public function getPrice($dom)
    {
        return $this->getElement($dom, 'PRICE');
    }

    public function getTitle($dom)
    {
        return trim($this->getElement($dom, 'HEADLINE'));
    }

    public function getBedrooms($dom)
    {
        return $this->getElement($dom, 'BEDROOMS');
    }

    public function getBathrooms($dom)
    {
        return $this->getElement($dom, 'BATHROOMS');
    }

    public function getFeatures($dom)
    {
        $features = [];

        for ($i = 1; $i <= 10; $i++) {

            $feature = $this->getElement($dom, 'FEATURE' . $i);

            if ($feature) {
                $features[$i] = $feature;
            }
        }

        return $features;
    }

    public function getFile($fileName = 'data.xml')
    {
        $file = fopen(__DIR__ . DIRECTORY_SEPARATOR . $fileName, 'w+');

       
        $ch = curl_init('');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// set large timeout to allow curl to run for a longer time
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_USERAGENT, 'any');
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_exec($ch);
        curl_close($ch);
    }

    public function getImages($dom)
    {
        $images = [];


        for ($i = 0; $i < 20; $i++) {
            $sayi = $i < 10 ? '0' . $i : $i;

            $image = $this->getElement($dom, 'MEDIA_IMAGE_' . $sayi);
            $text = $this->getElement($dom, 'MEDIA_IMAGE_TEXT_' . $sayi);

            if ($image) {
                $images[] = [
                    'image' => $image,
                    'text' => $text
                ];
            }
        }

        return $images;

    }

    public function getPostCode($dom)
    {
        return [
            $this->getElement($dom, 'POSTCODE1'),
            $this->getElement($dom, 'POSTCODE2')
        ];
    }

    /**
     * @param \SimpleXMLReader $reader
     */
    public function parseProperty(\SimpleXMLReader $reader)
    {
        $dom = $reader->expandDomDocument();


        $features = $this->getFeatures($dom);

        $this->prorities[] = [
            'id' => $this->getElement($dom, 'AGENT_REF'),
            'type' => $this->getPropertyType($dom),
            'features' => $features,
            'city' => $this->getElement($dom, 'TOWN'),
            'bedrooms' => $this->getBedrooms($dom),
            'bathrooms' => $this->getBathrooms($dom),
            'images' => $this->getImages($dom),
            'price' => $this->getPrice($dom),
            'title' => $this->getTitle($dom),
            'description' => $this->getDescription($dom),
            'summary' => $this->getSummary($dom),
            'address' => $this->getAddress($dom),
            'views' => 1,
            'offer_type_after_price' => $this->getAfterPrice($dom),
            'zip-code' => $this->getPostCode($dom),
            'offer-type' => $this->getElement($dom, 'TRANS_TYPE_ID'),
            'neighborhood' => $this->getElement($dom, 'ADDRESS_1'),
            'street' => $this->getElement($dom, 'ADDRESS_2'),
            'lng' => $this->getElement($dom, 'LONGITUDE'),
            'lat' => $this->getElement($dom, 'LATITUDE')
        ];


        return true;
    }

    public function getIds()
    {
        return array_map(function ($property) {
            return $property['id'];
        }, $this->prorities);
    }

    public function saveIds($ids)
    {
        file_put_contents('last_ids.json', json_encode($ids));

        return $ids;
    }

    public function parse($name)
    {
        $reader = new \SimpleXMLReader();

        $reader->open($name);

        $reader->registerCallback('property', [$this, 'parseProperty']);

        $reader->parse();

        $reader->close();

        return $this->prorities;
    }

}