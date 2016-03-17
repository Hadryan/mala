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

use Chrisyue\Mala\Manager\EpgManagerInterface;
use Chrisyue\Mala\Manager\VideoManagerInterface;
use Chrisyue\Mala\Model\ChannelInterface;

class EpgScheduler
{
    private $epgManager;
    private $videoManager;

    public function __construct(EpgManagerInterface $epgManager, VideoManagerInterface $videoManager)
    {
        $this->epgManager = $epgManager;
        $this->videoManager = $videoManager;
    }

    public function schedule(ChannelInterface $channel, \DateTime $startsAt, \DateTime $endsAt, $isForce = false)
    {
        if ($isForce) {
            $this->epgManager->clear($channel, $startsAt);
        }

        $lastProgram = $this->epgManager->findLastProgram($channel);

        if (null !== $lastProgram) {
            $shouldStartsAt = clone $lastProgram->getEndsAt();
            $shouldStartsAt->modify('+1 second');

            if ($shouldStartsAt > $endsAt) {
                // the ends time of the last program is after $endsAt
                throw new \Exception(sprintf(
                    'Cannot schedule in %s to %s because there is newer epg, you can try to do a force schedule',
                    $startsAt->format('c'),
                    $endsAt->format('c')
                ));
            }

            if ($startsAt < $shouldStartsAt) {
                $startsAt = $shouldStartsAt;
            }
        }

        $videos = $this->videoManager->findByChannel($channel);
        if (empty($videos)) {
            return;
        }

        $videoStartsAt = clone $startsAt;
        $sequence = null === $lastProgram ? 0 : $lastProgram->getSequence();

        $videos = new \InfiniteIterator(new \ArrayIterator($videos));
        foreach ($videos as $video) {
            $videoEndsAt = clone $videoStartsAt;
            $videoEndsAt->modify(sprintf('+%d seconds', $video->getDuration() - 1));

            $program = $this->epgManager->createProgram($channel, $video, ++$sequence, $videoStartsAt, $videoEndsAt);
            $this->epgManager->saveDeferred($program);

            $videoStartsAt = clone $videoEndsAt;
            $videoStartsAt->modify('+1 second');
            if ($videoStartsAt > $endsAt) {
                break;
            }
        }

        $this->epgManager->commit();
    }
}
