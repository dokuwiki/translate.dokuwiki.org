<?php

namespace App\Tests\Services\Language;

use App\Services\Language\Author;
use App\Services\Language\AuthorList;
use App\Services\Language\LocalText;
use App\Services\Language\UserTranslationValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ValidateUserTranslationTest extends TestCase {

    private ValidatorDummy $validator;

    function setUp(): void {
        $this->validator = new ValidatorDummy();
    }

    function testValidateTranslationMarkup() {
        $defaultTranslation = [
            'path' => new LocalText('default text', LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $userTranslation = [
            'path' => 'new translated text'
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [
            'path' => new LocalText('new translated text', LocalText::TYPE_MARKUP)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupNoChange() {
        $defaultTranslation = [
            'path' => new LocalText("default\ntext", LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [
            'path' => new LocalText("translated\ntext", LocalText::TYPE_MARKUP)
        ];

        //submissions could have other line endings
        $userTranslation = [
            'path' => "translated\r\ntext"
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [
            'path' => new LocalText("translated\ntext", LocalText::TYPE_MARKUP)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupEmptyDefault() {
        $defaultTranslation = [];
        $previousTranslation = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $userTranslation = [
            'path' => 'new translated text'
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        // internal cleanup of old strings does not regarded as translation change by this user
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupEmptyDefaultCombinedWithEntryWithNoChange() {
        $defaultTranslation = [
            'path2' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP),
            'path2' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $userTranslation = [
            'path' => 'new translated text',
            'path2' => 'translated text'
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [
            'path2' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        //internal cleanup of old strings does not count as change by this user
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupNoUserTranslation() {
        $defaultTranslation = [
            'path' => new LocalText('default text', LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        //should not occur, normally all values from default translation should be in userTranslation
        $userTranslation = [];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupEmptyUserTranslation() {
        $defaultTranslation = [
            'path' => new LocalText('default text', LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $userTranslation = ['path' => ''];

        $author = 'author';
        $authorEmail = 'author@example.com';

        //empty files are filtered when creating the patch
        $expected = [
            'path' => new LocalText('', LocalText::TYPE_MARKUP)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationMarkupEmptyPreviousTranslation() {
        $defaultTranslation = [
            'path' => new LocalText('default text', LocalText::TYPE_MARKUP)
        ];
        $previousTranslation = [];

        $userTranslation = ['path' => 'translated text',];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [
            'path' => new LocalText('translated text', LocalText::TYPE_MARKUP)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArray() {
        $defaultTranslation = [
            'path' => new LocalText(
                [
                    'key' => 'value',
                    'js' => ['key' => 'value']
                ], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(
                [
                    'key' => 'translated value',
                    'js' => ['key' => 'translated value']
                ], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => [
                'key' => 'new translated value',
                'js' => ['key' => 'value']
            ]
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expectedAuthor = new AuthorList();
        $expectedAuthor->add(new Author('author', 'author@example.com'));
        $expected = [
            'path' => new LocalText(
                [
                    'key' => 'new translated value',
                    'js' => ['key' => 'value']
                ], LocalText::TYPE_ARRAY,
                $expectedAuthor)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayEmptyDefault() {
        $defaultTranslation = [];
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value', 'js' => ['key' => 'value']], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value', 'js' => ['key' => 'value']]
        ];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        //internal cleanup of old strings does not count as change by this user
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationArrayNoUserTranslation() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value', 'js' => ['key' => 'value']], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(['key' => 'translated value', 'js' => ['key' => 'value']], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [];

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = [];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayEmptyUserTranslation() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value', 'js' => ['key' => 'value']], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(['key' => 'translated value', 'js' => ['key' => 'value']], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = ['path' => ['key' => '', 'js' => ['key' => '']]];

        $author = 'author';
        $authorEmail = 'author@example.com';

        //empty files are filtered when creating the patch
        $expectedAuthor = new AuthorList();
        $expectedAuthor->add(new Author('author', 'author@example.com'));
        $expected = [
            'path' => new LocalText(['key' => '', 'js' => ['key' => '']], LocalText::TYPE_ARRAY, $expectedAuthor)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayAuthor() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value'], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value']
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthor = new AuthorList();
        $expectedAuthor->add(new Author('author', 'e@ma.il'));
        $expected = [
            'path' => new LocalText(['key' => 'new translated value'], LocalText::TYPE_ARRAY, $expectedAuthor)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayKeepAuthors() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)
        ];
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value'], LocalText::TYPE_ARRAY, $prevAuthors)
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value']
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = [
            'path' => new LocalText(['key' => 'new translated value'], LocalText::TYPE_ARRAY, $expectedAuthors)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayKeepAuthorsRenamed() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)
        ];
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $prevAuthors->add(new Author('author old name', 'e@ma.il'));
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value'], LocalText::TYPE_ARRAY, $prevAuthors)
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value']
        ];

        $author = 'author new name';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author new name', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = [
            'path' => new LocalText(['key' => 'new translated value'], LocalText::TYPE_ARRAY, $expectedAuthors)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                                                  $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayKeepHeader() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY, null, " * old header1\n * @licence GPL")
        ];
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value'], LocalText::TYPE_ARRAY, $prevAuthors, " * old header2\n * @licence GPL")
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value']
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = [
            'path' => new LocalText(['key' => 'new translated value'], LocalText::TYPE_ARRAY, $expectedAuthors, " * old header2\n * @licence GPL")
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                                                  $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayAuthorsDoNotMix() {
        $authors = new AuthorList();
        $authors->add(new Author('other', 'some'));
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY, $authors)
        ];
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value'], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => ['key' => 'new translated value']
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expected = [
            'path' => new LocalText(['key' => 'new translated value'], LocalText::TYPE_ARRAY, $expectedAuthors)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }

    function testValidateTranslationArrayDoNotSetAuthorIfTranslationNotChanged() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value','key2' => 'value'], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value', 'key2' => 'translated value'], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => ['key' => 'translated value', 'key2' => 'translated value']
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expected = [
            'path' => new LocalText(['key' => 'translated value', 'key2' => 'translated value'], LocalText::TYPE_ARRAY)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationArrayDoNotSetAuthorIfTranslationNotChangedInJsArray() {
        $defaultTranslation = [
            'path' => new LocalText(['key' => 'value', 'js' => ['some', 'other']], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path' => new LocalText(
                ['key' => 'translated value', 'js' => ['some', 'translated other']], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path' => ['key' => 'translated value', 'js' => ['some', 'translated other']]
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expected = [
            'path' => new LocalText(['key' => 'translated value', 'js' => ['some', 'translated other']], LocalText::TYPE_ARRAY)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationArrayGlobalHasChangedAll() {
        $defaultTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path3' => 'translated text',
            'path4' => 'translated text2',
            'path' => [
                'key' => 'translated value',
                'key2' => 'translated value2',
                'js' => ['translated some', 'translated other']
            ],
            'path2' => [
                'js' => ['translated some2', 'translated other2'],
                'key' => 'translated value',
                'key2' => 'translated value2'
            ]
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));

        $expected = [
            'path3' => new LocalText('translated text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('translated text2', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'translated value',
                'key2' => 'translated value2',
                'js' => ['translated some', 'translated other']
            ], LocalText::TYPE_ARRAY, $expectedAuthors),
            'path2' => new LocalText([
                'js' => ['translated some2', 'translated other2'],
                'key' => 'translated value',
                'key2' => 'translated value2'
            ], LocalText::TYPE_ARRAY, $expectedAuthors)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();
        $this->assertEquals($expected, $result);

        $errors = $validator->getErrors();
        $this->assertArrayNotHasKey('translation', $errors);
    }


    function testValidateTranslationArrayGlobalHasChangedNothing() {
        $defaultTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path3' => "text",
            'path4' => "text",
            'path' => [
                'key' => "value",
                'key2' => "value2",
                'js' => ["some", "other"]
            ],
            'path2' => [
                'js' => ["some2", "other2"],
                'key' => "value",
                'key2' => "value2"
            ]
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));

        $expected = [
            'path3' => new LocalText("text", LocalText::TYPE_MARKUP),
            'path4' => new LocalText("text", LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => "value",
                'key2' => "value2",
                'js' => ["some", "other"]
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ["some2", "other2"],
                'key' => "value",
                'key2' => "value2"
            ], LocalText::TYPE_ARRAY)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();
        $this->assertEquals($expected, $result);

        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }


    function testValidateTranslationArrayGlobalHasChangedNothingEmptyPlaces() {
        $defaultTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => '',
                'key2' => '',
                'js' => ['', '']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];

        $userTranslation = [
            'path3' => "text",
            'path4' => "text",
            'path' => [
                'key' => "",
                'key2' => "",
                'js' => ["", ""]
            ],
            'path2' => [
                'js' => ["some2", "other2"],
                'key' => "value",
                'key2' => "value2"
            ]
        ];

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));

        $expected = [
            'path3' => new LocalText("text", LocalText::TYPE_MARKUP),
            'path4' => new LocalText("text", LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => "",
                'key2' => "",
                'js' => ["", ""]
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ["some2", "other2"],
                'key' => "value",
                'key2' => "value2"
            ], LocalText::TYPE_ARRAY)
        ];

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();
        $this->assertEquals($expected, $result);

        $errors = $validator->getErrors();
        $this->assertArrayHasKey('translation', $errors);
    }

    function testValidateTranslationArrayGlobalHasChangedOneItem() {
        $defaultTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];
        $previousTranslation = [
            'path3' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path4' => new LocalText('text', LocalText::TYPE_MARKUP),
            'path' => new LocalText([
                'key' => 'value',
                'key2' => 'value2',
                'js' => ['some', 'other']
            ], LocalText::TYPE_ARRAY),
            'path2' => new LocalText([
                'js' => ['some2', 'other2'],
                'key' => 'value',
                'key2' => 'value2',
            ], LocalText::TYPE_ARRAY)
        ];

        for ($i = 0; $i <= 9; $i++) {
            $change = array_fill(0,10, '');
            $change[$i] = "translated$i ";
            $userTranslation = [
                'path3' => "$change[8]text",
                'path4' => "$change[9]text",
                'path' => [
                    'key' => "$change[0]value",
                    'key2' => "$change[1]value2",
                    'js' => ["$change[2]some", "$change[3]other"]
                ],
                'path2' => [
                    'js' => ["$change[4]some2", "$change[5]other2"],
                    'key' => "$change[6]value",
                    'key2' => "$change[7]value2"
                ]
            ];

            $author = 'author';
            $authorEmail = 'e@ma.il';

            $expectedAuthors = new AuthorList();
            $expectedAuthors->add(new Author('author', 'e@ma.il'));

            $expected = [
                'path3' => new LocalText("$change[8]text", LocalText::TYPE_MARKUP),
                'path4' => new LocalText("$change[9]text", LocalText::TYPE_MARKUP),
                'path' => new LocalText([
                    'key' => "$change[0]value",
                    'key2' => "$change[1]value2",
                    'js' => ["$change[2]some", "$change[3]other"]
                ], LocalText::TYPE_ARRAY, $i < 4 ? $expectedAuthors : null),
                'path2' => new LocalText([
                    'js' => ["$change[4]some2", "$change[5]other2"],
                    'key' => "$change[6]value",
                    'key2' => "$change[7]value2"
                ], LocalText::TYPE_ARRAY, 3 < $i && $i < 8 ? $expectedAuthors : null)
            ];

            $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail, $this->validator);
            $result = $validator->validate();
            $this->assertEquals($expected, $result);

            $errors = $validator->getErrors();
            $this->assertArrayNotHasKey('translation', $errors);
        }
    }

    function testEmptyAuthorName() {
        $validator = new UserTranslationValidator([], [],
            [], '', 'author@example.com', $this->validator);

        $this->assertArrayHasKey('author', $validator->getErrors());
    }

    function testEmptyAuthorEmail() {
        $validator = new UserTranslationValidator([], [],
            [], 'author', '', $this->validator);
        $this->assertArrayHasKey('email', $validator->getErrors());
    }
}

class ValidatorDummy extends RecursiveValidator {

    public function __construct() {
    }

    public function validate($value, $constraints = null, $groups = null, $deep = false) : ConstraintViolationListInterface {
        return new ConstraintViolationList([]);
    }

}
