<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\InputFilter\InputFilter as inputFilter;

class inputFilter_bool_test extends TestCase
{
    protected $_testingobject;
    protected function setUp(): void
    {
        $this->_testingobject = new inputFilter();
    }
    public function test_bool_notset()
    {
        $results1 = $this->_testingobject->getFilter("popcorn", "bool");
        $this->assertSame($results1, null);
        $results2 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results2, "No get value found with name: popcorn");
    }
    public function test_bool_empty()
    {
        $_GET["popcorn2"] = "";
        $results1 = $this->_testingobject->getFilter("popcorn2", "bool");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "is empty");
    }
    public function test_bool_set()
    {
        $_GET["popcorn3"] = "true";
        $results1 = $this->_testingobject->getFilter("popcorn3", "bool");
        $this->assertSame($results1, true);
        $results2 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results2, "");
        $_GET["popcorn2"] = "0";
        $results1 = $this->_testingobject->getFilter("popcorn2", "bool");
        $this->assertSame($results1, false);
        $results2 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results2, "");
        $_GET["popcorn1"] = "yes";
        $results1 = $this->_testingobject->getFilter("popcorn1", "bool");
        $this->assertSame($results1, true);
        $results2 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results2, "");
        $_GET["popcorn4"] = 0;
        $results1 = $this->_testingobject->getFilter("popcorn4", "bool");
        $this->assertSame($results1, false);
        $results2 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results2, "");
    }
    public function test_bool_invaild()
    {
        $_GET["popcorn4"] = new inputFilter();
        $results1 = $this->_testingobject->getFilter("popcorn4", "bool");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "is a object");
    }
}
