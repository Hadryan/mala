<?php

namespace Chrisyue\Mala\Manager;

use Chrisyue\Mala\Model\ChannelInterface;
use Chrisyue\Mala\Model\ProgramInterface;
use Chrisyue\PhpM3u8\M3u8\MediaSegment\MediaSegmentInterface;

interface MediaSegmentManagerInterface extends CommitableInterface
{
    public function clear(ChannelInterface $channel, \DateTime $startsAt);

    public function findLast(ChannelInterface $channel);

    public function findPlaying(ChannelInterface $channel, \DateTime $startsAt, \DateTime $endsAt);

    public function create(ProgramInterface $program, \DateTime $startsAt, \DateTime $endsAt, $uri, $duration, $sequence, $isDiscontinuity);

    public function saveDeferred(MediaSegmentInterface $segment);
}
