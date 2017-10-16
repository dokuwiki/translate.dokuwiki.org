<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class AuthorListTest extends \PHPUnit_Framework_TestCase {

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

    function testAddDifferentNameandEmail() {
        $list = new AuthorList();
        $list->add(new Author('name', 'email'));
        $list->add(new Author('name1', 'email1'));
        $this->assertEquals(2, count($list->getAll()));
        $this->assertTrue($list->has(new Author('name', 'email')));
        $this->assertTrue($list->has(new Author('name1', 'email1')));
    }
}