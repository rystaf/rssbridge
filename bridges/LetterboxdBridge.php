<?php

class LetterboxdBridge extends FeedExpander
{
    const NAME           = 'Letterboxd';
    const URI            = 'https://letterboxd.com/';
    const DESCRIPTION    = 'Latest activity';
    const PARAMETERS = [ [
        'username' => [
            'name' => 'username',
            'type' => 'text',
            'exampleValue' => 'joelhaver',
            'required' => true
        ],
    ] ];

    public function detectParameters($url)
    {
        if (preg_match('/letterboxd\.com\/([a-z0-9\-]+)/', $url, $matches) && count($matches) > 1) {
            return [
                'username' => $matches[1]
            ];
        }
        return null;
    }


    private $feedIcon;

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached(self::URI . $this->getInput('username'));
        $this->feedIcon = $html->find("meta[property=og:image]", 0)->getAttribute('content');
        $this->collectExpandableDatas(self::URI . $this->getInput('username') . '/rss');
    }

    protected function parseItem(array $item)
    {
        // Move movie poster after text
        $item['content'] = preg_replace('/(<p><img src=".+"\/><\/p>) (<p>.+<\/p>)/', '$2$1', $item['content']);
        return $item;
    }

    public function getName()
    {
        return str_replace('Letterboxd - ', '', parent::getName());
    }

    public function getIcon()
    {
        return $this->feedIcon;
    }
}
