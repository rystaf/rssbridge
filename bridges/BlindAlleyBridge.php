<?php
class BlindAlleyBridge extends BridgeAbstract {
    const NAME = "Blind Alley";
    const DESCRIPTION = "Returns latest comic";
    const URI = 'https://www.blind-alley.com';

    public function collectData() {
        $pageData = json_decode(getContents(self::URI . "/page-data/archive/page-data.json"));
        $archiveID = end($pageData->staticQueryHashes);
        $archive = json_decode(getContents(self::URI . '/page-data/sq/d/' . $archiveID . '.json'));
        foreach ($archive->data->allPrismicComic->nodes as $node) {
            $title = $node->data->title->text;
            $publish_date = $node->data->publish_date;
            $dom = getSimpleHTMLDOM(self::URI . $node->url);
            $formated_date = $dom->find('span',0)->plaintext;
            $imgPath = $dom->find('main picture img', 0)->{'data-src'};
            $imgURL = self::URI . $imgPath;
            $iconPath = $dom->find('link[rel=apple-touch-icon]', 0)->{'href'};
            $this->iconURL = self::URI . $iconPath;

            $item = array();
            $item['author'] = "Adam de Souza";
            $item['title'] = "{$title}: {$formated_date}";
            $item['content'] = "<figure><img src=\"{$imgURL}\"></figure>";
            $item['uri'] = self::URI . $node-url;
            $item['timestamp'] = $publish_date;
            $this->items[] = $item;
            break;
        }
    }
    public function getIcon() {
        if (empty($this->iconURL)) {
            return parent::getIcon();
        } else {
            return $this->iconURL;
        }
    }
}
