<?php

namespace Reactor\ABDriver;

class ABDriver {
    
    public $common_factors = array();
    public $tests = array();
    protected $dispatcher = array();

    public function __construct($dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function startTest($test_name, $factors = array(), $total_variants = 2) {
        if (!isset($this->tests[$test_name])) {
            $variant = rand(1, $total_variants);
            $test = array(
                'variant' => $variant,
                'factors' => $factors,
            );
            $this->registerEvent($test_name, 'show', $variant, $factors);
        }
        return $this->tests[$test_name]['variant'];
    }

    public function goal($test_name) {
        if (isset($this->tests[$test_name])) {
            $this->registerEvent($test_name, 'goal', $variant, $factors);
            unset($this->tests[$test_name])
        }
    }

    public function registerEvent($test_name, $action, $variant, $factors = array()) {
        $event_name = $this->event_prefix . '.' . $action;
        $event = array(
            'test_name' => $test_name,
            'action'    => $action,
            'variant'   => $variant,
            'factors'   => array_merge($this->common_factors, $factors),
        );
        $this->dispatcher->dispatch($event_name, $event);
    }

}
