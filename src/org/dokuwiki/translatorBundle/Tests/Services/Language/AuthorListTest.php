<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use PHPUnit\Framework\TestCase;

class AuthorListTest extends TestCase {

    function testAdd() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $this->assertEquals(1, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
    }

    function testAddDuplicate() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $list->add(new Author('name', 'email'));
        $this->assertEquals(1, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
    }

    function testAddSameName() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $list->add(new Author('name', 'email1'));
        $this->assertEquals(1, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
    }

    function testAddSameEmail() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $list->add(new Author('name1', 'email'));
        $this->assertEquals(1, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
    }

    function testAddDifferentNameAndEmail() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $list->add(new Author('name1', 'email1'));
        $this->assertEquals(2, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
        $this->assertTrue($list->has(new Author('name1', 'email1')));
    }

    function testAddEmptyNameOnlyEmails() {
        $list = new AuthorList();
        $list->add(new Author('', 'email1'));
        $list->add(new Author('', 'email2'));
        $this->assertEquals(2, count($list->getAll()));
        $this->assertTrue($list->has(new Author('', 'email1')));
        $this->assertTrue($list->has(new Author('', 'email2')));
    }

    function testAddDifferentNamesEmptyEmails() {
        $list = new AuthorList();
        $list->add(new Author('name1', ''));
        $list->add(new Author('name2', ''));
        $this->assertEquals(2, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name1', '')));
        $this->assertTrue($list->has(new Author('name2', '')));
    }
}
