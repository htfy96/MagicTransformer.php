# MagicTransformer.php
Bidirectional array-like struct mapper in PHP. 

[中文](https://github.com/htfy96/MagicTransformer.php/tree/cn)

## Initiative
Model transformation is common in PHP project. Traditional way is to write `A::transformToB` and `A::setFromB` method. However in most cases the logic behind these two methods is almost the same, except that one constructs object and the other extract information from object.

Then comes this library: define transformation once, use it bidirectionally.

## Usage
```php
require_once 'magic_transformer.php';
use MagicTransform\M as M;
```

A mapper is an object that implments `MagicTransform\iBidirectionTrans`:

```php
interface iBidirectionTrans {
    public function forward_map($left_val);
    public function reverse_map($right_val, &$left_obj);
}
```

```php
//Define transformation
$trans = M::make_mapper(
            [
                'abc' => M::$self[0], // the value of 'abc' is $left[0]
                'ccc' => M::make_chain(
                    M::$self[0], // The 2nd argument(which is a mapper) will receive $left[1] as $left
                    [
                        'yyy' => M::$self // Use self to reference $left
                    ]
                ),
                'ddd' => M::self['eee']['fff'], // to reference $left['eee']['fff']
                'eee' => M::make_chain(
                    M::$self[2], // now $left(in make_list_mapper) is $left[2]
                    M::make_list_mapper( // Apply the mapper to each item of list
                        M::make_func_mapper( // customize mapper!
                            function($left_val) { // forward
                                return $left_val + 1;
                            },
                            function ($right_val, &$left_obj) {
                                $left_obj = $right_val - 1;
                            }
                        )
                    )
                )
            ]
        );

$left = ['0th', '1st', [1,2,3], 'eee' => ['fff' => 4]];

$right = $trans->forward_map($left);
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

// Now let's modify $right

$right['abc'] = '0th0th';
$right['ccc']['yyy'] = ['1st1st'];
$right['ddd'] = 100;
$right['eee'][1] = 7;

$left = array(); // You can use your ORM here!
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

And that's all!

## API

All following API is in `MagicTransform` namespace.
### Interface
#### iBidirectionTrans
```php
interface iBidirectionTrans {
    public function forward_map($left_val);
    public function reverse_map($right_val, &$left_obj);
}
```

All mapper shall implement this interface.

### Mapper/mapper maker
All mapper maker is defined in class `M`:
#### make_key_mapper
maps `$left[$key1][$key2]...`

```php
M::make_key_mapper('abc', 'def'); //$left['abc']['def']
```

#### __0/__1/__2/__3
alias of make_key_mapper(0), ..., make_key_mapper(3)

#### self
A mapper which always returns itself and never modify left value when called.

Magic function [] overloaded as sugar of `make_key_mapper`

#### make_chain
Map in order Arg1, Arg2, ..., ArgN

```php
M::make_chain(M::$__0, M::$__1); // $left[0][1], which is equivalent to make_key_mapper(0, 1)
```

#### make_list_mapper
accept a mapper and apply it to each item of $left

```php
M::make_list_mapper(M::$__0); //[$left[0][0], $left[1][0], ...]
```

#### make__func_mapper
Customize your own mapper. The first argument is forward mapper and the second is reverse mapper.

```php
M::make_func_mapper( // customize mapper!
                    function($left_val) { // forward
                        return $left_val + 1;
                    },
                    function ($right_val, &$left_obj) {
                        $left_obj = $right_val - 1;
                    }
                ); // $left + 1
```

#### make_mapper
Transform a simple array to mapper automaticly.

Transformation should be defined with this method.

```php
make_mapper(
            [
                'aaa' => M::$__0
            ]
        );
```

## License

Apache License.
