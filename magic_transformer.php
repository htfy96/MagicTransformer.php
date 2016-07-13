<?php
namespace MagicTransform;
interface iBidirectionTrans {
    public function forward_map($left_val);
    public function reverse_map($right_val, &$left_obj);
}

class ConstMapper implements iBidirectionTrans {
    private $c;
    public function __construct($pC) {
        $this->c = $pC;
    }
    public function forward_map($left_val) {
        return $this->c;
    }
    public function reverse_map($right_val, &$left_obj) {
    }
}

class SelfMapper implements iBidirectionTrans {
    public function forward_map($left_val) {
        return $left_val;
    }

    public function reverse_map($right_val, &$left_obj) {
        $left_obj = $right_val;
    }
}

class KeyMapper implements iBidirectionTrans {
    private $pos;
    public function __construct($pos) {
        $this->pos = $pos;
    }
    public function forward_map($left_val) {
        return $left_val[$this->pos];
    }
    public function reverse_map($right_val, &$left_obj) 
    {
        $left_obj[$this->pos] = $right_val;
    }
}

class ArrayMapper implements iBidirectionTrans {
    private $arr;
    public function __construct($arr) {
        foreach ($arr as &$value) 
        {
            $value = M::make_mapper($value);
        }
        $this->arr = $arr;
    }
    public function forward_map($left_val) {
        return array_map(function($mapper) use ($left_val) {
            return $mapper->forward_map($left_val);
        }, $this->arr);
    }
    public function reverse_map($right_val, &$left_obj) {
        foreach ($this->arr as $key => $value) {
            if (!isset($right_val[$key]))
                error_log("Key $key not found in right");
            $value->reverse_map($right_val[$key], $left_obj);
        }
    }
}

class ListMapper implements iBidirectionTrans {
    private $mapper;
    public function __construct($f) {
        $this->mapper = M::make_mapper($f);
    }

    public function forward_map($left_val) {
        return array_map(function($obj) {
            return $this->mapper->forward_map($obj);
        }, $left_val);
    }

    public function reverse_map($right_val, &$left_obj) {
        foreach($right_val as $key => $value) {
            $this->mapper->reverse_map($value, $left_obj[$key]);
        }
    }
}

class FuncMapper implements iBidirectionTrans {
    private $forward_func, $reverse_func;
    public function __construct($ff, $rf) {
        $this->forward_func = $ff;
        $this->reverse_func = $rf;
    }

    public function forward_map($left_val) {
        return ($this->forward_func)($left_val);
    }

    public function reverse_map($right_val, &$left_obj) {
        return ($this->reverse_func)($right_val, $left_obj);
    }
}

class ChainMapper implements iBidirectionTrans {
    private $mapper1, $mapper2, $initVal;
    static private function omniCopy($old) {
        if (is_object($old))
            return clone $old;
        else
            return $old;
    }

    public function __construct($pMapper1, $pMapper2, $initVal = array()) {
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
        $this->mapper1->reverse_map($initVal, $left_obj);
    }
}

class M
{
    static public function make_mapper($obj)
    {
        if (is_subclass_of($obj, "MagicTransform\iBidirectionTrans"))
            return $obj;

        if (is_array($obj))
            return new ArrayMapper($obj);

        return new ConstMapper($obj);
    }

    static public function make_key_mapper() {
        $numargs = func_num_args();
        assert($numargs > 0);
        if ($numargs == 1)
            return new KeyMapper(func_get_arg(0));
        $args_list = array_map(function($key) {
            return new KeyMapper($key);
        }, func_get_args());
        return 
            call_user_func_array("MagicTransform\M::make_chain", $args_list);
    }

    static public function make_chain() {
        $numargs = func_num_args();
        assert($numargs > 1);
        $args_list = func_get_args();
        $cur = $args_list[0];
        for($i = 1; $i < $numargs; $i++) {
            $cur = new ChainMapper($cur, $args_list[$i]);
        }
        return $cur;
    }


    static public function make_list_mapper($mapper) {
        return new ListMapper($mapper);
    }

    static public function make_func_mapper($ff, $rf) {
        return new FuncMapper($ff, $rf);
    }

    static public $__0, $__1, $__2, $__3, $__self;
}

M::$__0 = new KeyMapper(0);
M::$__1 = new KeyMapper(1);
M::$__2 = new KeyMapper(2);
M::$__3 = new KeyMapper(3);
M::$__self = new SelfMapper();
