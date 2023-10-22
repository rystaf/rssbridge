<?php

class FarsideNitterBridge extends FeedExpander
{
    const NAME = 'Farside Nitter Bridge';
    const DESCRIPTION = "Returns an user's recent tweets";
    const URI = 'https://farside.link/nitter/';
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'required' => true
            ],
            'linkBackToTwitter' => [
                'name' => 'Link back to twitter',
                'type' => 'checkbox',
                'required' => false
            ],
            'noreply' => [
                'name' => 'Without replies',
                'type' => 'checkbox',
                'title' => 'Only return initial tweets'
            ],
            'noretweet' => [
                'name' => 'Without retweets',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Hide retweets'
            ],
        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/^(https?:\/\/)?(www\.)?(nitter\.net|twitter\.com)\/([^\/?\n]+)/', $url, $matches) > 0) {
            return [
                'username' => $matches[4],
                'linkBackToTwitter' => true,
                'noreply' => true,
                'noretweet' => true
            ];
        }
        return null;
    }

    public function collectData()
    {
        $this->getRSS();
    }

    public function getRSS($attempt = 0) {
        try {
            $this->collectExpandableDatas(self::URI . $this->getInput('username') . '/rss');
        } catch (\Exception $e) {
            if ($attempt > 2) {
                throw $e;
            } else {
                $this->getRSS($attempt++);
            }
        }
    }

    protected function parseItem(array $item)
    {
        if ($this->getInput('noreply') && substr($item['title'], 0, 5) == "R to "){
            return;
        }
        if ($this->getInput('noretweet') && substr($item['title'], 0, 6) == "RT by "){
            return;
        }
        $item['title'] = truncate($item['title']);
        if (preg_match('/(\/status\/.+)/', $item['uri'], $matches) > 0) {
            if ($this->getInput('linkBackToTwitter')) {
                $item['uri'] = 'https://twitter.com/' . $this->getInput('username') . $matches[1];
            } else {
                $item['uri'] = self::URI . $this->getInput('username') . $matches[1];
            }
        }
        return $item;
    }

    public function getName()
    {
        if (preg_match('/(.+) \//', parent::getName(), $matches) > 0) {
            return $matches[1];
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->getInput('linkBackToTwitter')) {
            return 'https://twitter.com/' . $this->getInput('username');
        }
        return self::URI . $this->getInput('username');
    }
}
