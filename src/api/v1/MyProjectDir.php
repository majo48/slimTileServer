<?php

namespace App\api\v1;

/**
 * Trait MyProjectDir
 * @package App\api\v1
 *
 * Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
trait MyProjectDir
{
    /**
     * Get the path of the project directory, e.g. '/srv/slim'
     * @return string
     */
    public function getProjectDir()
    {
        $dir = __DIR__;
        $dar = explode('/', $dir);
        $root = '/'.$dar[1].'/'.$dar[2];
        return $root;
    }

}