<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\InputFilter\InputFilter as inputFilter;

class inputFilter_integer_test extends TestCase
{
    protected ?inputFilter $_testingobject;
    protected function setUp(): void
    {
        $this->_testingobject = new inputFilter();
    }
    public function test_integer_notset()
    {
        $results1 = $this->_testingobject->getFilter("popcorn", "integer");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "No get value found with name: popcorn");
    }
    public function test_integer_empty()
    {
        $_GET["popcorn2"] = "";
        $results1 = $this->_testingobject->getFilter("popcorn2", "integer");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "is empty");
    }
    public function test_integer_set()
    {
        $_GET["popcorn2"] = "5";
        $results1 = $this->_testingobject->getFilter("popcorn2", "integer");
        $this->assertSame($results1, 5);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "");
    }
    public function test_integer_invaild()
    {
        $_GET["popcorn4"] = new inputFilter();
        $results1 = $this->_testingobject->getFilter("popcorn4", "integer");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "is a object");
        $_GET["popcorn4"] = "ten";
        $results1 = $this->_testingobject->getFilter("popcorn4", "integer");
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "not numeric");
    }
    public function test_integer_zeroChecks()
    {
        $_GET["popcorn5"] = "0";
        $results1 = $this->_testingobject->getFilter("popcorn5", "integer", array("zeroCheck" => true));
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "Zero value detected");
        $_GET["popcorn6"] = "22";
        $results1 = $this->_testingobject->getFilter("popcorn6", "integer", array("zeroCheck" => true));
        $this->assertSame($results1, 22);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "");
    }
    public function test_integer_gtr_zero()
    {
        $_GET["popcorn7"] = "-22";
        $results1 = $this->_testingobject->getFilter("popcorn7", "integer", array("gtr0" => true));
        $this->assertSame($results1, null);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "Value must be more than zero");
        $_GET["popcorn8"] = "22";
        $results1 = $this->_testingobject->getFilter("popcorn8", "integer", array("gtr0" => true));
        $this->assertSame($results1, 22);
        $results1 = $this->_testingobject->getWhyFailed();
        $this->assertSame($results1, "");
    }

    public function test_integer_via_get_post()
    {
        $_GET["popcorn2"] = "5";
        $results1 = $this->_testingobject->getInteger("popcorn2");
        $this->assertSame($results1, 5);

        $_POST["popcorn3"] = "77";
        $results1 = $this->_testingobject->postInteger("popcorn3");
        $this->assertSame($results1, 77);
    }
}
