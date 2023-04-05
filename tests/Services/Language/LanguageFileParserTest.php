<?php
namespace App\Tests\Services\Language;

use App\Services\Language\Author;
use App\Services\Language\AuthorList;
use App\Services\Language\LanguageFileParser;
use App\Services\Language\LanguageParseException;
use PHPUnit\Framework\TestCase;

class LanguageFileParserTestDummy extends LanguageFileParser {


    function __construct() {
        parent::__construct('prefixpath');
        $this->author = new AuthorList();
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getContent() {
        return $this->content;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function getLangByKey($key) {
        return $this->lang[$key];
    }

    public function getLang() {
        return $this->lang;
    }

    public function setHeader($header) {
        $this->header = $header;
    }
}

class LanguageFileParserTest extends TestCase {

    function testGoToStart() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('<?php');
        $parser->goToStart();
        $this->assertEquals('', $parser->getContent());

        $parser->setContent('some Text<?php');
        $parser->goToStart();
        $this->assertEquals('', $parser->getContent());

        $parser->setContent('<?phpa');
        $parser->goToStart();
        $this->assertEquals('a', $parser->getContent());

        $parser->setContent('<?php<?php');
        $parser->goToStart();
        $this->assertEquals('<?php', $parser->getContent());
    }

    function testDetermineNextMode() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('/* Bla bla */');
        $this->assertEquals(LanguageFileParser::$MODE_COMMENT_MULTI_LINE, $parser->determineNextMode());
        $this->assertEquals(' Bla bla */', $parser->getContent());

        $parser->setContent('// Bla');
        $this->assertEquals(LanguageFileParser::$MODE_COMMENT_SINGLE_LINE, $parser->determineNextMode());
        $this->assertEquals(' Bla', $parser->getContent());

        $parser->setContent('$lang["some"] = "text";');
        $this->assertEquals(LanguageFileParser::$MODE_LANG, $parser->determineNextMode());
        $this->assertEquals('"some"] = "text";', $parser->getContent());

        $parser->setContent('$lang[\'js\']["some"] = "text";');
        $this->assertEquals(LanguageFileParser::$MODE_LANG, $parser->determineNextMode());
        $this->assertEquals('\'js\']["some"] = "text";', $parser->getContent());

        $parser->setContent('echo "bla";');
        $this->assertEquals(LanguageFileParser::$MODE_PHP_UNKNOWN, $parser->determineNextMode());
        $this->assertEquals('echo "bla";', $parser->getContent());
    }

