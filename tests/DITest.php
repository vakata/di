<?php
namespace vakata\di\test;

interface ITest1
{
	public function f1();
}
interface ITest2
{
	public function f2($a, ITest1 $b);
}
class Class1 implements ITest1
{
	public function f1() { return 1; }
}
class Class2 implements ITest2
{
	public function f2($a, ITest1 $b) { return $a . $b->f1(); }
}
class Class3 implements ITest1, ITest2
{
	protected $a = 0;
	public function __construct($a) { $this->a = $a; }
	public function f1() { return $this->a; }
	public function f2($a, ITest1 $b) { return $a . $this->a; }
}
class Class4
{
}
class Class5
{
	public function __construct($a, ITest1 $b, ITest2 $c, $d) {
		$this->a = $a;
		$this->d = $d;
	}
	public function sum() { return $this->a * 2 + $this->d; }
	public function sum2($a, $b) { return $a + $b; }
}

class DITest extends \PHPUnit\Framework\TestCase
{
	protected static $storage = null;

	public function testCreate() {
		$di = new \vakata\di\DIContainer();
		$di->alias('\vakata\di\test\Class1', ['\vakata\di\test\ITest1']);
		$c2 = new \vakata\di\test\Class2();
		$di->register($c2, false);
        $di->alias('\vakata\di\test\Class2', ['c2'], false);
		$di->defaults('\vakata\di\test\Class3', [3]);
		$di->alias('\vakata\di\test\Class3', ['c3', 'c4']);
		$di->alias('\vakata\di\test\Class2', ['\vakata\di\test\ITest2']);

		$this->assertEquals(true, $di->instance('\vakata\di\test\Class1') instanceof \vakata\di\test\Class1);
		$this->assertEquals(true, $di->instance('\vakata\di\test\ITest1') instanceof \vakata\di\test\Class1);
		$this->assertEquals(true, $di->instance('\vakata\di\test\Class2') instanceof \vakata\di\test\Class2);
		$this->assertEquals(true, $di->instance('c2') instanceof \vakata\di\test\Class2);
		$this->assertEquals(true, $di->instance('c3') instanceof \vakata\di\test\Class3);
		$this->assertEquals(true, $di->instance('c4') instanceof \vakata\di\test\Class3);
		$this->assertEquals(true, $di->instance('\vakata\di\test\Class4') instanceof \vakata\di\test\Class4);
		$this->assertEquals(3, $di->instance('c3')->f1());
		$this->assertEquals(3, $di->invoke('c3', 'f1'));
		$this->assertEquals(true, $di->instance('\vakata\di\test\Class5', [2,3]) instanceof \vakata\di\test\Class5);
		$di->defaults('\vakata\di\test\Class5', ['d'=>2,3]);
		$c5 = $di->instance('\vakata\di\test\Class5');
		$this->assertEquals(7, $c5->sum());
		$this->assertEquals(7, $di->invoke('\vakata\di\test\Class5', 'sum'));
		$this->assertEquals(10, $di->invoke('\vakata\di\test\Class5', 'sum', [], [4,1]));
		$this->assertEquals(3, $di->invoke('\vakata\di\test\Class5', 'sum2', [1,2]));
	}
}
