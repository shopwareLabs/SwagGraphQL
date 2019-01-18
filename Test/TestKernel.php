<?php declare(strict_types=1);

namespace SwagGraphQL\Test;

use Shopware\Development\Kernel;
use SwagGraphQL\SwagGraphQL;

class TestKernel extends Kernel
{
    protected function initializePlugins(): void
    {
        self::$plugins->add(new SwagGraphQL(true));
    }

    public function getProjectDir(): string
    {
        return parent::getProjectDir() . '/../../..';
    }
}