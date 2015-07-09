<?php

namespace rockunit\db\models;

use rock\components\Linkable;
use rock\helpers\Link;
use rock\url\Url;
use rockunit\db\models\BaseUsers;

class Users extends BaseUsers implements Linkable
{
    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::modify(["http://site.com/users/{$this->username}"], Url::ABS),
        ];
    }
}