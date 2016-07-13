<?php
namespace MagicTransform;
interface iBidirectionTrans
{
    public function forward_map($left_val);

    public function reverse_map($right_val, &$left_obj);
}

class CommonMagics implements \ArrayAccess
{
    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetSet($offset, $value)
    {
        error_log("Does not support array assign");
    }

    public function offsetGet($offset)
    {
        return new ChainMapper(
            $this,
            new KeyMapper($offset)
        );
    }

    public function offsetUnset($offset)
    {
        error_log("Does not support offset unset");
    }

}

class ConstMapper implements iBidirectionTrans
{
    private $c;

    public function __construct($pC)
    {
        $this->c = $pC;
    }

    public function forward_map($left_val)
    {
        return $this->c;
    }

    public function reverse_map($right_val, &$left_obj)
    {
    }
}

class SelfMapper extends CommonMagics implements iBidirectionTrans
{
    public function forward_map($left_val)
    {
        return $left_val;
    }

    public function reverse_map($right_val, &$left_obj)
    {
        $left_obj = array_merge((array) $left_obj, (array)($right_val));
    }
}

class KeyMapper extends CommonMagics implements iBidirectionTrans
{
    private $pos;

    public function __construct($pos)
    {
        $this->pos = $pos;
    }

    public function forward_map($left_val)
    {
        return $left_val[$this->pos];
    }

    public function reverse_map($right_val, &$left_obj)
    {
        $left_obj[$this->pos] = $right_val;
    }
}

class ArrayMapper extends CommonMagics implements iBidirectionTrans
{
    private $arr;

    public function __construct($arr)
    {
        foreach ($arr as &$value) {
            $value = M::make_mapper($value);
        }
        $this->arr = $arr;
    }

    public function forward_map($left_val)
    {
        return array_map(function ($mapper) use ($left_val) {
            return $mapper->forward_map($left_val);
        }, $this->arr);
    }

    public function reverse_map($right_val, &$left_obj)
    {
        foreach ($this->arr as $key => $value) {
            if (!isset($right_val[$key]))
                error_log("Key $key not found in right");
            $value->reverse_map($right_val[$key], $left_obj);
            error_log('rev_map');
            error_log(print_r($left_obj, true));
        }
    }
}

class ListMapper implements iBidirectionTrans
{
    private $mapper;

    public function __construct($f)
    {
        $this->mapper = M::make_mapper($f);
    }

    public function forward_map($left_val)
    {
        return array_map(function ($obj) {
            return $this->mapper->forward_map($obj);
        }, $left_val);
    }

    public function reverse_map($right_val, &$left_obj)
    {
        foreach ($right_val as $key => $value) {
            $this->mapper->reverse_map($value, $left_obj[$key]);
        }
    }
}

class FuncMapper implements iBidirectionTrans
{
    private $forward_func, $reverse_func;

    public function __construct($ff, $rf)
    {
        $this->forward_func = $ff;
        $this->reverse_func = $rf;
    }

    public function forward_map($left_val)
    {
        return ($this->forward_func)($left_val);
    }

    public function reverse_map($right_val, &$left_obj)
    {
        return ($this->reverse_func)($right_val, $left_obj);
    }
}

class ChainMapper extends CommonMagics implements iBidirectionTrans
{
    private $mapper1, $mapper2, $initVal;

    public function __construct($pMapper1, $pMapper2, $initVal = array())
    {
        $this->mapper1 = M::make_mapper($pMapper1);
        $this->mapper2 = M::make_mapper($pMapper2);
        $this->initVal = $initVal;
    }

    public function forward_map($left_val)
    {
        return $this->mapper2->forward_map(
            $this->mapper1->forward_map($left_val)
        );
    }

    public function reverse_map($right_val, &$left_obj)
    { // D*** crazy side effects
        $initVal = self::omniCopy($this->initVal);
        $this->mapper2->reverse_map($right_val, $initVal);
        error_log(print_r($initVal, true));
        $this->mapper1->reverse_map($initVal, $left_obj);
        error_log(print_r($left_obj, true));
    }

    static private function omniCopy($old)
    {
        if (is_object($old))
            return clone $old;
        else
            return $old;
    }
}

class M
{
    static public $__0, $__1, $__2, $__3, $self;

    static public function make_mapper($obj)
    {
        if (is_subclass_of($obj, "MagicTransform\iBidirectionTrans"))
            return $obj;

        if (is_array($obj))
            return new ArrayMapper($obj);

        return new ConstMapper($obj);
    }

    static public function make_key_mapper()
    {
        $numargs = func_num_args();
        assert($numargs > 0);
        if ($numargs == 1)
            return new KeyMapper(func_get_arg(0));
        $args_list = array_map(function ($key) {
            return new KeyMapper($key);
        }, func_get_args());
        return
            call_user_func_array("MagicTransform\M::make_chain", $args_list);
    }

    static public function make_chain()
    {
        $numargs = func_num_args();
        assert($numargs > 1);
        $args_list = func_get_args();
        $cur = $args_list[0];
        for ($i = 1; $i < $numargs; $i++) {
            $cur = new ChainMapper($cur, $args_list[$i]);
        }
        return $cur;
    }

    static public function make_list_mapper($mapper)
    {
        return new ListMapper($mapper);
    }

    static public function make_func_mapper($ff, $rf)
    {
        return new FuncMapper($ff, $rf);
    }
}

M::$__0 = new KeyMapper(0);
M::$__1 = new KeyMapper(1);
M::$__2 = new KeyMapper(2);
M::$__3 = new KeyMapper(3);
M::$self = new SelfMapper();
