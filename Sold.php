<?php
namespace Anonym;


class Sold
{


    public function makeSoldItems($newIds)
    {

        if (!\function_exists('wp_set_object_terms')) {
            return false;
        }

        $oldIds = json_decode(file_get_contents(__DIR__ . '/last_ids.json'), true);


        $soldItems = array_filter($oldIds, function ($oldId) use ($newIds) {
            return !in_array($oldId, $newIds, false);
        });


        foreach ($soldItems as $item) {
            wp_set_post_terms($item, ['Sold!'], 'offer-type');
        }

        echo sprintf('%d item satıldı olarak işaretlendi <br/>', count($soldItems));

    }
}