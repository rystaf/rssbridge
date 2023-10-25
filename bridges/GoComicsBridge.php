<?php

class GoComicsBridge extends BridgeAbstract
{
    const MAINTAINER = 'sky';
    const NAME = 'GoComics Unofficial RSS';
    const URI = 'https://www.gocomics.com';
    const DESCRIPTION = 'The Unofficial GoComics RSS';
    const MAX_ITEMS = 5;
    const PARAMETERS = [ [
        'comicname' => [
            'name' => 'comic name',
            'type' => 'text',
            'exampleValue' => 'heartofthecity',
            'required' => true
        ],
        'yearoffset' => [
            'name' => 'year offset',
            'type' => 'number',
            'exampleValue' => 10,
            'requred' => false,
            'title' => 'number of years to offset the comics by',
        ],
    ]];

    public function detectParameters($url)
    {
        if (preg_match('/gocomics\.com\/([a-z0-9\-]+)/', $url, $matches) && count($matches) > 1) {
            return [
                'comicname' => $matches[1]
            ];
        }
        return null;
    }

    private function getDates($time) {
        $calendarurl = self::URI . '/calendar/' . $this->getInput('comicname') . date("/Y/m", $time);
        $html = getSimpleHTMLDOM($calendarurl);
        return array_reverse(json_decode($html));
    }

    private $feedName;
    private $feedIconUrl;

    public function collectData()
    {
        $now = time();
        $html = getSimpleHTMLDOM($this->getURI());
        $link = self::URI . $html->find('.gc-deck--cta-0', 0)->find('a', 0)->href;
        $this->feedName = $html->find('h4.media-heading', 0)->plaintext;
        $this->feedIconUrl = $html->find('.gc-avatar img', 0)->src;
        $author = preg_replace('/By /', '', $html->find('.media-subheading', 0)->plaintext);
        if (!is_null($this->getInput('yearoffset'))){
            $offsetdate = strtotime("-".$this->getInput("yearoffset")." year", $now);
            $dates = $this->getDates($offsetdate);
            // fetch previous months comic dates if necessary
            if ((int)date('j', $offsetdate) < self::MAX_ITEMS){
                $dates = array_merge($dates, $this->getDates((strtotime("-1 months", $offsetdate))));
            }
            $todayscomic = array_search(date('Y/m/d', $offsetdate), $dates);
            // account for months with extra days
            if (date('m',strtotime("+1 day", $now)) != date('m', $now) && $todayscomic < 2){
                $todayscomic = 0;
            }
            $dates = array_slice($dates, $todayscomic);
        }
        for ($i = 0; $i < self::MAX_ITEMS; $i++) {
            $item = [];
            if (preg_match('/\d{4}\/\d{2}\/\d{2}/', $link, $match)){
                $date = new DateTime($match[0]);
                $item['title'] = $date->format('l, F j, Y');
                $item["timestamp"] = $date->getTimestamp();
            }
            if (!is_null($this->getInput('yearoffset'))) {
                if (!$dates || !array_key_exists($i, $dates)) {
                    continue;
                }
                $link = preg_replace('/\d{4}\/\d{2}\/\d{2}/', $dates[$i], $link);
                $date = new DateTime($dates[$i]);
                $item['title'] = $date->format('l, F j, Y');
                $item["timestamp"] = $date->modify('+'.$this->getInput('yearoffset').' years')->getTimestamp();
                if (date("z", $item["timestamp"]) > date("z", $now)) {
                    continue;
                }
            }
            $page = getSimpleHTMLDOM($link);
            $imagelink = $page->find('.comic.container', 0)->getAttribute('data-image');
            $item['id'] = $imagelink;
            $item['uri'] = $link;
            $item['author'] = $author;
            $item['content'] = '<img src="' . $imagelink . '" />';

            $link = self::URI . $page->find('.js-previous-comic', 0)->href;
            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('comicname'))) {
            return self::URI . '/' . urlencode($this->getInput('comicname'));
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->feedName) {
            return $this->feedName;
        }
        if (!is_null($this->getInput('comicname'))) {
            return $this->getInput('comicname') . ' - GoComics';
        }

        return parent::getName();
    }
    public function getIcon()
    {
        return $this->feedIconUrl;
    }
}
