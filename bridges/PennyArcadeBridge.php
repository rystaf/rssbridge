<?php

class PennyArcadeBridge extends FeedExpander
{
    const NAME           = 'Penny Arcade';
    const URI            = 'https://penny-arcade.com/';
    const DESCRIPTION    = 'Latest comics and news';

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . 'feed');
    }

    protected function parseItem(array $item)
    {
        $item['content'] = '';
        $html = getSimpleHTMLDOMCached($item['uri']);
        $item['author'] = ltrim($html->find('.details.author', 0)->plaintext, 'By ');
        if ($aside = $html->find('.post-body', 0)->find('aside', 0)) {
            $aside->remove();
        }
        foreach ($html->find('.post-body', 0)->find('.post-text') as $text) {
            foreach ($text->children() as $child) {
                $item['content'] .= $child->save();
            }
        }
        if ($replies = $html->find('.replies', 0)) {
            $item['content'] .= $replies->save();
        }
        $comic = $html->find('#comic-panels', 0);
        if ($comic) {
            $item['author'] = 'Penny Arcade';
            $item['content'] = $comic->save();
        }
        return $item;
    }

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }
}
