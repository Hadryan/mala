<?php

namespace Chrisyue\Mala\Manager;

use Chrisyue\Mala\Model\ChannelInterface;

interface VideoManagerInterface
{
    public function findByChannel(ChannelInterface $channel);
}
