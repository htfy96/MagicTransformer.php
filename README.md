# MagicTransformer.php
PHP的双向映射模型

[English Version](https://github.com/htfy96/MagicTransformer.php/tree/master)

## 设计动机
在PHP开发之中，把一种模型变化到另一种模型是非常常见的操作。传统的方式是写两个`transformToB`和`setFromB`方法。然而，在很多时候我们发现其中的逻辑基本是相同的，只不过一个构造对象，一个从对象中析出信息。

因此就有了这个库：变换模型一次定义，双向使用。

## 用法
```php
require_once 'magic_transformer.php';
use MagicTransform\M as M;
```

一个映射器（mapper）就是实现 `MagicTransform\iBidirectionTrans`类的实例。

```php
interface iBidirectionTrans {
    public function forward_map($left_val); //正向映射
    public function reverse_map($right_val, &$left_obj); //逆向映射
}
```

```php
//定义映射关系
$trans = M::make_mapper(
            [
                'abc' => M::$__0, // 'abc'对应的键值就是$left[0]
                'ccc' => M::make_chain( // 正向映射时，将第一个映射器的值传给第二个；反向则相反
                    M::$__1, // make_chain的第二个参数接收到$left是这里的$left[1]
                    [
                        'yyy' => M::$__self // __self是一个始终将自身映射到自身的映射器
                    ]
                ),
                'ddd' => M::make_key_mapper('eee', 'fff'), // 正向映射$left['eee']['fff'], 反向则设置$left['eee']['fff']
                'eee' => M::make_chain(
                    M::$__2, // 现在make_list_mapper所接受到到$left是这里的$left[2]
                    M::make_list_mapper( // 将参数的mapper作用到$left的每一项上，反向则先获取每一项再构造出一个数组
                        M::make_func_mapper( // 自定义映射器！
                            function($left_val) { // 正向
                                return $left_val + 1;
                            },
                            function ($right_val, &$left_obj) { // 反向
                                $left_obj = $right_val - 1;
                            }
                        )
                    )
                )
            ]
        );

$left = ['0th', '1st', [1,2,3], 'eee' => ['fff' => 4]];

$right = $trans->forward_map($left); // 正向映射
print_r($right);

/*
 * Array
(
    [abc] => 0th
    [ccc] => Array
        (
            [yyy] => 1st
        )

    [ddd] => 4
    [eee] => Array
        (
            [0] => 2
            [1] => 3
            [2] => 4
        )
 */

// 我们来修改一下$right

$right['abc'] = '0th0th';
$right['ccc']['yyy'] = ['1st1st'];
$right['ddd'] = 100;
$right['eee'][1] = 7;

$left = array(); // 这里你可以传一个自己的ORM对象进来
$trans->reverse_map($right, $left);

print_r($left);
/*
Array
(
    [0] => 0th0th
    [1] => Array
        (
            [0] => 1st1st
        )

    [eee] => Array
        (
            [fff] => 100
        )

    [2] => Array
        (
            [0] => 1
            [1] => 6
            [2] => 3
        )

)
*/
```


## API

所有的API都定义在`MagicTransform`命名空间下

### Interface
#### iBidirectionTrans
```php
interface iBidirectionTrans {
    public function forward_map($left_val);
    public function reverse_map($right_val, &$left_obj);
}
```

所有mapper都应该实现这个接口。

### Mapper与mapper构造辅助函数
所有的这些都在`M`类中被定义。

#### make_key_mapper
映射`$left[$key1][$key2]...`

```php
M::make_key_mapper('abc', 'def'); //$left['abc']['def']
```

#### __0/__1/__2/__3
make_key_mapper(0), ..., make_key_mapper(3) 的别名

#### make_chain
参数是映射器。

正向分别按Arg1, Arg2, ..., ArgN的顺序映射,将前一个输出作为后一个输入；反向则逆序。

```php
M::make_chain(M::$__0, M::$__1); // $left[0][1], 和make_key_mapper(0, 1)等价
```

#### make_list_mapper
将mapper作用到$left这个数组的每一项上

```php
M::make_list_mapper(M::$__0); //[$left[0][0], $left[1][0], ...]
```

#### make__func_mapper
自定义映射器，第一个参数是正向，第二个是反向。

```php
M::make_func_mapper( // 自定义
                    function($left_val) { // 正向
                        return $left_val + 1;
                    },
                    function ($right_val, &$left_obj) {
                        $left_obj = $right_val - 1;
                    }
                ); // $left + 1
```

#### make_mapper
自动将一个对象/数组等转化为映射器。

```php
make_mapper(
            [
                'aaa' => M::$__0
            ]
        );
```

## 许可证

Apache License.
