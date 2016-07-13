<?php

require_once 'magic_transformer.php';
use MagicTransform\M as M;

$model = M::make_mapper([
    'abc' => M::$__0,
    'ccc' => 
    [
        'xxx' => M::make_chain(M::$__1,
        [
            'yyy' => M::make_chain([M::$__0, 234], M::$__0),
            'zzz' => M::$__1
        ])
    ],
    'ddd' => M::make_key_mapper('eee', 'fff') ,
    'eee' => M::make_chain(M::$__2,
    M::make_list_mapper(
        M::make_func_mapper(
            function($left_val) {
                return $left_val + 1;
            },
            function ($right_val, &$left_obj) {
                $left_obj = $right_val - 1;
            }
        )
    ))
]);

$right = $model->forward_map([123, [333,666], [1,2,3], 'eee' => ['fff' => 9]]);

assert($right['abc'] == 123);
assert($right['ccc']['xxx']['yyy'] == 333);
assert($right_val['ccc']['xxx']['zzz'] == 666);
assert($right['ddd'] == 999);

print_r($right);

$right['abc'] = 789;
$right['ccc']['xxx']['yyy']=222;
$left = [];
$model->reverse_map($right, $left);
assert(left[0] == 789);
assert(left[1][1] == 666);

print_r($left);

//Define transformation
$trans = M::make_mapper(
    [
        'abc' => M::$__0, // the value of 'abc' is $left[0]
        'ccc' => M::make_chain(
            M::$__1, // The 2nd argument(which is a mapper) will receive $left[1] as $left
            [
                'yyy' => M::$__self // Use __self to reference $left
            ]
        ),
        'ddd' => M::make_key_mapper('eee', 'fff'), // to reference $left['eee']['fff']
        'eee' => M::make_chain(
            M::$__2, // now $left(in make_list_mapper) is $left[2]
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
echo '<pre>';
print_r($right);
echo '</pre>';



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
echo '<pre>';
print_r($left);
echo '</pre>';

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