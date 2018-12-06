<?php

namespace App\Services;

use DateTime;
use SimpleXMLElement;

class UpWork
{
    public $title;
    public $description;
    public $category;
    public $country;
    public $skills;
    public $budget;
    public $link;
    public $created_date;
    public $created_timestamp;
    public $posted_date;

    public function __construct($params)
    {
        if (is_array($params)) {

            foreach ($params as $option => $value) {

                if (property_exists(self::class, $option)) {

                    $this->{$option} = $value;
                }
            }
        }
    }

    public function getHash()
    {
        return md5($this->title . $this->created_timestamp);
    }

    public static function fetchFromXml(SimpleXMLElement $xml_item)
    {
        $description = (string) $xml_item->description;
        $created_date = (string) $xml_item->pubDate;
        $created_timestamp = DateTime::createFromFormat('D, d M Y H:i:s O', $created_date)->format('U');

        $fields = self::parseFields($description);

        return new self([
            'title'             => (string) $xml_item->title,
            'link'              => (string) $xml_item->link,
            'description'       => $description,
            'created_date'      => $created_date,
            'created_timestamp' => $created_timestamp,
            'category'          => $fields['category'],
            'country'           => $fields['country'],
            'skills'            => $fields['skills'],
            'budget'            => $fields['budget'],
            'posted_date'       => $fields['posted_date'],
        ]);
    }

    private static function parseFields($description)
    {
        $fields = [
            'category'          => null,
            'country'           => null,
            'skills'            => null,
            'budget'            => null,
            'posted_date'       => null,
        ];

        if (preg_match("{<br /><b>Category</b>: (.+?)<}s", $description, $matches)) {
            $fields['category'] = trim($matches[1]);
        }

        if (preg_match("{<br /><b>Country</b>: (.+?)<}s", $description, $matches)) {
            $fields['country'] = trim($matches[1]);
        }

        if (preg_match("{<br /><b>Skills</b>: (.+?)<}s", $description, $matches)) {

            $skills_list = explode(',', $matches[1]);

            $skills = [];

            foreach ($skills_list as $skill) {
                $skills[] = trim($skill);
            }

            $fields['skills'] = $skills;
        }

        if (preg_match("{<br /><b>Budget</b>: (.+?)<}s", $description, $matches)) {
            $fields['budget'] = preg_replace("{[$,\.]}", '', $matches[1]);
        }

        if (preg_match("{<br /><b>Posted On</b>: (.+?)<}s", $description, $matches)) {
            $fields['posted_date'] = $matches[1];
        }

        return $fields;
    }
}