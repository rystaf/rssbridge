<?php

class CercaBridge extends FeedExpander
{
    const NAME = 'Cerca Bridge';
    const DESCRIPTION = "Returns latest posts";
    const TIMEFRAME = 48;
    const MAX_POSTS = 10;
    const FETCH_DELAY = 5;
    private $now = false;
    private $fails = 0;
    private $posts = Array();
    const PARAMETERS = [
        [
            'domain' => [
                'name' => 'domain',
                'required' => true
            ],
        ],
    ];

    public function collectData()
    {
        $this->collectExpandableDatas("https://" . $this->getInput('domain') . "/rss.xml", 10);
        ksort($this->posts);
        $posts = array_reverse(array_values($this->posts));
        $this->items = $posts;
    }

    protected function parseItem(array $item)
    {
        $id = parse_url($item['uri'])["fragment"];
        if (!$this->now) {
            $this->now = $item['timestamp'];
        } else {
            if (((($this->now - $item['timestamp']) / 3600) > self::TIMEFRAME) && count($this->posts) >= self::MAX_POSTS) {
                return;
            }
        }
        $anchor = "#" . $id;
        $uri = str_replace($anchor, '', $item['uri']);
        try {
            $html = $this->getResetCache($uri);
            $content = $html->find($anchor, 0);
            if (!$content && ($this->fails++ < 2)) {
                $html = $this->getResetCache($uri, true);
                $content = $html->find($anchor, 0);
            }
            if (!$content) {
                return;
            }
            foreach (array_reverse($html->find('article')) as $article) {
                $timestamp = strtotime($article->find('time', 0)->datetime);
                if (((($this->now - $timestamp) / 3600) > self::TIMEFRAME) && count($this->posts) >= self::MAX_POSTS){
                    continue;
                }
                if (date('d', $timestamp) == date('d', $item["timestamp"])) {
                    $timestamp = $item["timestamp"];
                }
                $author = $article->find('b', 0)->innertext;
                $body = str_get_html($article->save());
                $body->find('article',0)->first_child()->remove();
                $this->posts[(int)$article->id] = [
                    "uri" => str_replace($anchor, '#'.$article->id, $item['uri']),
                    "author" => $author,
                    "title" => $item["title"],
                    "timestamp" => $timestamp,
                    "content" => $body->save(),
                ];
            }
        } catch (\Exception $e) {
            return;
        }
        return;
    }
    private function getResetCache($url, $force = false) {
        $ttl = 86400;
        $cache = RssBridge::getCache();
        $cacheKey = 'pages_' . $url;
        $content = $cache->get($cacheKey);
        if (!$content || $force) {
            sleep(self::FETCH_DELAY);
            $content = getContents($url, $header ?? [], $opts ?? []);
        }
        $cache->set($cacheKey, $content, $ttl);
        return str_get_html(
            $content,
            true,
            true,
            DEFAULT_TARGET_CHARSET,
            true,
            DEFAULT_BR_TEXT,
            DEFAULT_SPAN_TEXT 
        );
    }
    public function getIcon()
    {
        return "https://" . $this->getInput('domain') . "/assets/favicon.png";
    }
}
