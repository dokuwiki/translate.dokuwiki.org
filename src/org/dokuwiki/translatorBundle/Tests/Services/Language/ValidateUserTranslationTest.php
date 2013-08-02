<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class ValidateUserTranslationTest extends \PHPUnit_Framework_TestCase {


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

        $author = '';
        $authorEmail = '';

        $expected = array(
            'path' => new LocalText('new translated text', LocalText::$TYPE_MARKUP)
        );

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $author = '';
        $authorEmail = '';

        $expected = array();

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $author = '';
        $authorEmail = '';

        $expected = array();

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail);
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

        $author = '';
        $authorEmail = '';

        $expected = array(
            'path' => new LocalText(
                array('key' => 'new translated value', 'js' => array('key' => 'value')), LocalText::$TYPE_ARRAY)
        );

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $author = '';
        $authorEmail = '';

        $expected = array();

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $author = '';
        $authorEmail = '';

        $expected = array();

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
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

        $validator = new ValidateUserTranslation($defaultTranslation, $previousTranslation,
            $userTranslation, $author, $authorEmail);
        $result = $validator->validate();

        $this->assertEquals($expected, $result);
    }

}