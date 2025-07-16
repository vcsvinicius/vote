<?php

namespace Drupal\Tests\voting\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\voting\Entity\Question;
use Drupal\voting\Entity\Option;

/**
 * Tests the Option entity form.
 *
 * @group voting
 */
class OptionFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'image',
    'file',
    'voting',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to create and edit options.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A test question.
   *
   * @var \Drupal\voting\Entity\Question
   */
  protected $question;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('voting'), 'Voting module is enabled');
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists('voting_question'), 'voting_question table exists');
    $this->assertTrue($schema->tableExists('voting_option'), 'voting_option table exists');

    // Create a question to associate with options.
    $this->question = Question::create([
      'title' => 'Test Question',
      'identifier' => 'test_question',
    ]);
    $this->question->save();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer voting',
      'vote in polls',
      'access content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests creating an option.
   */
  public function testCreateOption() {
    $this->drupalGet('admin/structure/voting_option/add');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Test Option',
      'description[0][value]' => 'This is a test option',
      'question[0][target_id]' => $this->question->label() . ' (' . $this->question->id() . ')',
    ];

    $this->submitForm($edit, 'Save');

    $options = Option::loadMultiple();
    $this->assertNotEmpty($options, 'Options were created');

    if (!empty($options)) {
      $option = reset($options);
      $this->assertNotNull($option, 'Option was created successfully');
      $this->assertEquals('Test Option', $option->label());
      $this->assertEquals('This is a test option', $option->get('description')->value);
      $this->assertEquals($this->question->id(), $option->get('question')->target_id);
    }
  }

  /**
   * Tests editing an option.
   */
  public function testEditOption() {
    $option = Option::create([
      'title' => 'Original Option',
      'description' => 'Original description',
      'question' => $this->question->id(),
    ]);
    $option->save();

    $this->drupalGet("admin/structure/voting_option/{$option->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Updated Option',
      'description[0][value]' => 'Updated description',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Saved the Updated Option Option.');

    $updated_option = Option::load($option->id());
    $this->assertEquals('Updated Option', $updated_option->label());
    $this->assertEquals('Updated description', $updated_option->get('description')->value);
  }

  /**
   * Tests that the form redirects to the collection page after saving.
   */
  public function testRedirectAfterSave() {
    $this->drupalGet('admin/structure/voting_option/add');

    $edit = [
      'title[0][value]' => 'New Option',
      'description[0][value]' => 'New description',
      'question[0][target_id]' => $this->question->label() . ' (' . $this->question->id() . ')',
    ];

    $this->submitForm($edit, 'Save');

    $current_url = $this->getSession()->getCurrentUrl();

    $options = Option::loadMultiple();
    $this->assertNotEmpty($options, 'Options were created');

    $this->assertStringContainsString('admin/structure/voting_option', $current_url);
  }

}
