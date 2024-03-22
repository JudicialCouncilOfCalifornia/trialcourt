<?php

namespace Drupal\jcc_elevated_roc\Service;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

/**
 * Build a rule object.
 */
class JccElevatedRocRuleService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * JccElevatedRocRuleService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file and stream wrapper helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              StateInterface $state,
                              TranslationInterface $string_translation,
                              ModuleHandlerInterface $module_handler,
                              FileSystemInterface $file_system,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->setStringTranslation($string_translation);
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getRocDefiningDocumentCategoryId(): int {
    return $this->state->get('jcc_elevated_roc.roc_defining_category', 45);
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getRocDefiningDocumentBundleType(): string {
    return 'document';
  }

  /**
   * Returns the base path to the ROC section.
   */
  public function getRocBasePath(): string {
    return '/cms/rules/index';
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

  /**
   * Returns the possible Document id by analyzing the first part of a string.
   */
  public function getDocumentIdFromProcessedContent($text): string {
    /*
     * Make document_id into a URL friendly string. Break at first period/space
     * combo. Trim first item to 11 characters. This helps with any items above
     * that break weirdly. Replace spaces and periods with underscores. Replace
     * the first _ with a space. Return as all lowercase.
     */
    $linkid = explode('. ', $text, 2);
    $linkid = substr($linkid[0], 0, 11);
    $linkid = str_replace([" ", "."], "_", $linkid);
    $linkid = preg_replace('/_/', '', $linkid, 1);
    return strtolower($linkid);
  }

  /**
   * Returns the possible Document section id (rule_id).
   */
  public function getDocumentSectionIdFromProcessedContent($text): string {
    $rule_id = explode('. ', $text, 2);
    return $rule_id[0];
  }

  /**
   * Returns the possible Document section id (rule_id).
   */
  public function getLevelFromStyleName($style_name): string {
    switch ($style_name) {
      case 'Title':
      case 'Heading 1':
      case 'Heading 1*':
      case 'Title,h1':
      case 'Heading 1,| Title,h1':
      case 'Heading 1,| Title,h1,(1)':
      case 'Heading 1,| Title,h1,(a)':
        return '1';

      case 'Subtitle':
      case 'Heading 2':
      case 'Heading 2*':
      case 'Division,h2':
      case 'Division,h2,(a)':
      case 'Heading 2,| Division,h2':
      case 'Heading 2,| Division,h2,(1)':
      case 'Heading 2,| Division,h2,(a)':
        return '2';

      case 'Table of Content':
      case 'TOC':
      case 'toc':
        return 'toc';

      case 'Heading 3':
      case 'Heading 3*':
      case 'Rule,h3':
      case 'Rule,h3,(A)':
      case 'Heading 3,| Rule,h3':
      case 'Heading 3,| Rule,h3,(1)':
      case 'Heading 3,| Rule,h3,(A)':
        return '3';

      case 'Heading 4':
      case 'Heading 4,| Subdivision':
        return '4';

      case 'Heading 5':
      case 'Heading 5,| Subdivision Text':
        return '5';

      case 'Heading 6':
      case 'Heading 6,| Paragraph or List,| Paragraph':
        return '6';

      default:
        return '';
    }
  }

  /**
   * Returns the possible Document section id (rule_id).
   */
  public function getStyleClassFromStyleName($style_name): string {
    switch ($style_name) {
      case 'Title':
      case 'Heading 1':
      case 'Heading 1*':
      case 'Title,h1':
      case 'Heading 1,| Title,h1':
      case 'Heading 1,| Title,h1,(1)':
      case 'Heading 1,| Title,h1,(a)':
        return 'heading';

      case 'Subtitle':
      case 'Heading 2':
      case 'Heading 2*':
      case 'Division,h2':
      case 'Division,h2,(a)':
      case 'Heading 2,| Division,h2':
      case 'Heading 2,| Division,h2,(1)':
      case 'Heading 2,| Division,h2,(a)':
        return 'subheading';

      case 'Heading 3':
      case 'Heading 3*':
      case 'Rule,h3':
      case 'Rule,h3,(A)':
      case 'Heading 3,| Rule,h3':
      case 'Heading 3,| Rule,h3,(1)':
      case 'Heading 3,| Rule,h3,(A)':
        return 'ruleheading';

      case 'Heading 4':
      case 'Heading 4,| Subdivision':
        return 'subdivheading';

      case 'Heading 5':
      case 'Heading 5,| Subdivision Text':
        return 'subdivtext';

      case 'Heading 6':
      case 'Heading 6,| Paragraph,| Paragraph or List':
      case 'Heading 6,| Paragraph,| Paragraph or List,| Paragraph List':
        return 'paragraphlist';

      case 'Heading 7':
      case 'Heading 7,| Subparagraph':
        return 'subparagraphlist';

      case 'Subd History':
        return 'subdhist';

      case 'Rule History':
        return 'rulehist';

      case 'AdvComm Heading':
        return 'advcommheader';

      case 'AdvComm Text':
        return 'advcommtext';

      case '[Numbers] + Left: 0 pt First line: 0 pt':
      case '[Numbers] + Left:  0 pt First line:  0 pt':
        return 'numberedlist';

      case 'Heading 8':
      case 'Heading 8,| Item':
        return 'NEED_A_CLASS_TO_SET_TO_HEADING_8';

      case 'Heading 9':
        return 'NEED_A_CLASS_TO_SET_TO_HEADING_9';

      case 'Normal.0':
      default:
        return 'normal';
    }
  }

  // /**
  //   * Returns the possible Document section id (rule_id).
  //   */
  //  public function processRuleDocumentContent($doc_elements): array {
  //    $pre_content = [];
  //    $content = [];
  //    $toc = [];
  //
  //    foreach ($doc_elements as $index => $element) {
  //      if ($element instanceof TextRun) {
  //        // Start building our TOC and text content.
  //        $style_name = $element->getParagraphStyle()->getStyleName();
  //        $level = $this->getLevelFromStyleName($style_name);
  //        $style = $this->getStyleClassFromStyleName($style_name);
  //
  //        if (in_array($level, ['1', '2', 'toc'])) {
  //          // Gather sub-elements together into 1 element of text.
  //          $sub_elements = $element->getElements();
  //          $toc[$index]['text'] = '';
  //          foreach ($sub_elements as $sub_element) {
  //            if ($sub_element instanceof Text) {
  //              $text = $sub_element->getText();
  //              $toc[$index]['text'] .= $text;
  //              $toc[$index]['level'] = $level;
  //              $toc[$index]['style'] = $style;
  //            }
  //          }
  //
  //          // Clean up our text.
  //          $toc[$index]['text'] = trim($toc[$index]['text']);
  //          // Get the linkid from processing the text, only for first and second
  //          // level headings and table of content items.
  //          $toc[$index]['linkid'] = $this->getDocumentIdFromProcessedContent($toc[$index]['text']);
  //          // Remove potential empty TOC items.
  //          if (empty($toc[$index]['text'])) {
  //            unset($toc[$index]);
  //          }
  //        }
  //
  //        // Build out our content arrays.
  //        if (!in_array($level, ['1', '2', 'toc'])) {
  //
  //          // Gather sub-elements together into 1 element of text.
  //          $sub_elements = $element->getElements();
  //          $pre_content[$index]['text'] = '';
  //          foreach ($sub_elements as $sub_element) {
  //            if ($sub_element instanceof Text) {
  //              $text = $sub_element->getText();
  //              $pre_content[$index]['text'] .= $text;
  //              $pre_content[$index]['level'] = $level;
  //              $pre_content[$index]['style'] = $style_name;
  //            }
  //          }
  //
  //          // Clean up text.
  //          $pre_content[$index]['text'] = trim($pre_content[$index]['text']);
  //          // Remove potential empty TOC items.
  //          if (empty($pre_content[$index]['text'])) {
  //            unset($pre_content[$index]);
  //          }
  //
  //          // Regroup content based on the linkid group, which is built from H3
  //          // text.
  //          $link_id = FALSE;
  //          foreach ($pre_content as $id => $value) {
  //            if ($value['level'] == '3') {
  //              $link_id = $this->getDocumentIdFromProcessedContent($value['text']);
  //            }
  //            if ($link_id) {
  //              $content[$link_id][$id]['text'] = $value['text'];
  //              $content[$link_id][$id]['level'] = $value['level'];
  //              $content[$link_id][$id]['style'] = $value['style'];
  //            }
  //          }
  //        }
  //      }
  //    }
  //
  //    $first_text = reset($toc);
  //    $toc = array_values($toc);
  //
  //    return [
  //      'document_id' => $first_text['linkid'],
  //      'toc' => $toc,
  //      'content' => $content,
  //    ];
  //  }

  /**
   * Get document elements.
   */
  public function getDocumentElements($real_path_to_file): array {
    $phpWord = IOFactory::load($real_path_to_file);
    $elements = [];
    foreach ($phpWord->getSections() as $section) {
      foreach ($section->getElements() as $index => $element) {
        $elements[$index] = $element;
      }
    }
    return $elements;
  }

  /**
   * Get file path of file set to media entity.
   */
  public function getMediaDocumentFile($node_entity): bool|EntityInterface|null {
    $media_entity = $node_entity->get('field_media')->referencedEntities();
    $file_manager = $this->entityTypeManager->getStorage('file');
    if ($media_entity[0]) {
      $fid = $media_entity[0]->getSource()->getSourceFieldValue($media_entity[0]);
    }
    return $fid ? $file_manager->load($fid) : FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function getMediaDocumentFilePath($node_entity): bool|string {
    if ($file = $this->getMediaDocumentFile($node_entity)) {
      return $this->fileSystem->realpath($file->getFileUri());
    };

    return FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function getRuleDocumentFileMimeType($node_entity) {
    if ($file = $this->getMediaDocumentFile($node_entity)) {
      return $file->getMimeType();
    };

    return FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function isRuleDocumentFileWordDoc($node_entity): bool {
    return $this->getRuleDocumentFileMimeType($node_entity) == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
  }

  /**
   * Get word docs.
   */
  public function getRuleDocuments($document_id = NULL): array {
    $node_manager = $this->entityTypeManager
      ->getStorage('node');

    $id = $this->getRocDefiningDocumentCategoryId();
    $bundle = $this->getRocDefiningDocumentBundleType();
    $sort = [];

    if ($document_id) {
      return $node_manager->loadByProperties([
        'type' => $bundle,
        'field_document_type' => $id,
        'field_document_id' => Xss::filter($document_id),
      ]);
    }
    else {
      $nodes = $node_manager->loadByProperties([
        'type' => $bundle,
        'field_document_type' => $id,
      ]);

      // Get the sorting order from the draggable view.
      $results = views_get_view_result('roc_view', 'roc_admin_page', $this->getRocDefiningDocumentCategoryId());
      foreach ($results as $item) {
        $sort[$item->nid] = $item->nid;
      }

      // Sort the media items based on the draggable sorted rules view.
      return array_replace($sort, $nodes);
    }
  }

  /**
   * Get word docs.
   */
  public function getRuleDocument($document_id) {
    $doc = $this->getRuleDocuments($document_id);
    return reset($doc);
  }

  // /**
  //   * Get rule document note.
  //   */
  //  public function getRuleDocumentNote($media_entity, $classes = []): ?string {
  //    $note = $media_entity->get('note')->value;
  //    if (!empty($classes)) {
  //      $classes = implode(' ', $classes);
  //    }
  //    return !empty($note) ? "<span class='$classes'>$note</span>" : NULL;
  //  }
  //  /**
  //   * Get the document content for a given rule document..
  //   */
  //  public function getRuleDocumentSectionContent($doc_id, $doc_section_id) {
  //    if ($doc = $this->getRuleDocument($doc_id)) {
  //      $value = $doc->get('processed_content')->value;
  //      $value = json_decode($value, TRUE);
  //      return $value[$doc_section_id] ?? FALSE;
  //    }
  //
  //    return FALSE;
  //  }

  /**
   * Returns the possible Document section id (rule_id).
   */
  public function processRuleDocumentData($doc_elements, $parent_node): array {
    $toc = [];
    $content = [];
    $pre_content = [];

    foreach ($doc_elements as $index => $element) {
      if ($element instanceof TextRun) {
        $style_name = $element->getParagraphStyle()->getStyleName();
        $level = $this->getLevelFromStyleName($style_name);
        $style = $this->getStyleClassFromStyleName($style_name);
        $sub_elements = $element->getElements();

        // Build out our toc arrays.
        if (in_array($level, ['1', '2'])) {
          foreach ($sub_elements as $sub_element) {
            if ($sub_element instanceof Text) {
              $text = $sub_element->getText();
              $section_id = $this->getDocumentIdFromProcessedContent($text);
              $toc[$index]['field_title'] .= $text;
              $toc[$index]['field_content'] .= '';
              $toc[$index]['field_content_search'] .= '';
              $toc[$index]['field_section_level'] = $level;
              $toc[$index]['field_section_style'] = $style;
              $toc[$index]['field_section_style_name'] = $style_name;
              $toc[$index]['field_parent_node'] = $parent_node->id();
              $toc[$index]['field_parent_section'] = $section_id;
            }
            $toc[$index]['field_title'] = trim($toc[$index]['field_title']);
            if (empty($toc[$index]['field_title'])) {
              unset($toc[$index]);
            }
          }
        }

        if (!in_array($level, ['1', '2', 'toc'])) {
          // Gather sub-elements together into 1 element of text.
          foreach ($sub_elements as $sub_element) {
            if ($sub_element instanceof Text) {
              $text = $sub_element->getText();
              if ($level == '3') {
                $pre_content[$index]['field_title'] .= $text;
              }
              else {
                $pre_content[$index]['field_title'] .= '';
              }
              $pre_content[$index]['field_content'] .= $text;
              $pre_content[$index]['field_content_search'] .= $text;
              $pre_content[$index]['field_section_level'] = $level;
              $pre_content[$index]['field_section_style'] = $style;
              $pre_content[$index]['field_section_style_name'] = $style_name;
              $pre_content[$index]['main_index'] = $index;
            }
          }
        }
      }
    }

    foreach ($pre_content as $id => $value) {
      if (in_array($value['field_section_level'], ['1', '2', '3', 'toc'])) {
        $section_id = $this->getDocumentIdFromProcessedContent($value['field_content']);
        $content[$section_id]['field_title'] .= $value['field_title'];
        $content[$section_id]['field_section_level'] = $value['field_section_level'];
        $content[$section_id]['field_section_style'] = $value['field_section_style'];
        $content[$section_id]['field_section_style_name'] = $value['field_section_style_name'];
        $content[$section_id]['field_parent_section'] = $section_id ?? '';
        $content[$section_id]['field_parent_node'] = $parent_node->id();
        $content[$section_id]['field_content'][] = [
          'content' => $value['field_content'],
          'level' => $value['field_section_level'],
          'style' => $value['field_section_style'],
        ];
        $content[$section_id]['field_content_search'][] = $value['field_content_search'];
        $content[$section_id]['main_index'] = $value['main_index'];
      }
      else {
        $content[$section_id]['field_content'][] = [
          'content' => $value['field_content'],
          'level' => $value['field_section_level'],
          'style' => $value['field_section_style'],
        ];
        $content[$section_id]['field_content_search'][] = $value['field_content_search'];
        $content[$section_id]['main_index'] = $value['main_index'];
        unset($pre_content[$id]);
      }
    }

    $items = [];
    foreach ($content as $item) {
      $items[$item['main_index']] = $item;
    }

    $combined_items = $toc + $items;
    ksort($combined_items);
    $first_text = reset($toc);
//    $toc = array_values($toc);

    return [
      'document_id' => $first_text['field_id'],
      'parent_nid' => $parent_node->id(),
      'content' => $combined_items,
    ];
  }

//  /**
//   * Process our new or existing processed content entity.
//   */
//  public function updateProcessedDocumentContent($entity, $content, $document_id) {
//    if (!$entity || $entity->type != 'processed_document_content') {
//      return FALSE;
//    }
//
//    // Set the basic fields.
//    $entity->set('field_id', $content['field_id']);
//    $entity->set('field_parent_id', $document_id);
//    $entity->set('field_section_level', $content['field_section_level']);
//    $entity->set('field_section_style', $content['field_section_style']);
//
//    if ($content['field_section_style_name']) {
//      $entity->set('field_section_style_name', $content['field_section_style_name']);
//    }
//
//    if ($content['field_section_parent_id']) {
//      $entity->set('field_section_parent_id', $content['field_section_parent_id']);
//    }
//
//    // Set the main content field.
//    $entity->set('field_content', [
//      'value' => $content['field_content'],
//      'format' => 'full_html',
//    ]);
//
//    $entity->save();
//
//    return $entity->id();
//  }

//  /**
//   * Process our new or existing processed content entity.
//   */
//  public function createProcessedDocumentContent($entity, $content, $document_id) {
//    if (!$entity || $entity->type != 'processed_document_content') {
//      return FALSE;
//    }
//
//    // Set the basic fields.
//    $entity->set('field_id', $content['field_id']);
//    $entity->set('field_parent_id', $document_id);
//    $entity->set('field_section_level', $content['field_section_level']);
//    $entity->set('field_section_style', $content['field_section_style']);
//
//    if ($content['field_section_style_name']) {
//      $entity->set('field_section_style_name', $content['field_section_style_name']);
//    }
//
//    if ($content['field_section_parent_id']) {
//      $entity->set('field_section_parent_id', $content['field_section_parent_id']);
//    }
//
//    // Set the main content field.
//    $entity->set('field_content', [
//      'value' => $content['field_content'],
//      'format' => 'full_html',
//    ]);
//
//    return $entity;
//  }

}
