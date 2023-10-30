<?php

class GithubUserBridge extends FeedExpander
{
    const NAME           = 'Github User Timeline';
    const URI            = 'https://github.com/';
    const DESCRIPTION    = 'The public timeline for any user';
    const PARAMETERS = [[
        'username' => [
            'name' => 'username',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'awesomekling'
        ]
    ]];

    private $feedIcon;
    private $feedName;

    public function collectData()
    {

        $json = $this->api('/users/'.$this->getInput('username'));
        $this->feedName = $json->name;
        $this->collectExpandableDatas(self::URI . $this->getInput('username') . '.atom');
    }

    protected function parseItem(array $item)
    {
        $content = str_get_html($item['content']);
        if (!$this->feedIcon) {
            $this->feedIcon = $content->find('.avatar-user', 0)->src;
        }
        $item['content'] = $content->find('.Box', 0)->save();
        $item['title'] = trim(ltrim($item['title'], $this->getInput('username')));
        if (preg_match('/github.com\/(.+)\/(issues|pull)\/(\d+)#issuecomment-(\d+)/', $item['uri'], $matches)) {
            try {
                $json = $this->api('/repos/' . $matches[1] . '/issues/comments/' . $matches[4]);
            } catch (\Exception $e) {
                return;
            }
            $item['content'] = markdownToHtml($json->body);
        } else if (preg_match('/github.com\/(.+)\/pull\/(\d+)#discussion_r(\d+)/', $item['uri'], $matches)) {
            try {
                $json = $this->api('/repos/' . $matches[1] . '/pulls/comments/' . $matches[3]);
            } catch (\Exception $e) {
                return;
            }
            $item['content'] = '<details><summary>'.$json->path.'</summary><pre>'.$json->diff_hunk.'</pre></details>'.markdownToHtml($json->body);
        } else if (preg_match('/github.com\/(.+)\/compare\/(.+\.{3}.+)/', $item['uri'], $matches)) {
            try {
                $json = $this->api('/repos/' . $matches[1] . '/compare/' . $matches[2]);
            } catch (\Exception $e) {
                return;
            }
            if (!isset($json->commits)){
                return;
            }
            $item['content'] = '<h3>Commits</h3>';
            foreach ($json->commits as $commit) {
                $item['content'] .= '<img height="16" width="16" src="'.$commit->author->avatar_url.'&s=20"><strong>'.$commit->author->login.'</strong>: '.$commit->commit->message.'<br>';
            }
            $item['content'] .= '<h3>Files changed</h3>';
            foreach ($json->files as $file) {
                $patch = nl2br(htmlentities(substr(($file->patch ?? ''), 0, 1000)));
                $item['content'] .= '<details><summary><a href="'.$file->blob_url.'">'.$file->filename.'</a> (+'.$file->additions.' -'.$file->deletions.')</summary><pre>'.$patch.'</pre></details>';
            }
        } else if (preg_match('/github.com\/(.+)\/pull\/(\d+)/', $item['uri'], $matches)) {
            try {
                $json = $this->api('/repos/' . $matches[1] . '/pulls/' . $matches[2]);
            } catch (\Exception $e) {
                return;
            }
            $item['content'] = '<h1><img height="20" width="20" src="'.$json->user->avatar_url.'&s=20"> '.$json->user->login.': '.$json->title . '</h1>'. markdownToHtml($json->body ?? '');
        } else if (preg_match('/github.com\/(.+)\/issues\/(\d+)/', $item['uri'], $matches)) {
            try {
                $json = $this->api('/repos/' . $matches[1] . '/issues/' . $matches[2]);
            } catch (\Exception $e) {
                return;
            }
            $item['content'] = '<h1><img height="20" width="20" src="'.$json->user->avatar_url.'&s=20"> '.$json->user->login.': '.$json->title . '</h1>'. markdownToHtml($json->body ?? '');
        } else if (preg_match('/^(starred|forked)/', $item['title'])) {
            $item['content'] = '';
            $html = $this->getResetCache($item['uri']);

            $item['content'] .= $html->find('article', 0)->save();
        } else {
        }
        $html = str_get_html($item['content']);
        foreach($html->find('img') as $img) {
            if (preg_match("/^https?:\/\//i", $img->src) === 0)   {
                $img->src = 'https://github.com'.$img->src;
            }
        }
        $item['content'] = $html->save();
        return $item;
    }

    private function api($uri, $force=false) {
        $headers = [
            "Accept: application/vnd.github+json",
            "X-GitHub-Api-Version: 2022-11-28",
        ];
        if ($token = $this->getOption('api_token')){
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $json = $this->getResetCache('https://api.github.com' . $uri, $headers, $force);
        return json_decode($json);
    }
    
    public function getName()
    {
        return $this->feedName;
    }

    public function getIcon()
    {
        return $this->feedIcon;
    }

    private function getResetCache($url, $headers = [], $force = false) {
        $ttl = 7200;
        $cache = RssBridge::getCache();
        $cacheKey = 'pages_' . $url;
        $content = $cache->get($cacheKey);
        if (!$content || $force) {
            $content = getContents($url, $headers, $opts ?? []);
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
