<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view__news(&$variables) {
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
      $source = '<span>' . $source . '</span>';
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
          $url = Url::fromUri($l['uri']);
          $l_markup[] = Link::fromTextAndUrl($l['title'], $url)->toString()->getGeneratedLink();
        }
        $links_markup = '<p class="font-code-xs text-italic">Related: ' . implode(', ', $l_markup) . '</p>';
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

    $tags_markup = _jcc_list_faceted_terms($node,
      ['field_topics', 'field_tags', 'field_division', 'field_subject_matter']);

    $excerpt['#markup'] = <<<TEMPLATE
<div class="source">{$source}</div>
<div class="created">{$date}</div>
<div class="teaser-text">{$summary_markup}</div>
{$links_markup}
<div class="jcc-tags">
  {$tags_markup}
</div>
TEMPLATE;

    // fix for new-109
    $promotional_image = _jcc_get_image_by_field($node, 'field_promotional_image', 'crop_16x9');
    $first_image = _jcc_get_first_image($node);
    $image_to_use = !empty($promotional_image['url']) ? $promotional_image : $first_image;

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
        'src' => $image_to_use['url'] ?? '',
        'alt' => $image_to_use['alt'] ?? '',
        'renderer' => 'patternlab',
      ],
    ];

    $excerpt = [];
  }
}
