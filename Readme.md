Readme.md


```php

// Somewhere in core
$_ab = new \Reactor\ABDriver\ABDriver($dispatcher, 'exchange-name');
$_ab->initUtm($_GET);


// Good card
$variant = $_ab->startTest('good_cart_buy_button');

// if needed
$variant = $_ab->goal('good_cart_buy_button', 'shown', array('pk_good' => $pk_good));


// in template
$variant = $_ab->getVariant('good_cart_buy_button');

if ($variant == 0) {
    echo "variant 0";
} else {
    echo "variant 1";
}


//------------------------------------------------------
// Good list
$variant = $_ab->startTest('good_list_onsale_tag');

// if needed
$variant = $_ab->goal('good_list_onsale_tag', 'shown', array('pk_tree' => $pk_tree));


// in template
$variant = $_ab->getVariant('good_list_onsale_tag');

if ($variant == 0) {
    echo "variant 0";
} else {
    echo "variant 1";
}

//------------------------------------------------------
// Checkout handler
$_ab->superGoal('checkout');

// Order handler
$_ab->superGoal('order');


```
