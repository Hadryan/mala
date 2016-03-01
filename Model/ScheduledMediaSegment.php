<?php

namespace Chrisyue\Mala\Model;

use Chrisyue\PhpM3u8\M3u8\MediaSegment\MediaSegment;

class ScheduledMediaSegment extends MediaSegment
{
    protected $program;

    protected $channel;

    protected $startsAt;

    protected $endsAt;

    public function __construct(
        ProgramInterface $program,
        \DateTime $startsAt,
        \DateTime $endsAt,
        $uri,
        $duration,
        $sequence,
        $isDiscontinuity
    ) {
        $this->program = $program;
        $this->channel = $program->getChannel();
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;

        parent::__construct($uri, $duration, $sequence, $isDiscontinuity);
    }

    public function getProgram()
    {
        return $this->program;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function getStartsAt()
    {
        return $this->startsAt;
    }

    public function getEndsAt()
    {
        return $this->endsAt;
    }
}
