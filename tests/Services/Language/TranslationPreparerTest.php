<?php

namespace App\Tests\Services\Language;

use App\Services\Language\LocalText;
use App\Services\Language\TranslationPreparer;
use PHPUnit\Framework\TestCase;

class TranslationPreparerTest extends TestCase {

    public function testPrepareMarkup() {
        $default = ['path.txt' => new LocalText('original', LocalText::TYPE_MARKUP)];
        $translation = ['path.txt' => new LocalText('translated', LocalText::TYPE_MARKUP)];

        $expected = [
            [
                'key' => 'translation[path.txt]',
                'searchkey' => 'path',
                'default' => 'original',
                'target' => 'translated',
                'type' => LocalText::TYPE_MARKUP
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareMarkupEmptyTarget() {
        $default = ['path.txt' => new LocalText('original', LocalText::TYPE_MARKUP)];
        $translation = [];

        $expected = [
            [
                'key' => 'translation[path.txt]',
                'searchkey' => 'path',
                'default' => 'original',
                'target' => '',
                'type' => LocalText::TYPE_MARKUP
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareMarkupEmptyDefault() {
        $default = [];
        $translation = ['path' => new LocalText('translated', LocalText::TYPE_MARKUP)];

        $expected = [];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArray() {
        $default = ['path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText(['key' => 'translated value'], LocalText::TYPE_ARRAY)];

        $expected = [
            [
                'key' => 'translation[path][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyTarget() {
        $default = ['path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)];
        $translation = [];

        $expected = [
            [
                'key' => 'translation[path][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyTarget2() {
        $default = ['path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)];
        $translation = [];

        $expected = [
            [
                'key' => 'translation[path][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayEmptyDefault() {
        $default = ['path' => new LocalText([], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText(['key' => 'translated value'], LocalText::TYPE_ARRAY)];

        $expected = [];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJs() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText(['js' => ['key' => 'translated value']], LocalText::TYPE_ARRAY)];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty1() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText(['js' => []], LocalText::TYPE_ARRAY)];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty2() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText([], LocalText::TYPE_ARRAY)];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmpty3() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = [];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareArrayJsEmptyDefault() {
        $default = ['path' => new LocalText(['js' => []], LocalText::TYPE_ARRAY)];
        $translation = ['path' => new LocalText(['js' => ['key' => 'translated value']], LocalText::TYPE_ARRAY)];

        $expected = [];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }


    public function testPrepareUserMarkup() {
        $default = ['path.txt' => new LocalText('original', LocalText::TYPE_MARKUP)];
        $translation = ['path.txt' => 'translated'];

        $expected = [
            [
                'key' => 'translation[path.txt]',
                'searchkey' => 'path',
                'default' => 'original',
                'target' => 'translated',
                'type' => LocalText::TYPE_MARKUP
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserMarkupEmptyDefault() {
        $default = [];
        $translation = ['path' => 'translated'];

        $expected = [];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArray() {
        $default = ['path' => new LocalText(['key' => 'value'], LocalText::TYPE_ARRAY)];
        $translation = ['path' => ['key' => 'translated value']];

        $expected = [
            [
                'key' => 'translation[path][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayEmptyDefault() {
        $default = ['path' => new LocalText([], LocalText::TYPE_ARRAY)];
        $translation = ['path' => ['key' => 'translated value']];

        $expected = [];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJs() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = ['path' => ['js' => ['key' => 'translated value']]];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => 'translated value',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJsEmpty1() {
        $default = ['path' => new LocalText(['js' => ['key' => 'value']], LocalText::TYPE_ARRAY)];
        $translation = ['path' => ['js' => []]];

        $expected = [
            [
                'key' => 'translation[path][js][key]',
                'searchkey' => 'key',
                'default' => 'value',
                'target' => '',
                'type' => LocalText::TYPE_ARRAY
            ]
        ];

        $preparer = new TranslationPreparer();
        $result = $preparer->prepare($default, $translation);

        $this->assertEquals($expected, $result);
    }

    public function testPrepareUserArrayJsEmptyDefault() {
        $default = ['path' => new LocalText(['js' => []], LocalText::TYPE_ARRAY)];
        $translation = ['path' => ['js' => ['key' => 'translated value']]];

        $expected = [];

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

        $localText = ['path' => new LocalText('stuff', LocalText::TYPE_MARKUP)];
        $result = $translationController->createEntryGetTranslation($localText, 'path');
        $this->assertEquals('stuff', $result);

        $localText = ['path' => new LocalText(['key' => 'stuff'], LocalText::TYPE_ARRAY)];
        $result = $translationController->createEntryGetTranslation($localText, 'path', 'key');
        $this->assertEquals('stuff', $result);

        $localText = ['path' => new LocalText(['key' => ['jskey' => 'stuff']], LocalText::TYPE_ARRAY)];
        $result = $translationController->createEntryGetTranslation($localText, 'path', 'key', 'jskey');
        $this->assertEquals('stuff', $result);
    }

}
