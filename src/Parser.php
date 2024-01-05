<?php

namespace Enesisrl\LaravelGoogleMerchantFeedParser;

use JsonException;
use JsonSerializable;
use SimpleXMLElement;
use Illuminate\Support\Facades\Http;

class Parser extends SimpleXMLElement implements JsonSerializable{

    protected string $feedUrl;
    protected string $feedContent;

    public function __construct(string $url, int $options = 0, bool $dataIsURL = true, string $namespaceOrPrefix = "", bool $isPrefix = false)
    {
        if ($dataIsURL) {
            $this->feedUrl = $url;
            $this->feedContent = Http::get($this->feedUrl);
        }else{
            $this->feedContent = $url;
        }
        parent::__construct($this->feedContent, $options, false, $namespaceOrPrefix, $isPrefix);
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

}
