<?php

namespace Joomla\Component\Webmentions\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

class QueueModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDbo();
        return $db->getQuery(true)
            ->select('*')
            ->from('#__webmention_queue')
            ->order('created DESC');
    }
}
