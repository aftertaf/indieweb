<?php

namespace Joomla\Component\Webmentions\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

class MentionsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDbo();
        return $db->getQuery(true)
            ->select('*')
            ->from('#__webmention_received')
            ->order('created DESC');
    }
}
