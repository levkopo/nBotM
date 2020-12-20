<?php

interface Module
{
    public function __construct();

    public function init($peer_id);
}