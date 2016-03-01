<?php

namespace Chrisyue\Mala;

use Chrisyue\Mala\Manager\EpgManagerInterface;
use Chrisyue\Mala\Manager\VideoManagerInterface;
use Chrisyue\Mala\Model\ChannelInterface;

class EpgScheduler
{
    private $epgManager;
    private $videoRepository;

    public function __construct(EpgManagerInterface $epgManager, VideoManagerInterface $videoRepository)
    {
        $this->epgManager = $epgManager;
        $this->videoRepository = $videoRepository;
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
                // 或者上一个epg entry 的结束时间大于结束时间，直接退出
                throw new \Exception(sprintf(
                    'Cannot schedule in %s to %s because there is newer epg, you can try to do a force schedule',
                    $startsAt->format('c'),
                    $endsAt->format('c')
                ));
            }
            // 确定epg从$startsAt开始还是从last epg entry的结束时间开始
            if ($startsAt < $shouldStartsAt) {
                $startsAt = $shouldStartsAt;
            }
        }

        $videos = $this->videoRepository->findByChannel($channel);
        if (empty($videos)) {
            return;
        }

        $videoStartAt = clone $startsAt;
        $sequence = null === $lastProgram ? 0 : $lastProgram->getSequence();

        $videos = new \InfiniteIterator(new \ArrayIterator($videos));
        foreach ($videos as $video) {
            $videoEndAt = clone $videoStartAt;
            $videoEndAt->modify(sprintf('+%d seconds', $video->getDuration() - 1));

            $program = $this->epgManager->createProgram($channel, $video, ++$sequence, $videoStartAt, $videoEndAt);
            $this->epgManager->saveDeferred($program);

            $videoStartAt = clone $videoEndAt;
            $videoStartAt->modify('+1 second');
            if ($videoStartAt > $endsAt) {
                break;
            }
        }

        $this->epgManager->commit();
    }
}
