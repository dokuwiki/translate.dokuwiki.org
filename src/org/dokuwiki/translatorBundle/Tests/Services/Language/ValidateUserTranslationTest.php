<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator;

class ValidateUserTranslationTest extends \PHPUnit_Framework_TestCase {

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

        $expected = array(
            'path' => new LocalText(
                array('key' => 'new translated value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY,
                array('author' => 'author@example.com'))
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

        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY,
                array('author' => 'e@ma.il'))
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
        $previousTranslation = array(
            'path' => new LocalText(
                array('key' => 'translated value'), LocalText::$TYPE_ARRAY, array('other' => 'some'))
        );

        $userTranslation = array(
            'path' => array('key' => 'new translated value')
        );

        $author = 'author';
        $authorEmail = 'e@ma.il';

        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY,
                array('author' => 'e@ma.il', 'other' => 'some'))
        );

        $validator = new UserTranslationValidator($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail, $this->validator);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

    function testValidateTranslationArrayAuthorsDoNotMix() {
        $defaultTranslation = array(
            'path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY, array('other' => 'some'))
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

        $expected = array(
            'path' => new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY,
                array('author' => 'e@ma.il'))
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

    /**
     * @expectedException org\dokuwiki\translatorBundle\Services\Language\UserTranslationValidatorException
     */
    function testEmptyAuthorName() {
        $validator = new UserTranslationValidator(array(), array(),
            array(), '', 'author@example.com', $this->validator);
    }

    /**
     * @expectedException org\dokuwiki\translatorBundle\Services\Language\UserTranslationValidatorException
     */
    function testEmptyAuthorEmail() {
        $validator = new UserTranslationValidator(array(), array(),
            array(), 'author', '', $this->validator);
    }
}

class ValidatorDummy extends Validator {

    function __construct() {}

    public function validateValue($value, Constraint $constraint, $groups = null) {
        return array();
    }


}