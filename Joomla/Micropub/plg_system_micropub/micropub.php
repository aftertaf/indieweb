<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.micropub
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class PlgSystemMicropub extends CMSPlugin
{
    protected $app;

    public function onAfterRoute()
    {
        if (!$this->app->isClient('site')) return;

        $path = trim(parse_url(Uri::current(), PHP_URL_PATH), '/');
        if ($path === 'micropub') {
            $this->handleMicropub();
            $this->app->close();
        }
    }

    protected function handleMicropub()
    {
        if ($this->app->getMethod() !== 'POST') {
            return $this->respond(405, 'Micropub endpoint requires POST');
        }

        $input = $this->app->input;

        $content = $input->post->getString('content', '');
        $name    = $input->post->getString('name', '');
        $likeOf  = $input->post->getString('like-of', '');
        $replyTo = $input->post->getString('in-reply-to', '');

        $data = [
            'title'      => $name ?: '',
            'introtext'  => $content,
            'fulltext'   => '',
            'state'      => 1,
            'catid'      => 2,
            'created'    => gmdate('Y-m-d H:i:s'),
        ];

        $table = JTable::getInstance('Content');
        $table->bind($data);
        $table->store();

        return $this->respond(201, 'Micropub post created');
    }

    protected function respond(int $code, string $message)
    {
        $this->app->setHeader('Content-Type', 'application/json', true);
        $this->app->setHeader('Status', $code . ' ' . $message, true);
        $this->app->setBody(json_encode(['message' => $message]));
    }
}