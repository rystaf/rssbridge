<?php

class FarsideNitterBridge extends FeedExpander
{
    const NAME = 'Farside Nitter Bridge';
    const DESCRIPTION = "Returns an user's recent tweets";
    const URI = 'https://farside.link/nitter/';
    const CACHE_TIMEOUT = 0;
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'required' => true
            ]
        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/^(https?:\/\/)?(www\.)?(nitter\.net|twitter\.com)\/([^\/?\n]+)/', $url, $matches) > 0) {
            return [
                'username' => $matches[4]
            ];
        }
        return null;
    }

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . $this->getInput('username') . '/rss');
    }

    protected function parseItem(array $item)
    {
        if (preg_match('/(\/status\/.+)/', $item['uri'], $matches) > 0) {
            $item['uri'] = self::URI . $this->getInput('username') . $matches[1];
        }
        return $item;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('username');
    }
}
