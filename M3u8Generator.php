<?php

namespace Chrisyue\Mala;

use Chrisyue\Mala\Model\ChannelInterface;
use Chrisyue\PhpM3u8\M3u8\Playlist;
use Chrisyue\PhpM3u8\M3u8\M3u8;
use Chrisyue\Mala\Manager\MediaSegmentManagerInterface;

class M3u8Generator
{
    private $manager;
    private $options;

    public function __construct(MediaSegmentManagerInterface $manager, array $options)
    {
        $this->manager = $manager;
        $this->options = $options + [
            'version' => 3,
            'target_duration' => 10,
        ];
    }

    public function generate(ChannelInterface $channel)
    {
        $targetDuration = $this->options['target_duration'];

        $startsAt = new \DateTime();
        $endsAt = clone $startsAt;
        $endsAt->modify(sprintf('+%d seconds', $targetDuration * 3 - 1));

        $segments = $this->manager->findPlaying($channel, $startsAt, $endsAt);
        $playlist = new Playlist($segments);

        $first = $playlist->getFirst();
        if (null === $first) {
            return;
        }

        $age = $first->getEndsAt()->getTimestamp() - $startsAt->getTimestamp() + 1;
        $playlist->setAge($age);

        return new M3u8($playlist, $this->options['version'], $targetDuration, $first->getProgram()->getSequence());
    }
}
