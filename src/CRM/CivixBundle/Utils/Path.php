<?php

namespace CRM\CivixBundle\Utils;

class Path {

    function __construct($basedir) {
        $this->basedir = $basedir;
    }

    /**
     * Determine the full path to a file underneath this path
     *
     * ex: $basepath = $path->string()
     * ex: $item = $this->string('subdir', 'file.xml');
     *
     * @return string
     */
    function string() {
        $args = func_get_args();
        array_unshift($args, $this->basedir);
        return implode(DIRECTORY_SEPARATOR, $args);
    }

    /**
     * @return CRM_Civix_Utils_Path
     */
    function path() {
        $args = func_get_args();
        array_unshift($args, $this->basedir);
        return new Path(implode(DIRECTORY_SEPARATOR, $args));
    }

}