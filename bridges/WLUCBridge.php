<?php

class WLUCBridge extends BridgeAbstract
{
    const NAME = 'WLUC';
    const URI = 'https://www.uppermichiganssource.com/news/';
    const DESCRIPTION = 'Returns the recent articles published on WLUC';

    const PARAMETERS = [
        [
            'max' => [
                'name' => 'max',
                'type' => 'number',
                'required' => true
            ],
        ],
    ];

    public function collectData()
    {
        $home_html = getSimpleHTMLDOM(self::URI);
        $home_html = defaultLinkTo($home_html, 'https://www.uppermichiganssource.com/');

        $articles = $home_html->find('div.flex-feature');
        $count = 0;
        foreach ($articles as $article) {
            $url = $article->find('a', 0);

            $item['uri'] = $url->href;
            $item['title'] = trim($article->find('.headline',0)->plaintext);
            $item['author'] = trim($article->find('.author', 0)->plaintext);
            $article_html = getSimpleHTMLDOMCached($url->href);
            $time = trim($article_html->find('span.published-date-time', 0)->plaintext);
            $time = str_replace("Published: ", "", $time);
            $time = str_replace("at", "", $time);
            $item['timestamp'] = $time;
            $content = $article->find('figure.media-item img', 0);
            $content .= $article_html->find('section.body', 0);
            $item['content'] = sanitize($content);
            $this->items[] = $item;
            if (++$count == $this->getInput('max')){
                break;
            }
        }
    }
}
