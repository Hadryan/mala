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
use Chrisyue\Mala\Manager\MediaSegmentManagerInterface;
use Chrisyue\Mala\Model\ChannelInterface;
use Chrisyue\PhpM3u8\Parser;

class PlaylistScheduler
{
    private $parser;
    private $epgManager;
    private $mediaSegmentManager;

    public function __construct(Parser $parser, EpgManagerInterface $epgManager, MediaSegmentManagerInterface $mediaSegmentManager)
    {
        $this->parser = $parser;
        $this->epgManager = $epgManager;
        $this->mediaSegmentManager = $mediaSegmentManager;
    }

    public function schedule(ChannelInterface $channel, \DateTime $startsAt, \DateTime $endsAt)
    {
        $epg = $this->epgManager->find($channel, $startsAt, $endsAt);
        if (empty($epg)) {
            throw new \UnexpectedValueException('there is no epg found');
        }

        $firstProgramStartsAt = $epg[0]->getStartsAt();
        $this->mediaSegmentManager->clear($channel, $firstProgramStartsAt);

        $lastMediaSegment = $this->mediaSegmentManager->findLast($channel);

        $sequence = 0;
        if (null !== $lastMediaSegment && $firstProgramStartsAt->getTimestamp() <= $lastMediaSegment->getEndsAt()->getTimestamp() + 1) {
            $sequence = $lastMediaSegment->getSequence();
        }

        foreach ($epg as $program) {
            $isDiscontinuity = true;
            $segmentStartsAt = clone $program->getStartsAt();
            $handledDuration = 0;

            $m3u8 = $this->parser->parseFromUri($program->getVideo()->getUri());
            foreach ($m3u8->getPlaylist() as $originSegment) {
                $handledDuration += $originSegment->getDuration();
                $segmentEndsAt = clone $program->getStartsAt();
                $segmentEndsAt->modify(sprintf('+%d seconds', round($handledDuration)));

                $segment = $this->mediaSegmentManager->create(
                    $program, $segmentStartsAt, $segmentEndsAt,
                    $originSegment->getUri(), $originSegment->getDuration(),
                    ++$sequence, $isDiscontinuity
                );
                $this->mediaSegmentManager->saveDeferred($segment);

                $segmentStartsAt = clone $segmentEndsAt;
                $isDiscontinuity = false;
            }

            $this->mediaSegmentManager->commit();
        }
    }
}
