<?php

namespace YAPF\Framework\DbObjects\WhereConfigMaker;

class MakerJoinOptions
{
    public function __construct(protected WhereConfigMaker $master)
    {
        
    }
    public function and(): WhereConfigMaker
    {
        $this->master->whereConfig["joinWith"][] = "AND";
        return $this->master;
    }
    public function or(): WhereConfigMaker
    {
        $this->master->whereConfig["joinWith"][] = "OR";
        return $this->master;
    }
    public function result(): array
    {
        return $this->master->whereConfig;
    }

}