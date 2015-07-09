<?php

namespace rockunit\core\db\models;

use rock\components\Linkable;
use rock\helpers\Link;
use rock\url\Url;

class Users extends BaseUsers implements Linkable
{
    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::modify(["http://site.com/users/{$this->username}"], Url::ABS),
        ];
    }
}