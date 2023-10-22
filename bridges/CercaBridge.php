<?php

class CercaBridge extends FeedExpander
{
    const NAME = 'Cerca Bridge';
    const DESCRIPTION = "Returns latest posts";
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'feed',
                'required' => true
            ],
        ],
    ];

    public function collectData()
    {
        $this->fails = 0;
        $this->collectExpandableDatas($this->getInput('feed'), 5);
    }

    protected function parseItem(array $item)
    {
        $anchor = "#" . parse_url($item['uri'])["fragment"];
        $uri = str_replace($anchor, '', $item['uri']);
        if (preg_match('/\[[0-9\-]+\] (.+) posted/', $item["content"], $matches) > 0) {
            $item["author"] = $matches[1];
        }
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
            $content->find("section", 0)->remove();
            $item["content"] = $content->save();
        } catch (\Exception $e) {
            return;
        }
        return $item;
    }
    private function getResetCache($url, $force = false) {
        $ttl = 86400;
        $cache = RssBridge::getCache();
        $cacheKey = 'pages_' . $url;
        $content = $cache->get($cacheKey);
        if (!$content || $force) {
            sleep(15);
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
}
