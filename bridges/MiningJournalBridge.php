<?php

class MiningJournalBridge extends BridgeAbstract
{
    const NAME = 'The Mining Journal';
    const URI = 'https://www.miningjournal.net/news/local/';
    const DESCRIPTION = 'Returns the recent articles published on The Mining Journal';

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
        $page = 1;
        $count = 0;
        while ($count < $this->getInput('max')) {
            $home_html = getSimpleHTMLDOM(self::URI . 'page/' . $page++);
            $articles = $home_html->find('article');
            foreach ($articles as $article) {
                $url = $article->find('a', 0);
                $article_html = getSimpleHTMLDOMCached($url->href);

                $item['uri'] = $url->href;
                $item['title'] = trim($article->find('h1',0)->plaintext);
                $item['timestamp'] = trim($article_html->find('time', 0)->plaintext);
                $related = $article_html->find('section#related', 0);
                $content = $article_html->find('#article_content', 0);
                $content = stripWithDelimiters($content, '<section id="related">', '</section>');
                $content = substr($content, 0, strpos($content, '<style type="text/css">'));
                $item['content'] = sanitize($content);
                $this->items[] = $item;
                $count++;
            }
        }
    }
}
