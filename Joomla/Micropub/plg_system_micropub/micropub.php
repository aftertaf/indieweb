<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.micropub
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;

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

        if ($path === 'micropub/media') {
            $this->handleMedia();
            $this->app->close();
        }
    }

    /* ============================================================
     * MICROPUB MAIN ENDPOINT
     * ============================================================ */
    protected function handleMicropub()
    {
        $method = $this->app->getMethod();

        if ($method === 'GET') {
            return $this->respond(200, ['media-endpoint' => Uri::root() . 'micropub/media']);
        }

        if ($method !== 'POST') {
            return $this->respond(405, 'Micropub endpoint requires POST');
        }

        $token = $this->extractBearerToken();
        if (!$token) return $this->respond(401, 'Missing bearer token');

        $action = $this->detectAction();

        if ($action === 'create' && !$this->validateToken($token, 'create'))
            return $this->respond(403, 'Invalid or unauthorized token');

        if ($action === 'update' && !$this->validateToken($token, 'update'))
            return $this->respond(403, 'Invalid or unauthorized token');

        if ($action === 'delete' && !$this->validateToken($token, 'delete'))
            return $this->respond(403, 'Invalid or unauthorized token');

        if ($action === 'create') return $this->mpCreate();
        if ($action === 'update') return $this->mpUpdate();
        if ($action === 'delete') return $this->mpDelete();

        return $this->respond(400, 'Unknown Micropub action');
    }

    /* ============================================================
     * DETECT ACTION (create / update / delete)
     * ============================================================ */
    protected function detectAction(): string
    {
        $input = $this->app->input;

        if ($input->post->getString('action') === 'delete') return 'delete';
        if ($input->post->getString('action') === 'undelete') return 'create';

        if ($input->post->getString('url')) return 'update';

        return 'create';
    }

    /* ============================================================
     * PARSE JSON OR FORM-ENCODED MICROPUB INPUT
     * ============================================================ */
    protected function mpInput(): array
    {
        $raw = file_get_contents('php://input');
        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($ctype, 'application/json') !== false) {
            $json = json_decode($raw, true);
            return is_array($json) ? $json : [];
        }

        return $this->app->input->post->getArray();
    }

    /* ============================================================
     * CREATE
     * ============================================================ */
    protected function mpCreate()
    {
        $data = $this->mpInput();

        $content = $data['content'] ?? '';
        $name    = $data['name'] ?? '';

        $table = $this->app->bootComponent('com_content')
            ->getMVCFactory()
            ->createTable('Content');

        $record = [
            'title'     => $name ?: '',
            'introtext' => $content,
            'fulltext'  => '',
            'state'     => 1,
            'catid'     => 2,
            'created'   => gmdate('Y-m-d H:i:s'),
        ];

        $table->bind($record);
        $table->store();

        return $this->respond(201, ['url' => Uri::root() . 'index.php?option=com_content&view=article&id=' . $table->id]);
    }

    /* ============================================================
     * UPDATE
     * ============================================================ */
    protected function mpUpdate()
    {
        $data = $this->mpInput();
        $url  = $data['url'] ?? '';

        if (!$url) return $this->respond(400, 'Missing URL');

        $id = (int) parse_url($url, PHP_URL_QUERY);
        parse_str($id, $params);
        $id = $params['id'] ?? 0;

        if (!$id) return $this->respond(400, 'Invalid URL');

        $table = $this->app->bootComponent('com_content')
            ->getMVCFactory()
            ->createTable('Content');

        $table->load($id);

        if (isset($data['replace']['content'])) {
            $table->introtext = $data['replace']['content'];
        }

        if (isset($data['replace']['name'])) {
            $table->title = $data['replace']['name'];
        }

        $table->store();

        return $this->respond(200, 'Micropub update OK');
    }

    /* ============================================================
     * DELETE
     * ============================================================ */
    protected function mpDelete()
    {
        $data = $this->mpInput();
        $url  = $data['url'] ?? '';

        if (!$url) return $this->respond(400, 'Missing URL');

        $id = (int) parse_url($url, PHP_URL_QUERY);
        parse_str($id, $params);
        $id = $params['id'] ?? 0;

        if (!$id) return $this->respond(400, 'Invalid URL');

        $table = $this->app->bootComponent('com_content')
            ->getMVCFactory()
            ->createTable('Content');

        $table->load($id);
        $table->state = -2; // trashed
        $table->store();

        return $this->respond(200, 'Micropub delete OK');
    }

    /* ============================================================
     * MEDIA ENDPOINT
     * ============================================================ */
    protected function handleMedia()
    {
        if ($this->app->getMethod() !== 'POST')
            return $this->respond(405, 'Media endpoint requires POST');

        $token = $this->extractBearerToken();
        if (!$token) return $this->respond(401, 'Missing bearer token');
        if (!$this->validateToken($token, 'create'))
            return $this->respond(403, 'Invalid or unauthorized token');

        $file = $this->app->input->files->get('file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK)
            return $this->respond(400, 'Invalid upload');

        $dest = JPATH_ROOT . '/images/micropub/' . basename($file['name']);
        @mkdir(dirname($dest), 0777, true);
        move_uploaded_file($file['tmp_name'], $dest);

        return $this->respond(201, ['url' => Uri::root() . 'images/micropub/' . basename($file['name'])]);
    }

    /* ============================================================
     * TOKEN HANDLING
     * ============================================================ */
    protected function extractBearerToken(): ?string
    {
        $auth = $this->app->input->server->getString('HTTP_AUTHORIZATION', '');
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) return trim($m[1]);
        return null;
    }

    protected function validateToken(string $token, string $requiredScope): bool
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__micropub_tokens')
            ->where('token = ' . $db->quote($token));

        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row) return false;

        $scopes = array_map('trim', explode(' ', $row->scope));
        return in_array($requiredScope, $scopes);
    }

    /* ============================================================
     * RESPONSE
     * ============================================================ */
    protected function respond(int $code, $message)
    {
        $this->app->setHeader('Content-Type', 'application/json', true);
        $this->app->setHeader('Status', $code, true);
        $this->app->setBody(json_encode($message));
    }
}
