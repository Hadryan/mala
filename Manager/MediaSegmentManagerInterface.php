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
use Chrisyue\Mala\Model\ProgramInterface;
use Chrisyue\Mala\Model\ScheduledMediaSegment;

interface MediaSegmentManagerInterface extends CommitableInterface
{
    public function clear(ChannelInterface $channel, \DateTime $startsAt);

    public function findLast(ChannelInterface $channel);

    public function findPlaying(ChannelInterface $channel, \DateTime $startsAt, $targetDuration);

    public function create(ProgramInterface $program, \DateTime $startsAt, \DateTime $endsAt, $uri, $duration, $sequence, $isDiscontinuity);

    public function saveDeferred(ScheduledMediaSegment $segment);
}
