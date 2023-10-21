<?php

class StravaBridge extends BridgeAbstract
{
    const NAME = 'Strava Bridge';
    const DESCRIPTION = "Returns an athlete's recent activities";
    const URI = 'https://www.strava.com';
    const PARAMETERS = [
        [
            'athleteID' => [
                'name' => 'athleteID',
                'required' => true
            ]
        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/strava\.com\/athletes\/([\d]+)/', $url, $matches) > 0) {
            return [
                'athleteID' => $matches[1]
            ];
        }
        return null;
    }

    public function collectData()
    {
        $athleteID = $this->getInput('athleteID');

        $dom = getSimpleHTMLDOM(self::URI . '/athletes/' . $athleteID);
        $nextData = $dom->find("#__NEXT_DATA__", 0);
        $jsonData = json_decode(html_entity_decode($nextData->innertext));
        $jsonData = $jsonData->props->pageProps->athleteData;
        $this->feedName = $jsonData->athlete->name . "'s Recent Activities";
        $this->iconURL = $jsonData->athlete->avatarUrl;
        foreach ($jsonData->recentActivities as $activity) {
            $item = [];

            $item['title'] = $activity->name . ' (' . $activity->detailedType . ')';
            $item['author'] = $jsonData->athlete->name;
            $item['uri'] = self::URI . '/activities/' . $activity->id;
            $item['timestamp'] = $activity->startDateLocal;

            $content = '<b>Distance:</b> ' . $activity->distance .
                       '<br><b>Elev Gain:</b> ' . $activity->elevation .
                       '<br><b>Time:</b> ' . $activity->movingTime . '<br><br>';

            foreach ($activity->images as $image) {
                $src = $image->squareSrc;
                if (empty($src)) {
                    $src = $image->defaultSrc;
                }
                $content .= '<img src="' . $src . '">';
            }
            $item['content'] = $content;

            $item['enclosures'][] = $item['uri'] . '/export_gpx';

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (empty($this->feedName)) {
            return parent::getName();
        } else {
            return $this->feedName;
        }
    }

    public function getIcon()
    {
        if (empty($this->iconURL)) {
            return parent::getIcon();
        } else {
            return $this->iconURL;
        }
    }
}
