<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view__news_newsroom_only_(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];

    $news_types = [];
    foreach ($node->field_news_type as $item) {
      if ($item->entity) {
        $news_types[$item->entity->id()] = $item->entity->label();
      }
    }

    $source = $node->field_feed_source->value;
    if ($source) {
      $source = '<span class="usa-tag usa-tag--big bg-base-lighter text-base-darker"><code>' . $source . '</code></span>';
    }

    $links = $node->field_links->getValue();
    $links_markup = '';
    if (!empty($links)) {
      if (in_array('NewsLink', $news_types)) {
        array_shift($links);
      }

      if (!empty($links)) {
        $l_markup = [];
        foreach ($links as $l) {
          $url = Url::fromUri($l['uri'], ['attributes' => ['class' => 'text-base']]);
          $l_markup[] = Link::fromTextAndUrl($l['title'], $url)->toString()->getGeneratedLink();
        }
        $links_markup = '<p class="font-code-xs text-italic text-base">Related: ' . implode(', ', $l_markup) . '</p>';
      }
    }

    $date = date("F j, Y", $node->getCreatedTime());

    $summary = $node->body->view(
      [
        'label' => 'hidden',
        'type' => 'text_summary_or_trimmed',
        'settings' => [
          'trim_length' => 600,
        ],
      ]
    );
    $summary_markup = render($summary);
    $summary = [];

    $tags_markup = '';
    foreach (['field_division', 'field_tags', 'field_subject_matter'] as $tag) {
      $display_options = EntityViewDisplay::collectRenderDisplay($node, 'teaser')
        ->getComponent($tag);
      $tag_render = [];
      $tag_render = $node->$tag->view($display_options);
      $tags_markup .= render($tag_render);
    }

    $excerpt['#markup'] = <<<TEMPLATE
<div class="source">{$source}</div>
<div class="created">{$date}</div>
<div class="teaser-text">{$summary_markup}</div>
{$links_markup}
<div class="jcc-tags">
  {$tags_markup}
</div>
TEMPLATE;

    $image_url = $image_alt = "";
    $node_images = $node->get('field_images')->first();
    if (!empty($node_images->entity) && $node_images->entity->hasField('field_media_image')) {
      $node_image = $node_images->entity->get('field_media_image');

      if ($node_image) {
        $image_url = file_create_url(
          $node_image->first()->entity->uri->value
        );
        $image_alt = $node_image->first()->alt;
      }
    }

    $node_link = $node->toUrl();
    if (in_array('NewsLink', $news_types)) {
      $node_link_first = $node->get('field_links')->first();
      $node_link = $node_link_first->getValue()['uri'];
    }

    $variables['tiles_list']['tiles'][] = [
      'card' => [
        'title' => $node->getTitle(),
        'excerpt' => render($excerpt),
        'url' => $node_link,
      ],
      'date' => '',
      'img' => [
        'src' => $image_url,
        'alt' => $image_alt,
        'renderer' => 'patternlab',
      ],
    ];

    $excerpt = [];
  }
}
