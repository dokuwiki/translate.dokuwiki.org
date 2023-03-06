<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use PHPUnit\Framework\TestCase;

class LocalTextTest extends TestCase {



    public function testOnlyJsArray() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author author <e@ma.il>
 */
$lang['js']['key']             = 'value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)\n";
        $translation = new LocalText(array('js' => array('key' => 'value')), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testSingleLineLicence() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testLicenceSurroundedByEmptyCommentLines() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 3 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author author <e@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " *\n * @license    GPL 3 (https://www.gnu.org/licenses/gpl.html)\n *\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testNoLicenceTag() {

        $expected = <<<'CONTENT'
<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
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

    public function testNoStrings() {
        $this->expectException(LanguageFileIsEmptyException::class);

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $header = " * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)\n *\n";
        $translation = new LocalText(array(), LocalText::$TYPE_ARRAY, $author, $header);
        $translation->render();
    }

    public function testLicenceWithMoreAndNoEmails() {

        $expected = <<<'CONTENT'
<?php

/**
 * german language file
 *
 * @license    GPL 4 (https://www.gnu.org/licenses/gpl.html)
 *
 *
 * @package DokuWiki\lang\de\settings
 *
 * @author author <e@ma.il>
 * @author author2
 * @author author3
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $author->add(new Author('author2', ''));
        $author->add(new Author('author3', ''));
        $header = " *\n * german language file\n *\n * @license    GPL 4 (https://www.gnu.org/licenses/gpl.html)\n *\n *\n * @package DokuWiki\\lang\\de\\settings\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

    public function testLicenceWithMoreAndNoNames() {

        $expected = <<<'CONTENT'
<?php

/**
 * german language file
 *
 * @license    GPL 4 (https://www.gnu.org/licenses/gpl.html)
 *
 *
 * @package DokuWiki\lang\de\settings
 *
 * @author author <e@ma.il>
 * @author e1 <e1@ma.il>
 * @author e2 <e2@ma.il>
 */
$lang['key']                   = 'new translated value';

CONTENT;

        $author = new AuthorList();
        $author->add(new Author('author', 'e@ma.il'));
        $author->add(new Author('', 'e1@ma.il'));
        $author->add(new Author('', 'e2@ma.il'));
        $header = " *\n * german language file\n *\n * @license    GPL 4 (https://www.gnu.org/licenses/gpl.html)\n *\n *\n * @package DokuWiki\\lang\\de\\settings\n";
        $translation = new LocalText(array('key' => 'new translated value'), LocalText::$TYPE_ARRAY, $author, $header);
        $result = $translation->render();
        $this->assertEquals($expected, $result);
    }

}
