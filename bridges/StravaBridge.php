<?php
class StravaBridge extends BridgeAbstract {
    const NAME = "Strava Bridge";
    const DESCRIPTION = "Returns an athlete's recent activities";
    const URI = 'https://www.strava.com';
    const PARAMETERS = [
        [
            'athleteID' => [
                'name' => 'athleteID',
                'exampleValue' => '8976',
                'required' => true
            ]
        ],
    ];
    const CONFIGURATION = [
        'cookie_string' => [
            'required' => false,
        ],
    ];

    public function detectParameters($url) {
        if (preg_match('/strava\.com\/athletes\/([\d]+)/', $url, $matches) > 0) {
            return [
                'athleteID' => $matches[1]
            ];
        }
        return null;
    }

    public function collectData() {
        $athleteID = $this->getInput('athleteID');

        $descriptions = array();
        if ($this->getOption('cookie_string')) {
            $headers = ['Cookie: ' . $this->getOption('cookie_string')];
            $contents = getContents(self::URI . '/athletes/' . $athleteID, $headers);
            $dom = str_get_html($contents);
            $props = $dom->find('div[data-react-class=AthleteProfileHeaderMediaGrid]',0)->{'data-react-props'};
            $propData = json_decode(html_entity_decode($props));
            foreach ($propData->items as $item) {
                $descriptions[$item->activity->id] = $item->activity->description;
            }
        }

        $dom = getSimpleHTMLDOM(self::URI . '/athletes/' . $athleteID);
        $scriptRegex = "/data-react-props='(.*?)'/";
        preg_match($scriptRegex, $dom, $matches) or returnServerError('Could not find json');
        $jsonData = json_decode(html_entity_decode($matches[1]));
        $this->feedName = $jsonData->athlete->name . "'s Recent Activities";
        $this->iconURL = $jsonData->athlete->avatarUrl;
        foreach ($jsonData->recentActivities as $activity) {
            $item = array();

            $item['title'] = $activity->name . ' (' . $activity->detailedType . ')';
            $item['author'] = $jsonData->athlete->name;
            $item['uri'] = self::URI . '/activities/' . $activity->id;
            $item['timestamp'] = $activity->startDateLocal;

            $content = "<p>{$descriptions[$activity->id]}</p>" .
                       "<b>Distance:</b> " . $activity->distance .
                       "<br><b>Elev Gain:</b> " . $activity->elevation .
                       "<br><b>Time:</b> " . $activity->movingTime . "<br><br>";
            foreach ($activity->images as $image) {
                $src = $image->squareSrc;
                if (empty($src)) { $src = $image->defaultSrc; }
                $content .= '<img src="' . $src . '">';
            }
            $item['content'] = $content;

            // this link requires login
            $item['enclosures'][] = $item['uri'] . '/export_gpx';

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
