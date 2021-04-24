<?php
namespace Anonym;


use JMathai\PhpMultiCurl\MultiCurl;

class WpPostInsert
{

    /**
     * @var array
     */
    private $propertyType;


    /**
     * WpPostInsert constructor.
     */
    public function __construct()
    {
        $this->propertyType = $this->buildPropertyTypeNames();
    }

    function Generate_Featured_Image($image_url, $post_id)
    {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = md5(uniqid('_wp_mega_upload', random_int(1, 99999))) . '.jpg';

        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'draft'
        );
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($post_id, $attach_id);
    }

    public function buildTitle($data)
    {
        $properyType = $this->propertyType[$data['type']];


        if ($data['bedrooms'] > 0) {
            return trim(sprintf('%s %d bedroom %s', $properyType, (int)$data['bedrooms'], $data['offer-type'] == 1 ? 'For Sale' : 'For Rent'));
        }


        return trim(sprintf('%s %s', $properyType, $data['offer-type'] == 1 ? 'For Sale' : 'For Rent'));
    }

    /**
     * @param $data
     * @return string
     */
    public function buildUniqueId($data)
    {
        return md5($data['title'] . "_" . $data['id'] . $data['description']);
    }

    /**
     * @return array
     */
    public function buildPropertyTypeNames()
    {
        $data = require(__DIR__ . '/data.php');

        $types = $data['types'];
        $ids = $data['ids'];


        $types = explode("\n", $types);
        $ids = array_map('intval', explode("\n", $ids));


        return array_combine($ids, $types);
    }

    /**
     * Post'u oluşturur ve image attachmentleri ekler
     *
     * @param array $data
     * @return array|bool
     */
    public function insert($data)
    {
        global $wpdb;


        if (!\function_exists('wp_insert_post')) {
            return false;
        }

        $defaultData = [
            'post_author' => 1,
        ];

        $data['title'] = $this->buildTitle($data);

        $upData = [
            'post_name' => sanitize_title($data['title']),
            'post_title' => $data['title'],
            'post_content' => $data['description'],
            'post_type' => 'estate',
            'post_status' => 'publish',
            'comment_status' => 'closed',
        ];

        $uniq = $this->buildUniqueId($data);
        
        $result = $wpdb->get_row("SELECT id FROM wp_posts WHERE agent_id = '$uniq'", OBJECT, 0);

        if ($result) {
            echo sprintf('%s zaten var <br/>', $upData['post_name']);

            return [
                'post_id' => $result->id,
                'images' => [],
                'already_added' => true
            ];
        }

        $insert = wp_insert_post(array_merge($defaultData, $upData), true);

        try {
            echo sprintf('%d bu konrol edilecek <br/>', $insert);        
        } catch (Exception $e) {
            echo $e->getMessage();
        }       
       

        $return = [
            'post_id' => $insert,
            'images' => [],
            'already_added' => false
        ];

        if ($insert) {
            $after_post_type = $data['offer_type_after_price'];

            $wpdb->query("UPDATE wp_posts SET agent_id = '$uniq', after_post_type = '$after_post_type' WHERE ID = $insert");

            try {
                if (\count($data['images']) > 0) {
                    $this->Generate_Featured_Image($data['images'][0]['image'], $insert);
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }


            $curl = MultiCurl::getInstance();

            $added = array_map(function ($image) use ($curl) {
                return array_merge($this->getImage($image['image'], $curl), [$image]);
            }, $data['images']);

            foreach ($added as $item) {
                list($image_path, $curl, $image) = $item;

                if ($curl->code !== 200) {
                    continue;
                }

                $wp_filetype = wp_check_filetype($image_path, null);


                $id = wp_insert_attachment([
                    'post_name' => $upData['post_name'],
                    'post_content' => $image['text'],
                    'post_status' => 'inherit',
                    'comment_status' => 'open',
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($image_path),
                ], $image_path);

                $return['images'][] = $id;
            }


            return $return;
        }


        return false;
    }

    private function getImage($image_url, MultiCurl $curl)
    {
        $upload_dir = wp_upload_dir();

        $filename = md5(uniqid('_wp_mega_upload', random_int(1, 99999))) . '.jpg';


        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        $fileOpen = fopen($file, 'w+');

        $added = $curl->addCurl($this->buildCurl($fileOpen, $image_url));

        return [$file, $added];
    }

    public function buildCurl($fp, $url)
    {
        $ch = curl_init($url);
// enable SSL if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// output to file descriptor
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// set large timeout to allow curl to run for a longer time
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_USERAGENT, 'any');
        return $ch;
    }
}