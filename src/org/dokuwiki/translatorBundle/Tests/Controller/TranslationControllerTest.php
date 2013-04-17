<?php
namespace org\dokuwiki\translatorBundle\Controller;

use org\dokuwiki\translatorBundle\Services\Language\LocalText;

class TranslationControllerTest extends \PHPUnit_Framework_TestCase{

    function testCreateEntryKey() {
        $translationController = new TranslationController();

        $this->assertEquals('translation[path]',
                $translationController->createEntryKey('path'));

        $this->assertEquals('translation[path][key]',
                            $translationController->createEntryKey('path', 'key'));

        $this->assertEquals('translation[path][key][jskey]',
                            $translationController->createEntryKey('path', 'key', 'jskey'));

    }

    function testCreateEntryGetTranslation() {
        $translationController = new TranslationController();

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
