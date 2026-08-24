<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.webmentionreceiver
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class PlgSystemWebmentionreceiver extends CMSPlugin
{
    protected $app;

    public function onAfterRoute()
    {
        if (!$this->app->isClient('site')) return;

        $path = trim(parse_url($this->app->input->server->getString('REQUEST_URI', ''), PHP_URL_PATH), '/');
        if ($path === 'webmention') {
            $this->handleWebmention();
            $this->app->close();
        }
    }

    protected function handleWebmention()
    {
        $input = $this->app->input;

        if ($this->app->getMethod() !== 'POST') {
            return $this->respond(405, 'Webmention endpoint requires POST');
        }

        $source = $input->post->getString('source', '');
        $target = $input->post->getString('target', '');

        if (!$source || !$target) {
            return $this->respond(400, 'Missing source or target');
        }

        $html = @file_get_contents($source);
        if (!$html) {
            return $this->respond(400, 'Unable to fetch source');
        }

        $type = $this->detectType($html, $target);

        $db = Factory::getDbo();
        $obj = (object) [
            'source'  => $source,
            'target'  => $target,
            'type'    => $type,
            'created' => gmdate('Y-m-d H:i:s'),
        ];
        $db->insertObject('#__webmention_received', $obj);

        return $this->respond(202, 'Webmention accepted');
    }

    protected function detectType(string $html, string $target): string
    {
        $type = 'mention';

        if (strpos($html, 'u-in-reply-to') !== false) {
            $type = 'reply';
        } elseif (strpos($html, 'u-like-of') !== false) {
            $type = 'like';
        } elseif (strpos($html, 'u-repost-of') !== false) {
            $type = 'repost';
        } elseif (strpos($html, 'u-follow-of') !== false) {
            $type = 'follow';
        }

        return $type;
    }

    protected function respond(int $code, string $message)
    {
        $this->app->setHeader('Content-Type', 'application/json', true);
        $this->app->setHeader('Status', $code, true);
        $this->app->setBody(json_encode(['message' => $message]));
    }
}
