<?php

/*
 * This file is part of the Mala package.
 *
 * (c) Chrisyue <http://chrisyue.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chrisyue\Mala;

use Chrisyue\Mala\Manager\MediaSegmentManagerInterface;
use Chrisyue\Mala\Model\ChannelInterface;
use Chrisyue\PhpM3u8\M3u8\M3u8;
use Chrisyue\PhpM3u8\M3u8\Playlist;

class M3u8Generator
{
    private $manager;
    private $options;

    public function __construct(MediaSegmentManagerInterface $manager, array $options = array())
    {
        $this->manager = $manager;
        $this->options = $options + array(
            'version' => 3,
            'target_duration' => 10,
        );
    }

    public function generate(ChannelInterface $channel, \DateTime $startsAt = null)
    {
        $targetDuration = $this->options['target_duration'];

        if (null === $startsAt) {
            $startsAt = new \DateTime();
        }
        $segments = $this->manager->findPlaying($channel, $startsAt, $targetDuration * 3);
        $playlist = new Playlist($segments);

        $first = $playlist->getFirst();
        if (null === $first) {
            return;
        }

        $age = $first->getEndsAt()->getTimestamp() - $startsAt->getTimestamp() + 1;
        $playlist->setAge($age);

        $discontinuitySequence = $first->getProgram()->getSequence();
        if ($first->isDiscontinuity()) {
            --$discontinuitySequence;
        }

        return new M3u8($playlist, $this->options['version'], $targetDuration, $discontinuitySequence);
    }
}
