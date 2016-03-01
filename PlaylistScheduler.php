<?php

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
        $this->mediaSegmentManager->clear($channel, $startsAt);

        $lastMediaSegment = $this->mediaSegmentManager->findLast($channel);

        $sequence = 0;
        if (null !== $lastMediaSegment && $startsAt->getTimestamp() <= $lastMediaSegment->getEndsAt()->getTimestamp() + 1) {
            $sequence = $lastMediaSegment->getSequence();
        }

        $epg = $this->epgManager->find($channel, $startsAt, $endsAt);
        foreach ($epg as $program) {
            $isDiscontinuity = true;
            $segmentStartsAt = clone $program->getStartsAt();

            $m3u8 = $this->parser->parseFromUri($program->getVideo()->getUri());
            foreach ($m3u8->getPlaylist() as $originSegment) {
                $duration = round($originSegment->getDuration());
                $segmentEndsAt = clone $segmentStartsAt;
                $segmentEndsAt->modify(sprintf('+%d seconds', $duration - 1));

                $segment = $this->mediaSegmentManager->create(
                    $program, $segmentStartsAt, $segmentEndsAt,
                    $originSegment->getUri(), $originSegment->getDuration(),
                    ++$sequence, $isDiscontinuity
                );
                $this->mediaSegmentManager->saveDeferred($segment);

                $segmentStartsAt = clone $segmentEndsAt;
                $segmentStartsAt->modify('+1 second');
                $isDiscontinuity = false;
            }

            $this->mediaSegmentManager->commit();
        }
    }
}
