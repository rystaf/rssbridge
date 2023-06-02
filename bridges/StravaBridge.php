<?php
class StravaBridge extends BridgeAbstract {
    const URI = 'https://www.strava.com';
    const PARAMETERS = [
        'By athlete ID' => [
            'athleteID' => [
                'name' => 'athleteID',
                'exampleValue' => '8976',
                'required' => true
            ]
        ],
    ];

    public function collectData() {
        $athleteID = $this->getInput('athleteID');
        $dom = getSimpleHTMLDOM(self::URI . '/athletes/' . $athleteID);
        $scriptRegex = "/data-react-props='(.*?)'/";
        preg_match($scriptRegex, $dom, $matches) or returnServerError('Could not find json');
        $jsonData = json_decode(html_entity_decode($matches[1]));

        $this->feedName = $jsonData->athlete->name;
        $this->iconURL = $jsonData->athlete->avatarUrl;
        foreach ($jsonData->recentActivities as $activity) {
            $item = array();
            $item['title'] = $activity->name . ' (' . $activity->detailedType . ')';
            $content = '<b>Distance:</b> ' . $activity->distance .
                '<br><b>Elev Gain:</b> ' . $activity->elevation .
                '<br><b>Time:</b> ' . $activity->movingTime . '<br><br>';
            foreach ($activity->images as $image) {
                $src = $image->squareSrc;
                if (empty($src)) { $src = $image->defaultSrc; }
                $content .= '<img src="' . $src . '">';
            }
            $item['content'] = $content;
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
