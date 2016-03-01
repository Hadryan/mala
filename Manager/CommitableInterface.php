<?php

namespace Chrisyue\Mala\Manager;

interface CommitableInterface
{
    /**
     * Persist any deferred item
     */
    public function commit();
}
