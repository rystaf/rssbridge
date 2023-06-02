<?php

class SMBCBridge extends FeedExpander
{
    const NAME           = 'Saturday Morning Breakfast Cereal';
    const URI            = 'https://www.smbc-comics.com';
    const DESCRIPTION    = 'Latest comics.';

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        $content = str_get_html(html_entity_decode($item['content']));
        $comicURL = $content->find('img')[0]->{'src'};
        $title = $content->find('p')[0]->plaintext;
        $title = str_replace("Hovertext:", "", $title);
        $news = substr($item['content'], strpos($item['content'], "News:<br />") + 11);

        $html = getSimpleHTMLDOMCached($item['uri']) or returnServerError('Could not request ' . $this->getURI());
        $afterURL = $html->find('#aftercomic img', 0)->{'src'};

        $item['content'] = "<figure><img src=\"{$comicURL}\"><figcaption><p>{$title}</p></figcaption></figure><p><img src=\"{$afterURL}\"></p>{$news}";

        return $item;
    }

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . '/comic/rss', 1);
    }

    public function getIcon() {
        return self::URI . 'favicon.ico';
    }
}
