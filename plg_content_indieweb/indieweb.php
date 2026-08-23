<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.indieweb
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class PlgContentIndieweb extends CMSPlugin
{
    protected $app;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = Factory::getApplication();
    }

    /**
     * Open h-entry before article display
     */
    public function onContentBeforeDisplay($context, &$article, &$params, $limit = 0)
    {
        // Only Main article body
        if ($limit !== 0) {
        return '';
        }
        
        // Only frontend
        if (!$this->app->isClient('site')) {
            return '';
        }

        // Only com_content articles
        if ($context !== 'com_content.article') {
            return '';
        }

        // Load custom fields
        $fields = FieldsHelper::getFields('com_content.article', $article, true) ?? [];
        $fieldValues = [];

        foreach ($fields as $field) {
            if (!empty($field->name)) {
                $fieldValues[$field->name] = $field->value ?? '';
            }
        }

        // Safe field reads
        $postType       = $fieldValues['post_type'] ?? 'article';
        $inReplyTo      = $fieldValues['in_reply_to'] ?? '';
        $likeOf         = $fieldValues['like_of'] ?? '';
        $repostOf       = $fieldValues['repost_of'] ?? '';
        $syndicationRaw = $fieldValues['syndication'] ?? '';
        $photoRaw       = $fieldValues['photo'] ?? '';
        $mfCategoryRaw  = $fieldValues['mf_category'] ?? '';
        $hcardName      = $fieldValues['hcard_name'] ?? '';
        $hcardUrl       = $fieldValues['hcard_url'] ?? '';
        $hcardPhoto     = $fieldValues['hcard_photo'] ?? '';

        // Parse lists
        $syndication  = array_filter(array_map('trim', explode(',', $syndicationRaw)));
        $photos       = array_filter(array_map('trim', explode(',', $photoRaw)));
        $mfCategories = array_filter(array_map('trim', explode(',', $mfCategoryRaw)));

        // Article values
        $title = htmlspecialchars($article->title ?? '', ENT_QUOTES, 'UTF-8');
        $url   = Uri::current();

        $published = !empty($article->publish_up)
            ? HTMLHelper::_('date', $article->publish_up, 'c')
            : '';

        $updated = !empty($article->modified)
            ? HTMLHelper::_('date', $article->modified, 'c')
            : '';

        // Author / h-card
        $authorUser  = Factory::getUser($article->created_by ?: 0);
        $authorName  = $hcardName ?: htmlspecialchars($authorUser->name ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $authorUrl   = $hcardUrl ?: Uri::base();
        $authorPhoto = $hcardPhoto ?: '';

        // Build h-card
        $hcard = '<span class="p-author h-card">'
               . '<span class="p-name">' . $authorName . '</span>'
               . '<a class="u-url" href="' . htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') . '"></a>';

        if ($authorPhoto) {
            $hcard .= '<img class="u-photo" src="' . htmlspecialchars($authorPhoto, ENT_QUOTES, 'UTF-8') . '" alt="' . $authorName . '">';
        }

        $hcard .= '</span>';

        // Store data on article for later use
        $article->indieweb = [
            'postType'      => $postType,
            'inReplyTo'     => $inReplyTo,
            'likeOf'        => $likeOf,
            'repostOf'      => $repostOf,
            'syndication'   => $syndication,
            'photos'        => $photos,
            'mfCategories'  => $mfCategories,
            'title'         => $title,
            'url'           => $url,
            'published'     => $published,
            'updated'       => $updated,
            'hcard'         => $hcard,
        ];

        // Open h-entry and header block
        $output  = '<article class="h-entry">';

        // Title (hidden for notes)
        if ($postType === 'note') {
            $output .= '<h1 class="p-name" style="display:none;">' . $title . '</h1>';
        } else {
            $output .= '<h1 class="p-name">' . $title . '</h1>';
        }

        // Canonical URL
        $output .= '<a class="u-url" href="' . $url . '"></a>';

        // Published / updated
        if ($published) {
            $output .= '<time class="dt-published" datetime="' . $published . '"></time>';
        }

        if ($updated && $updated !== $published) {
            $output .= '<time class="dt-updated" datetime="' . $updated . '"></time>';
        }

        // h-card
        $output .= $hcard;

        // Post-type specific microformats
        if ($postType === 'reply' && $inReplyTo) {
            $output .= '<a class="u-in-reply-to" href="' . htmlspecialchars($inReplyTo, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        if ($postType === 'like' && $likeOf) {
            $output .= '<a class="u-like-of" href="' . htmlspecialchars($likeOf, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        if ($postType === 'repost' && $repostOf) {
            $output .= '<a class="u-repost-of" href="' . htmlspecialchars($repostOf, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        // Photos
        foreach ($photos as $photoUrl) {
            $output .= '<img class="u-photo" src="' . htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') . '" alt="">';
        }

        // Categories
        foreach ($mfCategories as $cat) {
            $output .= '<a class="u-category" href="#" rel="tag">' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        // Syndication links
        foreach ($syndication as $synUrl) {
            $output .= '<a class="u-syndication" href="' . htmlspecialchars($synUrl, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        // Open e-content wrapper; Astroid will render article body inside
        $output .= '<div class="e-content">';

        return $output;
    }

    /**
     * Close h-entry after article display
     */
    public function onContentAfterDisplay($context, &$article, &$params, $limit = 0)
    {
        // Only Main article body
        if ($limit !== 0) {
        return '';
        }
        

        // Only frontend
        if (!$this->app->isClient('site')) {
            return '';
        }

        // Only com_content articles
        if ($context !== 'com_content.article') {
            return '';
        }

        // Close e-content and h-entry, add Bridgy Fed trigger
        $output  = '</div>'; // close .e-content
        $output .= '<a class="u-bridgy-fed" href="https://fed.brid.gy"></a>';
        $output .= '</article>';

        return $output;
    }

    /**
     * ActivityPub discovery link
     */
    public function onBeforeCompileHead()
    {
        if (!$this->app->isClient('site')) {
            return;
        }

        $doc = $this->app->getDocument();
        $url = Uri::current();

        $doc->addHeadLink(
            'https://fed.brid.gy/r/' . $url,
            'alternate',
            'rel',
            ['type' => 'application/activity+json']
        );
    }
}
