<?php

namespace dokuwikiTranslaterBundle\tests\Services\Language;

use org\dokuwiki\translatorBundle\Services\Language\Author;
use org\dokuwiki\translatorBundle\Services\Language\AuthorList;
use org\dokuwiki\translatorBundle\Services\Language\LocalText;
use org\dokuwiki\translatorBundle\Services\Language\UserTranslationValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ValidateUserTranslationTest extends TestCase {

    private $validator;

    function setUp() {
        $this->validator = new ValidatorDummy();
    }

    function testValidateTranslationMarkup() {
        $defaultTranslation = array(
            'path' => new LocalText('default text', LocalText::$TYPE_MARKUP)
        );
        $previousTranslation = array(
            'path' => new LocalText('translated text', LocalText::$TYPE_MARKUP)
        );

        $userTranslation = array(
            'path' => 'new translated text'
        );

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = array(
            'path' => new LocalText('new translated text', LocalText::$TYPE_MARKUP)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationMarkupEmptyDefault() {
        $defaultTranslation = array();
        $previousTranslation = array(
            'path' => new LocalText('translated text', LocalText::$TYPE_MARKUP)
        );

        $userTranslation = array(
            'path' => 'new translated text'
        );

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = array();

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationMarkupNoUserTranslation() {
        $defaultTranslation = array(
            'path' => new LocalText('default text', LocalText::$TYPE_MARKUP)
        );
        $previousTranslation = array(
            'path' => new LocalText('translated text', LocalText::$TYPE_MARKUP)
        );

        $userTranslation = array();

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = array();

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArray() {
        $defaultTranslation = array(
            'path' => new LocalText(
                array('key' => 'value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY)
        );
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value', 'js' => array('key' => 'translated value')), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value', 'js' => array('key' => 'value'))
        );

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expectedAuthor = new AuthorList();
        $expectedAuthor->add(new Author('author', 'author@example.com'));
        $expected = array(
            'path' => new LocalText(
                array('key' => 'new translated value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY,
                $expectedAuthor)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayEmptyDefault() {
        $defaultTranslation = array();
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value', 'js' => array('key' => 'value'))
        );

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = array();

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayNoUserTranslation() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY)
        );
        $previousTranslation = array(
            'path' => new LocalText(array('key' => 'translated value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array();

        $author = 'author';
        $authorEmail = 'author@example.com';

        $expected = array();

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayAuthor() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY)
        );
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthor = new AuthorList();
        $expectedAuthor->add(new Author('author', 'e@ma.il'));
        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $expectedAuthor)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayKeepAuthors() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY)
        );
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY, $prevAuthors)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $expectedAuthors)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayKeepAuthorsRenamed() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY)
        );
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $prevAuthors->add(new Author('author old name', 'e@ma.il'));
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY, $prevAuthors)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author new name';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author new name', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $expectedAuthors)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                                                  $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayKeepheader() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY, null, " * old header1\n * @licence GPL")
        );
        $prevAuthors = new AuthorList();
        $prevAuthors->add(new Author('other', 'some'));
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY, $prevAuthors, " * old header2\n * @licence GPL")
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expectedAuthors->add(new Author('other', 'some'));
        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $expectedAuthors, " * old header2\n * @licence GPL")
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
                                                  $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }
    function testValidateTranslationArrayAuthorsDoNotMix() {
        $authors = new AuthorList();
        $authors->add(new Author('other', 'some'));
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY, $authors)
        );
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expectedAuthors = new AuthorList();
        $expectedAuthors->add(new Author('author', 'e@ma.il'));
        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $expectedAuthors)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayDoNotSetAuthorIfTranslationNotChanged() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY)
        );
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expected = array(
            'path' => new LocalText(array('key' => 'translated value'), LocalText::$TYPE_ARRAY)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayDoNotSetAuthorIfTranslationNotChangedInJsArray() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value', 'js' => array('some', 'other')), LocalText::$TYPE_ARRAY)
        );
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value', 'js' => array('some', 'translated other')), LocalText::$TYPE_ARRAY)
        );

        $userTranslation = array(
            'path' => array('key' => 'translated value', 'js' => array('some', 'translated other'))
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expected = array(
            'path' => new LocalText(array('key' => 'translated value', 'js' => array('some', 'translated other')), LocalText::$TYPE_ARRAY)
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testEmptyAuthorName() {
        $validator = new UserTranslationValidator(array(), array(),
            array(), '', 'author@example.com', $this->validator);

        $this->assertArrayHasKey('author', $validator->getErrors());
    }

    function testEmptyAuthorEmail() {
        $validator = new UserTranslationValidator(array(), array(),
            array(), 'author', '', $this->validator);
        $this->assertArrayHasKey('email', $validator->getErrors());
    }
}

class ValidatorDummy extends RecursiveValidator {

    function __construct() {}

    public function validate($value, $constraints = null, $groups = null, $deep = false) {
        return array();
    }


}
