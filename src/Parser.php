<?php

namespace Enesisrl\LaravelGoogleMerchantFeedParser;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use JsonException;
use JsonSerializable;
use RuntimeException;
use SimpleXMLElement;
use Illuminate\Support\Facades\Http;

class Parser extends SimpleXMLElement implements JsonSerializable{

    public function __construct(string $url, int $options = 0, bool $dataIsURL = true, string $namespaceOrPrefix = "", bool $isPrefix = false)
    {
        if ($dataIsURL) {
            $feedContent = Http::get($url);
        }else{
            $feedContent = $url;
        }

        parent::__construct($feedContent, $options, false, $namespaceOrPrefix, $isPrefix);
    }

    public function jsonSerialize(): array|string|null
    {
        $array = array();

        // json encode attributes if any.
        if ($attributes = $this->attributes()) {
            $array['@attributes'] = iterator_to_array($attributes);
        }

        $namespaces = [null] + $this->getDocNamespaces(true);
        // json encode child elements if any. group on duplicate names as an array.
        foreach ($namespaces as $namespace) {
            foreach ($this->children($namespace) as $name => $element) {
                if (isset($array[$name])) {
                    if (!is_array($array[$name])) {
                        $array[$name] = [$array[$name]];
                    }
                    $array[$name][] = $element;
                } else {
                    $array[$name] = $element;
                }
            }
        }

        // json encode non-whitespace element simplexml text values.
        $text = trim($this);
        if ($text !== '') {
            if ($array) {
                $array['@text'] = $text;
            } else {
                $array = $text;
            }
        }

        // return empty elements as NULL (self-closing or empty tags)
        if (!$array) {
            $array = null;
        }

        return $array;
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        return (array)json_decode(json_encode($this, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    public function getData(){
        $data = $this->toArray();
        return Arr::get($data,'channel');
    }

    public function getTitle(){
        $data = $this->getData();
        return property_exists($data,'title') ? $data->title : null;
    }
    public function getDescription(){
        $data = $this->getData();
        return property_exists($data,'description') ? $data->description : null;
    }
    public function getLink(){
        $data = $this->getData();
        return property_exists($data,'link') ? $data->link : null;
    }
    public function getPubDate(){
        $data = $this->getData();
        return property_exists($data,'pubDate') ? Carbon::parse($data->pubDate) : null;
    }
    /**
     * @throws JsonException
     */
    public function getItems(): array
    {
        try {
            $data = $this->getData();
            if (property_exists($data,'item')){
                if (is_array($data->item)){
                    return $data->item;
                }

                throw new RuntimeException('Items not is array');
            }

            throw new RuntimeException('Property item not exists');
        } catch (JsonException $e){
            throw new JsonException($e->getCode(),$e->getMessage());
        }
    }

    public function downloadImage($url,$path=null,$filename=null){

        $content = Http::get($url);
        if ($path || $filename){
            if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            if (!$filename){
                $filename = pathinfo($url,PATHINFO_BASENAME);
            }
            $file = rtrim($path,"/")."/".ltrim($filename,"/");
            file_put_contents($file,$content);
            return $filename;
        }
        return $content;
    }

}
