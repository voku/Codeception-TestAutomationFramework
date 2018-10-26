<?php

namespace Codeception\Module;
use PHPUnit\Framework\AssertionFailedError;

class ImageDeviationException extends AssertionFailedError
{
    private $identifier;
    private $expectedImage;
    private $currentImage;
    private $deviationImage;

    public function __construct($message, $identifier, $expectedImage, $currentImage, $deviationImage)
    {
        $this->identifier = $identifier;
        $this->deviationImage = $deviationImage;
        $this->currentImage = $currentImage;
        $this->expectedImage = $expectedImage;

        parent::__construct($message);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getDeviationImage()
    {
        return $this->deviationImage;
    }

    public function getCurrentImage()
    {
        return $this->currentImage;
    }

    public function getExpectedImage()
    {
        return $this->expectedImage;
    }
}
