<?php

namespace Tests\dokuwikiTranslatorBundle\Services\Language;

use App\Services\Language\LocalText;
use App\Services\Language\TranslationPreparer;
use PHPUnit\Framework\TestCase;

class TranslationPreparerTest extends TestCase {

    public function testPrepareMarkup() {
        $default = array('path' => new LocalText('original', LocalText::$TYPE_MARKUP));
        $translation = array('path' => new LocalText('translated', LocalText::$TYPE_MARKUP));

        $expected = array(
            array(
                'key' => 'translation[path]',
                'default' => 'original',
                'target' => 'translated',
                'type' => LocalText::$TYPE_MARKUP
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareMarkupEmptyTarget() {
        $default = array('path' => new LocalText('original', LocalText::$TYPE_MARKUP));
        $translation = array();

        $expected = array(
            array(
                'key' => 'translation[path]',
                'default' => 'original',
                'target' => '',
                'type' => LocalText::$TYPE_MARKUP
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareMarkupEmptyDefault() {
        $default = array();
        $translation = array('path' => new LocalText('translated', LocalText::$TYPE_MARKUP));

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArray() {
        $default = array('path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array('key' => 'translated value'), LocalText::$TYPE_ARRAY));

        $expected = array(
            array(
                'key' => 'translation[path][key]',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyTarget() {
        $default = array('path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY));
        $translation = array();

        $expected = array(
            array(
                'key' => 'translation[path][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyTarget2() {
        $default = array('path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY));
        $translation = array();

        $expected = array(
            array(
                'key' => 'translation[path][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyDefault() {
        $default = array('path' => new LocalText(array(), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array('key' => 'translated value'), LocalText::$TYPE_ARRAY));

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJs() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array('js' => array('key' => 'translated value')), LocalText::$TYPE_ARRAY));

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty1() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array('js' => array()), LocalText::$TYPE_ARRAY));

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty2() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array(), LocalText::$TYPE_ARRAY));

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty3() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array();

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmptyDefault() {
        $default = array('path' => new LocalText(array('js' => array()), LocalText::$TYPE_ARRAY));
        $translation = array('path' => new LocalText(array('js' => array('key' => 'translated value')), LocalText::$TYPE_ARRAY));

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }


    public function testPrepareUserMarkup() {
        $default = array('path' => new LocalText('original', LocalText::$TYPE_MARKUP));
        $translation = array('path' => 'translated');

        $expected = array(
            array(
                'key' => 'translation[path]',
                'default' => 'original',
                'target' => 'translated',
                'type' => LocalText::$TYPE_MARKUP
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserMarkupEmptyDefault() {
        $default = array();
        $translation = array('path' => 'translated');

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArray() {
        $default = array('path' => new LocalText(array('key' => 'value'), LocalText::$TYPE_ARRAY));
        $translation = array('path' => array('key' => 'translated value'));

        $expected = array(
            array(
                'key' => 'translation[path][key]',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayEmptyDefault() {
        $default = array('path' => new LocalText(array(), LocalText::$TYPE_ARRAY));
        $translation = array('path' => array('key' => 'translated value'));

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJs() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array('path' => array('js' => array('key' => 'translated value')));

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJsEmpty1() {
        $default = array('path' => new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY));
        $translation = array('path' => array('js' => array()));

        $expected = array(
            array(
                'key' => 'translation[path][js][key]',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::$TYPE_ARRAY
            )
        );

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJsEmptyDefault() {
        $default = array('path' => new LocalText(array('js' => array()), LocalText::$TYPE_ARRAY));
        $translation = array('path' => array('js' => array('key' => 'translated value')));

        $expected = array();

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    function testCreateEntryKey() {
        $translationController = new TranslationPreparer();

        $this->assertEquals('translation[path]',
            $translationController->createEntryKey('path'));

        $this->assertEquals('translation[path][key]',
            $translationController->createEntryKey('path', 'key'));

        $this->assertEquals('translation[path][key][jskey]',
            $translationController->createEntryKey('path', 'key', 'jskey'));

    }

    function testCreateEntryGetTranslation() {
        $translationController = new TranslationPreparer();

        $localText = array('path' => new LocalText('stuff', LocalText::$TYPE_MARKUP));
        $result = $translationController->createEntryGetTranslation($localText, 'path');
        $this->assertEquals('stuff', $result);

        $localText = array('path' => new LocalText(array('key' => 'stuff'), LocalText::$TYPE_ARRAY));
        $result = $translationController->createEntryGetTranslation($localText, 'path', 'key');
        $this->assertEquals('stuff', $result);

        $localText = array('path' => new LocalText(array('key' => array('jskey' => 'stuff')), LocalText::$TYPE_ARRAY));
        $result = $translationController->createEntryGetTranslation($localText, 'path', 'key', 'jskey');
        $this->assertEquals('stuff', $result);
    }

}
