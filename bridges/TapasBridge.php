<?php

class TapasBridge extends FeedExpander
{
    const NAME            = 'Tapas.io';
    const URI            = 'https://tapas.io/';
    const DESCRIPTION    = 'Return new chapters from standart Tapas RSS';
    const MAINTAINER    = 'Ololbu';
    const CACHE_TIMEOUT    = 3600;
    const PARAMETERS    = [
        [
            'title' => [
                'name' => 'URL\'s title / ID',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert title from URL (tapas.io/series/THIS_TITLE/info) or title ID',
            ],
            'extend_content' => [
                'name' => 'Include on-site content',
                'type' => 'checkbox',
                'title' => 'Activate to include images or chapter text',
            ],
            'max_entries' => [
                'name' => 'maximum entries',
                'type' => 'number',
                'title' => 'Maximum amount of entries',
                'exampleValue' => 5
            ],
//            'force_title' => [
//                'name' => 'Force title use',
//                'type' => 'checkbox',
//                'title' => 'If you have trouble with feed getting, try this option.',
//            ],
        ]
    ];

    protected $id;

    public function getURI()
    {
        if ($this->id) {
            return self::URI . 'rss/series/' . $this->id;
        } else {
            return self::URI . 'series/' . $this->getInput('title') . '/info/';
        }
        return self::URI;
    }

    public function getID($uri)
    {
        $html = getSimpleHTMLDOM($uri) or returnServerError('Could not request ' . $uri);
        $meta = $html->find('meta[property$=":url"]', 0)->content;
        if (preg_match('/tapastic:\/\/series\/([\d]+)\/.+/', $meta, $matches) > 0) {
            $this->id = $matches[1];
        }
    }

    public function detectParameters($url)
    {
        if (preg_match('/tapas\.io\/episode\/([\d]+)/', $url, $matches) > 0) {
            $this->getID($url);
            return [
                'title' => $this->id,
                'extend_content' => true,
                'max_entries' => 1
            ];
        }
        if (preg_match('/tapas\.io\/series\/([\w]+)\/info/', $url, $matches) > 0) {
            return [
                'title' => $matches[1],
                'extend_content' => true,
                'max_entries' => 1
            ];
        }

        return null;
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        $namespaces = $feedItem->getNamespaces(true);
        if (isset($namespaces['content'])) {
            $description = $feedItem->children($namespaces['content']);
            if (isset($description->encoded)) {
                $scriptRegex = "/<p>(.*?)<\/p>/";
                preg_match($scriptRegex, (string)$description->encoded, $matches);
                if ($matches[1] != 'null') {
                    $item['content'] = '<p>' . $matches[1] . '</p>';
                }
            }
        }

        if ($this->getInput('extend_content')) {
            $html = getSimpleHTMLDOMCached($item['uri']) or returnServerError('Could not request ' . $this->getURI());
            if (!$item['content']) {
                $item['content'] = '';
            }
            if ($html->find('article.main__body', 0)) {
                foreach ($html->find('article', 0)->find('img') as $line) {
                    $item['content'] .= '<img src="' . $line->{'data-src'} . '">';
                }
            } elseif ($html->find('article.main__body--book', 0)) {
                $item['content'] .= $html->find('article.viewer__body', 0)->innertext;
            } else {
                $item['content'] .= '<h1 style="font-size:24px;text-align:center;">Locked episode</h1>';
                $item['content'] .= '<h5 style="text-align:center;">' . $html->find('div.js-viewer-filter h5', 0)->plaintext . '</h5>';
            }
        }

        return $item;
    }

    public function collectData()
    {
        if (preg_match('/^[\d]+$/', $this->getInput('title'))) {
            $this->id = $this->getInput('title');
        }
        if ($this->getInput('force_title') or !$this->id) {
            $this->getID($this->getURI());
        }
        if ($this->getInput('max_entries')) {
            return $this->collectExpandableDatas($this->getURI(), $this->getInput('max_entries'));
        }
        $this->collectExpandableDatas($this->getURI());
    }
}
