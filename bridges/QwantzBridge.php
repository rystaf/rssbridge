<?php

class QwantzBridge extends FeedExpander
{
    const NAME           = 'Dinosaur Comics';
    const URI            = 'https://qwantz.com/';
    const DESCRIPTION    = 'Latest comic.';
    const CACHE_TIMEOUT = 0;

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . 'rssfeed.php');
    }

    protected function parseItem(array $item)
    {
        $item['author'] = 'Ryan North';

        preg_match('/title="(.*?)"/', $item['content'], $matches);
        $title = $matches[1] ?? '';

        preg_match('/title="(.*?)"/', $item['content'], $matches);
        $title = $matches[1];

        $content = str_get_html(html_entity_decode($item['content']));
        $comicURL = $content->find('img')[0]->{'src'};

        $subject = $content->find('a')[1];
        preg_match('/mailto:ryan@qwantz.com\?subject=(.*?)">/', $subject, $matches);
        $subject = urldecode($matches[1]);

        $p = (string)$content->find('P')[0];

        $item['content'] = "{$subject}<figure><img src=\"{$comicURL}\"><figcaption><p>{$title}</p></figcaption></figure>{$p}";

        return $item;
    }

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }
}
