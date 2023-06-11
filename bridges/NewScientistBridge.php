<?php
class NewScientistBridge extends BridgeAbstract {
    const NAME = "New Scientist Author";
    const DESCRIPTION = "Returns an author's posts";
    const URI = 'https://www.newscientist.com';
    const PARAMETERS = [
        [
            'authorID' => [
                'name' => 'authorID',
                'required' => true
            ]
        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/newscientist\.com\/author\/(.+)/', $url, $matches) > 0) {
            return [
                'authorID' => $matches[1]
            ];
        }
        if (preg_match('/newscientist\.com\/article\/(.+)/', $url, $matches) > 0) {
            $dom = getSimpleHTMLDOM($url);
            $authorID = substr($dom->find('a.ArticleHeader__NameLink', 0)->{'href'}, 8);
            return [
                'authorID' => $authorID
            ];
        }
        return null;
    }


    public function collectData() {
        $authorID = $this->getInput('authorID');
        $dom = getSimpleHTMLDOM(self::URI . '/author/' . $authorID);
        $author = $dom->find('h1.Author__Name', 0)->plaintext;
        $this->iconURL = $dom->find('img.author__image', 0)->{'src'};
        $this->iconURL = preg_replace('/(.+)\?(.*)/','$1', $this->iconURL);
        $this->feedName = $author;
        foreach ($dom->find('section a.CardLink') as $card) {
            $title = $card->find('.Card__Title', 0)->plaintext;
            $title = preg_replace('/.*: (.*)/', '$1', $title);
            $imageURL = $card->find('img.image', 0)->{'data-src'};
            $imageURL = preg_replace('/(.+)\?width=[\d]+/','$1', $imageURL);
            $timestamp = preg_replace('/.*\/([\d]{4})\/([\d]{2})\/([\d]{2})([\d]{6})\/.*/', '$1-$2-$3 $4', $imageURL);
            $item = array();
            $item['title'] = $title;
            $item['author'] = $author;
            $item['uri'] = self::URI . $card->{'href'};
            $item['timestamp'] = $timestamp;
            $item['content'] = '<img src="'.$imageURL.'">';
            $this->items[] = $item;
        }

    }
    public function getName() {
        if (empty($this->feedName)) {
            return parent::getName();
        } else {
            return $this->feedName;
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
