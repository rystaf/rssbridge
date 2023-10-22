<?php

class FarsideNitterBridge extends FeedExpander
{
    const NAME = 'Farside Nitter Bridge';
    const DESCRIPTION = "Returns an user's recent tweets";
    const URI = 'https://farside.link/nitter/';
    const HOST = 'https://twitter.com/';
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'required' => true
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
            $item['uri'] = self::HOST . $this->getInput('username') . $matches[1];
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
        return self::HOST . $this->getInput('username');
    }
}
