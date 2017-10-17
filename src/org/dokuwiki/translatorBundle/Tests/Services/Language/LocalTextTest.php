<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class LocalTextTest extends \PHPUnit_Framework_TestCase {

    public function testSinglelineLicence() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testLicenceSurroundedByEmptyCommentlines() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " *\n * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)\n *\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testNolicencetag() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * no licence tag
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " *\n * no licence tag\n *\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \org\dokuwiki\translatorBundle\Services\Language\LanguageFileIsEmptyException
     */
    public function testNostrings() {


        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)\n *\n";
        $translation = new LocalText(array(), LocalText::$TYPE_ARRAY, $author, $header);
        $translation->render();
    }

    public function testLicencewithmore() {

        $expected = <<<'CONTENT'
<?php

/**
 * german language file
 *
 * @license    GPL 4 (http://www.gnu.org/licenses/gpl.html)
 *
 *
 * @package DokuWiki\lang\de\settings
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " *\n * german language file\n *\n * @license    GPL 4 (http://www.gnu.org/licenses/gpl.html)\n *\n *\n * @package DokuWiki\\lang\\de\\settings\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

}