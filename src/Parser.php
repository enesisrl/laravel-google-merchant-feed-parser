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

    protected string $feed_type = '';
    protected string $feed_data = '';


    public function __construct(string $url, int $options = 0, bool $dataIsURL = true, string $namespaceOrPrefix = "", bool $isPrefix = false)
    {
        if ($dataIsURL) {
            $feedContent = Http::get($url);
        }else{
            $feedContent = $url;
        }

        parent::__construct($feedContent, $options, false, $namespaceOrPrefix, $isPrefix);


        // Controlla se il feed è di tipo RSS
        if (isset($this->channel)) {
            $this->feed_type = "RSS";
        }

        // Controlla se il feed è di tipo Atom
        if ($this->getName() === "feed") {
            $this->feed_type = "Atom";
        }

        $this->getData();

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

    /**
     * @throws JsonException
     */
    public function getData(){

        $feed_data = [];


        switch ($this->feed_type) {
            case "Atom":
                $feed_data['title'] = property_exists($this,'title') ?(string)$this->title : null;
                $feed_data['description'] = property_exists($this,'description') ?(string)$this->description : null;
                $feed_data['link'] = ($this->link->attributes()->href) ?(string)$this->link->attributes()->href : null;
                $feed_data['pubDate'] = property_exists($this,'updated') ? (string)$this->updated : null;
                $feed_data['items'] = [];
                foreach($this->entry as $entry){
                    $feed_data['items'][] = (array)json_decode(json_encode($entry, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
                }
                break;
            case "RSS":
                $data = Arr::get($this->toArray(),'channel');
                $feed_data['title'] = property_exists($data,'title') ? $data->title : null;
                $feed_data['description'] = property_exists($data,'description') ? $data->description : null;
                $feed_data['link'] = property_exists($data,'link') ? $data->link : null;
                $feed_data['pubDate'] = property_exists($this,'pubDate') ? $this->pubDate : null;
                $feed_data['items'] = [];
                if (property_exists($data, 'item') && is_array($data->item)) {
                    $feed_data['items'] = $data->item;
                }
                break;
        }
        $this->feed_data = json_encode($feed_data, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function feedData(){
        return json_decode($this->feed_data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function getTitle(){
        $data = $this->feedData();
        return Arr::get($data,'title');
    }

    /**
     * @throws JsonException
     */
    public function getDescription(){
        $data = $this->feedData();
        return Arr::get($data,'description');
    }

    /**
     * @throws JsonException
     */
    public function getLink(){
        $data = $this->feedData();
        return Arr::get($data,'link');
    }

    /**
     * @throws JsonException
     */
    public function getPubDate(): ?Carbon
    {
        $data = $this->feedData();
        return (Arr::get($data,'pudDate')) ? Carbon::parse(Arr::get($data,'pudDate')) : null;
    }
    /**
     * @throws JsonException
     */
    public function getItems(): array
    {
        $data = $this->feedData();
        return (Arr::get($data, 'items')) ?: [];
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
