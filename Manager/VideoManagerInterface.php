<?php

/*
 * This file is part of the Mala package.
 *
 * (c) Chrisyue <http://chrisyue.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chrisyue\Mala\Manager;

use Chrisyue\Mala\Model\ChannelInterface;

interface VideoManagerInterface
{
    public function findByChannel(ChannelInterface $channel);
}
