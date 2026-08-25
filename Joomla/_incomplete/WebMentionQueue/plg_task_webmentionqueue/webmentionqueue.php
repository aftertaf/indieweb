<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Task.webmentionqueue
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Uri\Uri;

class PlgTaskWebmentionqueue extends CMSPlugin
{
    public function onExecuteTask($taskId, $params, $schedule)
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__webmention_queue')
            ->where('status = ' . $db->quote('pending'))
            ->order('created ASC')
            ->setLimit(20);

        $db->setQuery($query);
        $items = $db->loadObjectList();

        if (!$items) {
            return true;
        }

        $http = (new HttpFactory)->getHttp();

        foreach ($items as $item) {
            $endpoint = $this->discoverEndpoint($item->target);
            if (!$endpoint) {
                $this->markFailed($item->id, 'No webmention endpoint');
                continue;
            }

            try {
                $response = $http->post($endpoint, [
                    'source' => $item->source,
                    'target' => $item->target,
                ]);

                $this->markDone($item->id, (string) $response->getBody());
            } catch (\Exception $e) {
                $this->markFailed($item->id, $e->getMessage());
            }
        }

        return true;
    }

    protected function discoverEndpoint(string $url): ?string
    {
        $http = (new HttpFactory)->getHttp();

        try {
            $response = $http->get($url);
            $html = (string) $response->getBody();
        } catch (\Exception $e) {
            return null;
        }

        if (preg_match('#<link[^>]+rel=["\']webmention["\'][^>]+href=["\']([^"\']+)["\']#i', $html, $m)) {
            return Uri::resolve($url, $m[1]);
        }

        if (preg_match('#<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']webmention["\']#i', $html, $m)) {
            return Uri::resolve($url, $m[1]);
        }

        return null;
    }

    protected function markDone(int $id, string $response)
    {
        $db = Factory::getDbo();
        $obj = (object) [
            'id'           => $id,
            'status'       => 'done',
            'last_attempt' => gmdate('Y-m-d H:i:s'),
            'response'     => $response,
        ];
        $db->updateObject('#__webmention_queue', $obj, 'id');
    }

    protected function markFailed(int $id, string $response)
    {
        $db = Factory::getDbo();
        $obj = (object) [
            'id'           => $id,
            'status'       => 'failed',
            'last_attempt' => gmdate('Y-m-d H:i:s'),
            'response'     => $response,
        ];
        $db->updateObject('#__webmention_queue', $obj, 'id');
    }
}
