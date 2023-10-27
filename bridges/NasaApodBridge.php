<?php

class NasaApodBridge extends BridgeAbstract
{
    const NAME = 'NASA APOD Bridge';
    const URI = 'https://apod.nasa.gov/apod/';
    const DESCRIPTION = 'Returns the latest NASA APOD pictures and explanations';

    public function collectData()
    {
        $xmlString = getContents('https://apod.com/feed.rss');
        $xmlString = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xmlString);
        $xml = simplexml_load_string(trim($xmlString));
        foreach ($xml->channel[0]->item as $item) {
            $this->items[] = $this->parseXmlItem($item);
        }
    }

    public function getName() {
        return "Astronomy Picture of the Day";
    }

    public function getIcon() {
        return 'https://apod.nasa.gov/favicon.ico';
    }

    private function parseXmlItem($feedItem) {
        $item = [
            'uri'           => (string)$feedItem->link,
            'title'         => (string)$feedItem->title,
            'content'       => (string)$feedItem->content,
            'timestamp'     => strtotime((string)$feedItem->pubDate),
            'author'        => '',
        ];
        $namespaces = $feedItem->getNamespaces(true);
        foreach ($feedItem->children($namespaces['dc'])->creator as $creator) {
            $item['author'] .= (string)$creator.", ";
        }
        $item['author'] = rtrim($item['author'], ", ");
        $item['content'] = (string)$feedItem->children($namespaces['content'])->encoded;

        // replace youtube embed with thumbnail link
        $item['content'] = preg_replace(
            '/<iframe.+src="https:\/\/www\.youtube.com\/embed\/([a-zA-Z0-9_]+).+><\/iframe>/',
            '<a href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/sddefault.jpg"></a>',
            $item['content']
        );
        return $item;
    }
}
