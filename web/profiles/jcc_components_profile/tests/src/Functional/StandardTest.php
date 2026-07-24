<?php

namespace Drupal\Tests\jcc_tc\Functional;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Url;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests JCCTC installation profile expectations.
 *
 * @group jcc_tc
 */
class JCCTCTest extends BrowserTestBase {

  use SchemaCheckTestTrait;

  protected $profile = 'jcc_tc';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Tests JCCTC installation profile.
   */
  public function testJCCTC() {
    $this->drupalGet('');
    $this->assertSession()->linkExists(t('Contact'));
    $this->clickLink(t('Contact'));
    $this->assertSession()->statusCodeEquals(200);

    // Test anonymous user can access 'Main navigation' block.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'post comments',
      'skip comment approval',
      'create article content',
      'create page content',
    ]);
    $this->drupalLogin($this->adminUser);
    // Configure the block.
    $this->drupalGet('admin/structure/block/add/system_menu_block:main/bartik');
    $this->submitForm([
      'region' => 'sidebar_first',
      'id' => 'main_navigation',
    ], 'Save block');
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Main navigation');

    // Verify we have role = aria on system_powered_by and help_block
    // blocks.
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', [
      ':role' => 'complementary',
      ':id' => 'block-bartik-help',
    ]);

    $this->assertEquals(1, count($elements), 'Found complementary role on help block.');

    $this->drupalGet('');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', [
      ':role' => 'complementary',
      ':id' => 'block-bartik-powered',
    ]);
    $this->assertEquals(1, count($elements), 'Found complementary role on powered by block.');

    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertSession()->pageTextContains('Main navigation');

    // Ensure comments don't show in the front page RSS feed.
    // Create an article.
    $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Foobar',
      'promote' => 1,
      'status' => 1,
      'body' => [['value' => 'Then she picked out two somebodies,<br />Sally and me', 'format' => 'basic_html']],
    ]);

    // Add a comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/1');
    $this->assertSession()->responseContains('Then she picked out two somebodies,<br />Sally and me');
    $this->submitForm([
      'subject[0][value]' => 'Barfoo',
      'comment_body[0][value]' => 'Then she picked out two somebodies, Sally and me',
    ], 'Save');
    // Fetch the feed.
    $this->drupalGet('rss.xml');
    $this->assertSession()->pageTextContains('Foobar');
    $this->assertSession()->pageTextNotContains('Then she picked out two somebodies, Sally and me');

    // Ensure block body exists.
    $this->drupalGet('block/add');
    $this->assertSession()->fieldExists('body[0][value]');

    // Now we have all configuration imported, test all of them for schema
    // conformance. Ensures all imported default configuration is valid when
    // jcc_tc profile modules are enabled.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }

    // Ensure that configuration from the JCCTC profile is not reused when
    // enabling a module again since it contains configuration that can not be
    // installed. For example, editor.editor.basic_html is editor configuration
    // that depends on the ckeditor module. The ckeditor module can not be
    // installed before the editor module since it depends on the editor module.
    // The installer does not have this limitation since it ensures that all of
    // the install profiles dependencies are installed before creating the
    // editor configuration.
    foreach (FilterFormat::loadMultiple() as $filter) {
      // Ensure that editor can be uninstalled by removing use in filter
      // formats. It is necessary to prime the filter collection before removing
      // the filter.
      $filter->filters();
      $filter->removeFilter('editor_file_reference');
      $filter->save();
    }
    \Drupal::service('module_installer')->uninstall(['editor', 'ckeditor']);
    $this->rebuildContainer();
    \Drupal::service('module_installer')->install(['editor']);
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = ContactForm::load('feedback');
    $recipients = $contact_form->getRecipients();
    $this->assertSession()->statusCodeEquals(['simpletest@example.com'], $recipients);

    $role = Role::create([
      'id' => 'admin_theme',
      'label' => 'Admin theme',
    ]);
    $role->grantPermission('view the administration theme');
    $role->save();
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');

    // Make sure the optional image styles are not installed.
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertSession()->responseNotContains('Max 325x325');
    $this->assertSession()->responseNotContains('Max 650x650');
    $this->assertSession()->responseNotContains('Max 1300x1300');
    $this->assertSession()->responseNotContains('Max 2600x2600');

    // Make sure the optional image styles are installed after enabling
    // the responsive_image module.
    \Drupal::service('module_installer')->install(['responsive_image']);
    $this->rebuildContainer();
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertSession()->pageTextContains('Max 325x325');
    $this->assertSession()->pageTextContains('Max 650x650');
    $this->assertSession()->pageTextContains('Max 1300x1300');
    $this->assertSession()->pageTextContains('Max 2600x2600');

    // Verify certain routes' responses are cacheable by Dynamic Page Cache, to
    // ensure these responses are very fast for authenticated users.
    $this->dumpHeaders = TRUE;
    $this->drupalLogin($this->adminUser);
    $url = Url::fromRoute('contact.site_page');
    $this->drupalGet($url);
    $this->assertEquals($this->getSession()->getResponseHeader(DynamicPageCacheSubscriber::HEADER), 'UNCACHEABLE', 'Site-wide contact page cannot be cached by Dynamic Page Cache.');

    $url = Url::fromRoute('<front>');
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEquals($this->getSession()->getResponseHeader(DynamicPageCacheSubscriber::HEADER), 'HIT', 'Frontpage is cached by Dynamic Page Cache.');

    $url = Url::fromRoute('entity.node.canonical', ['node' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEquals($this->getSession()->getResponseHeader(DynamicPageCacheSubscriber::HEADER), 'HIT', 'Full node page is cached by Dynamic Page Cache.');

    $url = Url::fromRoute('entity.user.canonical', ['user' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEquals($this->getSession()->getResponseHeader(DynamicPageCacheSubscriber::HEADER), 'HIT', 'User profile page is cached by Dynamic Page Cache.');
  }

}