    function testIssue52() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setContent('# Bla');
        $this->assertEquals(LanguageFileParser::$MODE_COMMENT_SINGLE_LINE, $parser->determineNextMode());
        $this->assertEquals(' Bla', $parser->getContent());

    }

    function testProcessMultiLineComment() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent("some text\n * @var string some text\n   * @author some one üß <email.address@someone>\n*/");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('some one üß', 'email.address@someone'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n", $parser->getHeader());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some more text\n   * @author any one üß <email.address@anyone>\n* @author an other <email.some@bla>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('any one üß', 'email.address@anyone'));
        $expected->add(new Author('an other', 'email.some@bla'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
       // $this->assertEquals(" * some text\n * @var string some more text\n", $parser->getHeader());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some text\n   * @author one <one@example.com>\n* @author one <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some text\n   * @author two <one@example.com>\n* @author one <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('two', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n", $parser->getHeader());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some text\n   * @author one <one@example.com>\n* @author two <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n", $parser->getHeader());


        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some text\n * \n * \n\n\nect\n*/");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $this->assertEquals(new AuthorList(), $parser->getAuthor());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n *\n * ect\n", $parser->getHeader());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("some text\n * @var string some text\n   * @author one <one@example.com>\n* @author two <one@example.com>\n* more lines\n*\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n * more lines\n *\n", $parser->getHeader());

        $parser->setAuthor(new AuthorList());
        $parser->setHeader('');
        $parser->setContent("\n * @author one <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());
        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
        $this->assertEquals("", $parser->getHeader());
    }

    /**
     * handle multiple spaces between @author tag and the name
     */
    function testIssue48() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author one <one@example.com>\n* @author      one <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

    }

    /**
     * handle multiple loosy defined author tags
     */
    function testAuthorWithoutBrackets() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author onlyemail@example.com\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('', 'onlyemail@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

    }

    /**
     * handle multiple loosy defined author tags
     */
    function testAuthorWithoutEmail() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author    Only Name\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('Only Name', ''));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

    }

    /**
     * handle multiple loosy defined author tags
     */
    function testAuthorOnlyemailinbrackets() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author   <onlybrackets@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('', 'onlybrackets@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

    }

    /**
     * handle multiple loosy defined author tags
     */
    function testAuthorWithoutnameMultiple() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author onlyemail@example.com\n* @author    Only Name\n* @author <onlybrackets@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('', 'onlyemail@example.com'));
        $expected->add(new Author('Only Name', ''));
        $expected->add(new Author('', 'onlybrackets@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());

    }


    /**
     * handle @author with trailing semicolon (however it is invalid phpdocs syntax)
     */
    function testIssue38() {
        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n   * @author: one <one@example.com>\n*/ text");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processMultiLineComment());

        $expected = new AuthorList();
        $expected->add(new Author('one', 'one@example.com'));
        $this->assertEquals($expected, $parser->getAuthor());
        $this->assertEquals(' text', $parser->getContent());
        $this->assertEquals(" * some text\n * @var string some text\n", $parser->getHeader());


    }

    function testProcessMultiLineCommentParserException() {
        $this->expectException(LanguageParseException::class);

        $parser = new LanguageFileParserTestDummy();
        $parser->setAuthor(new AuthorList());
        $parser->setContent("some text\n * @var string some text\n\n");
        $parser->processMultiLineComment();
    }

    function testProcessSingleLineComment() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent(" hello you");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processSingleLineComment());
        $this->assertEquals('', $parser->getContent());

        $parser->setContent(" hello you\nmore php code");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processSingleLineComment());
        $this->assertEquals('more php code', $parser->getContent());
    }

    function testGetFirstString() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Hello"');
        $this->assertEquals('Hello', $parser->getFirstString());
        $this->assertEquals('', $parser->getContent());

        $parser->setContent('"Hello" some other meaningful stuff');
        $this->assertEquals('Hello', $parser->getFirstString());
        $this->assertEquals(' some other meaningful stuff', $parser->getContent());

        $parser->setContent("'Hello'");
        $this->assertEquals('Hello', $parser->getFirstString());
        $this->assertEquals('', $parser->getContent());

        $parser->setContent('"\""');
        $this->assertEquals('"', $parser->getFirstString());
        $this->assertEquals('', $parser->getContent());

        $parser->setContent("'\\''");
        $this->assertEquals("'", $parser->getFirstString());
        $this->assertEquals('', $parser->getContent());

        $parser->setContent('""');
        $this->assertEquals('', $parser->getFirstString());
        $this->assertEquals('', $parser->getContent());
    }

    function testGetString() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Hello" . \' whats up\'');
        $this->assertEquals('Hello whats up', $parser->getString());
        $this->assertEquals('', $parser->getContent());
    }

    function testGetStringUnknownEnd() {
        $this->expectException(LanguageParseException::class);

        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Hello . \' whats up\'');
        $parser->getString();
    }

    function testProcessLang() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Key"] = "value";');
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processLang());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals('value', $parser->getLangByKey('Key'));

        $parser->setContent("'another']\t =  \n 'value' ;");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processLang());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals('value', $parser->getLangByKey('another'));

        $parser->setContent('"Key"]="value";');
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processLang());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals('value', $parser->getLangByKey('Key'));
    }

    function testProcessJsLang() {
        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('\'js\']["Key"] = "value";');
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processLang());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals(array('Key' => 'value'), $parser->getLangByKey('js'));

        $parser->setContent("'js'  ]\t [\n  \"Key\"] = \"value\";");
        $this->assertEquals(LanguageFileParser::$MODE_PHP, $parser->processLang());
        $this->assertEquals('', $parser->getContent());
        $this->assertEquals(array('Key' => 'value'), $parser->getLangByKey('js'));
    }

    function testProcessLangException() {
        $this->expectException(LanguageParseException::class);

        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Key"] = "value"');
        $parser->processLang();
    }

    function testProcessLangExceptionSyntax() {
        $this->expectException(LanguageParseException::class);

        $parser = new LanguageFileParserTestDummy();

        $parser->setContent('"Key" = "value"');
        $parser->processLang();
    }

    function testCompleteFile() {
        $parser = new LanguageFileParserTestDummy();
        $parser->loadFile(dirname(__FILE__) . '/testLang.php');
        $parser->parse();

        //note: first line is due to second * of opening comment tag /**
        $expectedHeader = ' *
 * german language file
 *
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @package DokuWiki\lang\de\settings
';

        $this->assertEquals($expectedHeader, $parser->getHeader());
        $this->assertCount(20, $parser->getAuthor()->getAll());
        $this->assertCount(268, $parser->getLang());
        $this->assertCount(41, $parser->getLangByKey('js'));
    }

    function testCompleteFileWithClosing() {
        $parser = new LanguageFileParserTestDummy();
        $content = trim(file_get_contents(dirname(__FILE__) . '/testLang.php'));
        $content .= "\n\n?>";
        $parser->setContent($content);
        $parser->parse();

        $this->assertCount(20, $parser->getAuthor()->getAll());
        $this->assertCount(268, $parser->getLang());
        $this->assertCount(41, $parser->getLangByKey('js'));
    }


    function testEscapeSingleQuoted() {
        $parser = new LanguageFileParserTestDummy();

        $this->assertEquals('\'', $parser->escapeString("\\'", '\''));
        $this->assertEquals('\\', $parser->escapeString("\\\\", '\''));
        $this->assertEquals('\\n', $parser->escapeString("\\n", '\''));
    }

    function testEscapeDoubleQuoted() {
        $parser = new LanguageFileParserTestDummy();

        $this->assertEquals("\n", $parser->escapeString('\\n', '"'));
        $this->assertEquals("\r", $parser->escapeString('\\r', '"'));
        $this->assertEquals("\t", $parser->escapeString('\\t', '"'));
        $this->assertEquals("\v", $parser->escapeString('\\v', '"'));
        $this->assertEquals("\e", $parser->escapeString('\\e', '"'));
        $this->assertEquals("\f", $parser->escapeString('\\f', '"'));
        $this->assertEquals("\\", $parser->escapeString('\\\\', '"'));
        $this->assertEquals("$", $parser->escapeString('\\$', '"'));
        $this->assertEquals('"', $parser->escapeString('\\"', '"'));

        $this->assertEquals('A', $parser->escapeString('\\x41', '"'));
        $this->assertEquals('A', $parser->escapeString('\\101', '"'));
    }
}
