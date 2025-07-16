<?php

namespace Drupal\Tests\voting\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\voting\Entity\Question;

/**
 * Tests the Question entity form.
 *
 * @group voting
 */
class QuestionFormTest extends BrowserTestBase {

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
   * A user with permission to administer questions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer voting',
      'vote in polls',
    ]);
  }

  /**
   * Tests creating a question.
   */
  public function testCreateQuestion() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/voting_question/add');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Test Question',
      'identifier[0][value]' => 'test_question',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Created the Test Question Question.');

    $question = Question::load(1);
    $this->assertNotNull($question);
    $this->assertEquals('Test Question', $question->label());
    $this->assertEquals('test_question', $question->get('identifier')->value);
  }

  /**
   * Tests editing a question.
   */
  public function testEditQuestion() {
    $question = Question::create([
      'title' => 'Original Question',
      'identifier' => 'original_question',
    ]);
    $question->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("admin/structure/voting_question/{$question->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Updated Question',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Saved the Updated Question Question.');

    $updated_question = Question::load($question->id());
    $this->assertEquals('Updated Question', $updated_question->label());
    $this->assertEquals('original_question', $updated_question->get('identifier')->value);
  }

  /**
   * Tests that the identifier cannot be changed after creation.
   */
  public function testIdentifierUnchangeable() {
    $question = Question::create([
      'title' => 'Test Question',
      'identifier' => 'test_question',
    ]);
    $question->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("admin/structure/voting_question/{$question->id()}/edit");

    $this->assertSession()->elementAttributeContains('css', 'input[name="identifier[0][value]"]', 'readonly', 'readonly');

    $edit = [
      'identifier[0][value]' => 'changed_identifier',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('The identifier cannot be changed after creation.');

    $updated_question = Question::load($question->id());
    $this->assertEquals('test_question', $updated_question->get('identifier')->value);
  }

  /**
   * Tests deleting a question.
   */
  public function testDeleteQuestion() {
    $question = Question::create([
      'title' => 'Test Question',
      'identifier' => 'test_question',
    ]);
    $question->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("admin/structure/voting_question/{$question->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([], 'Delete');

    $this->assertSession()->pageTextContains('The voting question Test Question has been deleted.');

    $deleted_question = Question::load($question->id());
    $this->assertNull($deleted_question);
  }

}
