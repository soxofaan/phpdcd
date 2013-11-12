<?php
/**
 * Created by PhpStorm.
 * User: stefaan
 * Date: 11/11/13
 * Time: 23:42
 */

namespace SebastianBergmann\PHPDCD\Log;


class HtmlReportTest extends \PHPUnit_Framework_TestCase
{

    public function testPathJoinBasic()
    {
        $report = new HtmlReport();
        $result = $report->pathJoin('foo', 'bar/', 'baz.txt');
        $this->assertEquals('foo/bar/baz.txt', $result);
    }
    
} 