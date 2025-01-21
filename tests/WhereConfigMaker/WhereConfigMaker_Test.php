<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\DbObjects\WhereConfigMaker\WhereConfigMaker;
use YAPF\Junk\Sets\CounttoonehundoSet;

class WhereConfigMaker_Test extends TestCase
{
    protected function setUp(): void
    {
        global $system;
        $system = new Config();
    }
    public function testOneItem()
    {
        $whereConfigMaker = new WhereConfigMaker();
        $result = $whereConfigMaker->field("testme")->equalTo("yes")->result();
        $this->assertSame(5,count($result),"Incorrect number of entrys");
        $this->assertSame(1,count($result["fields"]),"Incorrect number of entrys in fields");
        $this->assertSame("testme",$result["fields"][0],"field entry is not as expected");
        $this->assertSame(1,count($result["values"]),"Incorrect number of entrys in values");
        $this->assertSame("yes",$result["values"][0],"value entry is not as expected");
        $this->assertSame(1,count($result["types"]),"Incorrect number of entrys in types");
        $this->assertSame("s",$result["types"][0],"types entry is not as expected");
        $this->assertSame(1,count($result["matches"]),"Incorrect number of entrys in matches");
        $this->assertSame("=",$result["matches"][0],"matches entry is not as expected");
        $this->assertSame(0,count($result["joinWith"]),"Incorrect number of entrys in joinWith");
    }
    public function testTwoOptions()
    {
        $whereConfigMaker = new WhereConfigMaker();
        $result = $whereConfigMaker
            ->field("testme")->equalTo("yes")
            ->and()
            ->field("what")->equalTo(4)
            ->result();
        $this->assertSame(5,count($result),"Incorrect number of entrys");
        $this->assertSame(2,count($result["fields"]),"Incorrect number of entrys in fields");
        $this->assertSame("testme",$result["fields"][0],"field entry is not as expected");
        $this->assertSame(2,count($result["values"]),"Incorrect number of entrys in values");
        $this->assertSame("yes",$result["values"][0],"value entry is not as expected");
        $this->assertSame(2,count($result["types"]),"Incorrect number of entrys in types");
        $this->assertSame("s",$result["types"][0],"types entry is not as expected");
        $this->assertSame(2,count($result["matches"]),"Incorrect number of entrys in matches");
        $this->assertSame("=",$result["matches"][0],"matches entry is not as expected");
        $this->assertSame(1,count($result["joinWith"]),"Incorrect number of entrys in joinWith");
        $this->assertSame("AND",$result["joinWith"][0],"joinWith entry is not as expected");
    }
    public function testWithARealObject()
    {
        $whereConfig = new WhereConfigMaker()
            ->field("cvalue")->in([1,2,4,8,16,32,64,128,256,512])
            ->and()
            ->field("id")->in([1,4,8,34])->result();
        $countToSet = new CounttoonehundoSet();
        $load = $countToSet->loadWithConfig($whereConfig);
        $this->assertSame(true,$load->status,"Failed to fetch from database");
        $this->assertSame(
            "SELECT * FROM test.counttoonehundo  WHERE cvalue IN ( ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ) AND id IN ( ? , ? , ? , ? )",
            $countToSet->getLastSql(),
            "Wrong SQL created");
        $this->assertSame(4,$countToSet->getCount(),"wrong amount of objects loaded");
        $this->assertSame(1,$countToSet->getFirst()->_Cvalue,"expected result order not honnored");
    }
    public function testAllChecks()
    {
        $whereConfigMaker = new WhereConfigMaker();
        $result = $whereConfigMaker
            ->field("what1")->equalTo(4)
            ->and()
            ->field("is2")->greaterThan(55)
            ->or()
            ->field("up3")->greaterThanEqualTo(23)
            ->and()
            ->field("with4")->in([42,43,44])
            ->or()
            ->field("the5")->notIn([11,12,13])
            ->and()
            ->field("color6")->isNotNull()
            ->or()
            ->field("orange7")->isNull()
            ->and()
            ->field("just8")->lessThan(60)
            ->or()
            ->field("be9")->lessThanThanEqualTo(55)
            ->and()
            ->field("red10","yellow11")->notEqualTo(123)
            ->result();
        $values = [4,55,23,[42,43,44],[11,12,13],null,null,60,55,123];
        $matches = ["=",">",">=","IN","NOT IN","IS NOT","IS","<","<=","!="];
        $joins = ["AND","OR","AND","OR","AND","OR","AND","OR","AND"];
        $fields = ["what1","is2","up3","with4","the5","color6","orange7","just8","be9","red10 AS 'yellow11'"];
        $loop = 0;
        $this->assertSame(count($values),count($result["values"]),"Incorrect number of entrys in result");
        while($loop < count($values))
        {
            $this->assertSame($fields[$loop],$result["fields"][$loop],"Expected field name on index ".$loop);
            $this->assertSame($matches[$loop],$result["matches"][$loop],"Expected match on index ".$loop);
            if(is_array($values[$loop]) == true)
            {
                if(is_array($result["values"][$loop]) == true)
                {
                    $this->assertSame(count($values[$loop]),count($result["values"][$loop]),"value entry on index ".$loop." array count does not match");
                    $loop2 = 0;
                    while($loop2 < count($values[$loop]))
                    {
                        $this->assertSame(
                            $values[$loop][$loop2],
                            $result["values"][$loop][$loop2],
                            "value entry on index ".$loop." does is not the same on sub index ".$loop2
                        );
                        $loop2++;
                    }
                    
                }
                else
                {
                    $this->assertSame(true,false,"value entry on index ".$loop." is not an array");
                    break;
                }
            }
            else
            {
                $this->assertSame($values[$loop],$result["values"][$loop],"value entry on index ".$loop." does is not the same");
            }
            $loop++;
        }
        $loop = 0;
        while($loop < count($joins))
        {
            $this->assertSame($joins[$loop],$result["joinWith"][$loop],"Expected join on index ".$loop);
            $loop++;
        }
    }
}
