<?php

namespace Reactor\ABDriver;

class ABDriver {
    
    public $common_factors = array();
    public $tests = array();
    public $event_prefix;
    public $random_factor = null;
    protected $dispatcher = array();

    public function __construct($dispatcher, $exchange, $event_prefix = 'ab_test') {
        $this->dispatcher = $dispatcher;
        $this->exchange = $exchange;
        $session_prefix = 'ab-'.$event_prefix;
        if (!isset($_COOKIE[$session_prefix])) {
            setcookie($session_prefix, rand(1, 1000), time()+60*60*24*30);
        }
        $this->random_factor = (int)$_COOKIE[$session_prefix];
        if (!isset($_SESSION[$session_prefix])) {
            $_SESSION[$session_prefix] = array(
                'tests' => array(),
                'common_factors' => array(),
            );
        }
        $this->tests            = &$_SESSION[$session_prefix]['tests'];
        $this->common_factors   = &$_SESSION[$session_prefix]['common_factors'];
    }

    public function initUtm($get) {
        $tags = array('utm_source', 'utm_medium', 'utm_term', 'utm_content', 'utm_campaign');
        foreach ($tags as $tag) {
            if (isset($get[$tag])) {
                $this->common_factors[$tag] = $get[$tag];
            }
        }
    }

    public function startTest($test_name, $factors = array(), $total_variants = 2) {
        if (!isset($this->tests[$test_name])) {
            $variant = abs($this->random_factor + crc32($test_name) / 2) % $total_variants;
            $this->tests[$test_name] = array(
                'variant' => $variant,
                'factors' => $factors,
            );
        }
        return $this->tests[$test_name]['variant'];
    }

    public function getVariant($test_name) {
        if (isset($this->tests[$test_name])) {
            return $this->tests[$test_name]['variant'];
        }
        return null;
    }

    public function goal($test_name, $goal_name = 'goal', $factors = array()) {
        if (isset($this->tests[$test_name])) {
            $test = $this->tests[$test_name];
            $this->registerEvent($test_name, $goal_name, $test['variant'], array_merge($test['factors'], $factors));
            return $this->tests[$test_name]['variant'];
        }
        return null;
    }

    public function superGoal($goal_name = 'goal', $factors = array()) {
        foreach ($this->tests as $key => $value) {
            $this->goal($key, $goal_name, $factors);
        }
    }

    public function registerEvent($test_name, $goal_name, $variant, $factors = array()) {
        $event = array(
            'test_name' => $test_name,
            'goal'      => $goal_name,
            'variant'   => $variant,
            'factors'   => array_merge($this->common_factors, $factors),
        );
        $this->dispatcher->publish($this->exchange, $this->event_prefix, $event);
    }

}
