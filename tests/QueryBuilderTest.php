<?php
use PHPUnit\Framework\TestCase;

class tests_Estate_Service_QueryBuilderTest extends TestCase {
    /**
     * @dataProvider dataEscaping
    **/
    public function testEscaping($query, $arg, $expected) {
        $builder = new SD\QueryBuilder\QueryBuilder();
        $builder->addClause($query, $arg);
        $this->assertEquals($expected, $builder->getQuery());
    }

    public function dataEscaping() {
        return [
            [
                'query' => 'SELECT * FROM wpposts WHERE ids = %s',
                'arg' => "anything' OR 'x'='x",
                'expected' => "SELECT * FROM wpposts WHERE ids = 'anything\\' OR \\'x\\'=\\'x'",
            ]
        ];
    }
}
