<?php

namespace Codeception\Module\VisualCeption;

class Utils
{
    public function getTestFileName($signature, $identifier)
    {
        return str_replace([':', '\\'],  '.', $signature) . '.' . $identifier . '.png';
    }
}
