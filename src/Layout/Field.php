<?php

namespace LaravelAdmin\Crud\Layout;

class Field
{
    protected $content;
    protected $config;

    public function __construct($content, array $config=[])
    {
        $this->config = $config;
        $this->content = $content;
    }

    public function __toString()
    {
        return $this->content ?: '';
    }
}
