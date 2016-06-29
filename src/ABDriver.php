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
            $rnd_factor = rand(1, 1000);
            setcookie($session_prefix, $rnd_factor, time()+60*60*24*30);
            $this->random_factor = $rnd_factor;
        } else {
            $this->random_factor = (int)$_COOKIE[$session_prefix];    
        }
        if (!isset($_SESSION[$session_prefix])) {
            $_SESSION[$session_prefix] = array(
                'tests'             => array(),
                'common_factors'    => array(
                    'referer'           => $_SERVER['HTTP_REFERER'],
                    'random_factor'     => $this->random_factor,
                    'session'           => session_id(),
                ),
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

    public function startTest($test_name, $variants = null, $factors = array()) {
        if ($variants === null) {
            $variants = array('A', 'B');
        }
        if (!isset($this->tests[$test_name])) {
            $variant_id = abs($this->random_factor + crc32($test_name) / 2) % count($variants);
            $this->tests[$test_name] = array(
                'variant_id'    => $variant_id,
                'factors'       => $factors,
                'variants'      => $variants,
            );
        }
    }

    public function getVariantId($test_name) {
        if (isset($this->tests[$test_name])) {
            return $this->tests[$test_name]['variant_id'];
        }
        return null;
    }

    public function getVariantName($test_name) {
        if (isset($this->tests[$test_name])) {
            $test = $this->tests[$test_name];
            return $test['variants'][$this->getVariantId($test_name)];
        }
        return null;
    }

    public function goal($test_name, $goal_name = 'goal', $factors = array()) {
        if (isset($this->tests[$test_name])) {
            $test = $this->tests[$test_name];
            $this->registerEvent(
                $test_name,
                $goal_name,
                $this->getVariantName($test_name),
                array_merge($test['factors'], $factors)
            );
        }
    }

    public function superGoal($goal_name = 'goal', $factors = array()) {
        foreach ($this->tests as $key => $value) {
            $this->goal($key, $goal_name, $factors);
        }
    }

    public function registerEvent($test_name, $goal_name, $variant_name, $factors = array()) {
        $variant_id = 0;
        if (isset($this->tests[$test_name])) {
            $variant_id = array_search($variant_name, $this->tests[$test_name]['variants']);
        }
        $event = array(
            'test_name'     => $test_name,
            'goal'          => $goal_name,
            'variant_name'  => $variant_name,
            'variant_id'    => (int)$variant_id,
            'factors'       => array_merge($this->common_factors, $factors),
        );
        $this->dispatcher->publish($this->exchange, $this->event_prefix, $event);
    }

}
