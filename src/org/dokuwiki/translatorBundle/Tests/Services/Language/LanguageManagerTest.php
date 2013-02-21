<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class LanguageManagerTest extends \PHPUnit_Framework_TestCase {

    function testLanguageManager() {
        $manager = new LanguageManager();
        $manager->readLanguages('D:/Temp/test 1/core/dokuwiki/repository/inc/lang');

        $this->assertTrue(true);
    }

}
