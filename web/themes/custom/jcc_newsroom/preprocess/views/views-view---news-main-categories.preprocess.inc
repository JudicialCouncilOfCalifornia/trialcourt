<?php

/**
 * @file
 * Preprocessors for views.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view__news_main_categories(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];

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
<div class="created">{$date}</div>
<div class="teaser-text">{$summary_markup}</div>
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

    $variables['tiles_list']['tiles'][] = [
      'card' => [
        'title' => $node->getTitle(),
        'excerpt' => render($excerpt),
        'url' => $node->toUrl(),
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
