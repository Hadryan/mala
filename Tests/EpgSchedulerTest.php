<?php

/*
 * This file is part of the Mala package.
 *
 * (c) Chrisyue <http://chrisyue.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Chrisyue\Mala\EpgScheduler;
use Prophecy\Prophecy\ObjectProphecy;

class EpgSchedulerTest extends \PHPUnit_Framework_TestCase
{
    public function testScheduleWithNoLastProgramAndNoVideos()
    {
        $startsAt = new \DateTime();
        $endsAt = new \DateTime('+30 seconds');

        $channel = $this->prophesizeChannel();
        $epgManager = $this->prophesizeEpgManager($channel);
        $videoManager = $this->prophesizeVideoManager($channel);

        $epgScheduler = new EpgScheduler($epgManager->reveal(), $videoManager->reveal());
        $epgScheduler->schedule($channel->reveal(), $startsAt, $endsAt);
    }

    public function testScheduleWithNoLastProgram()
    {
        $startsAt = new \DateTime();
        $videoDuration = 40;

        $channel = $this->prophesizeChannel();
        $video = $this->prophesizeVideo($videoDuration);
        $videos = array($video);

        $epgManager = $this->prophesizeEpgManager($channel, null, true);
        $videoManager = $this->prophesizeVideoManager($channel, $videos);

        $epgScheduler = new EpgScheduler($epgManager->reveal(), $videoManager->reveal());
        $endsAt = clone $startsAt;
        $endsAt->modify(sprintf('+%d seconds', $videoDuration - 1)); // duration is [startsAt, endsAt]
        $this->epgManagerShouldCreateProgram($epgManager, $channel, $video, 1, $startsAt, $endsAt); // the first program sequence should be 1

        $epgScheduler->schedule($channel->reveal(), $startsAt, $endsAt);
    }

    public function testScheduleWithLastProgramButNoVideos()
    {
        $startsAt = new \DateTime();
        $endsAt = new \DateTime('+30 seconds');
        $lastProgramEndsAt = new \DateTime('+2 seconds');

        $channel = $this->prophesizeChannel();
        $program = $this->prophesizeProgram($lastProgramEndsAt);

        $epgManager = $this->prophesizeEpgManager($channel, $program);
        $videoManager = $this->prophesizeVideoManager($channel);
        $epgScheduler = new EpgScheduler($epgManager->reveal(), $videoManager->reveal());

        $epgScheduler->schedule($channel->reveal(), $startsAt, $endsAt);
    }

    public function testScheduleWithLastProgramWithForce()
    {
        $startsAt = new \DateTime();
        $endsAt = new \DateTime('+30 seconds');
        $lastProgramEndsAt = new \DateTime('+2 seconds');
        $lastProgramSequence = rand(1, 999);
        $videoDuration = 40;

        $channel = $this->prophesizeChannel($lastProgramEndsAt);
        $program = $this->prophesizeProgram($lastProgramEndsAt);
        $video = $this->prophesizeVideo($videoDuration);
        $videos = array($video);

        $epgManager = $this->prophesizeEpgManager($channel, $program);
        $epgManager->clear($channel->reveal(), $startsAt)->shouldBeCalledTimes(1);

        $videoManager = $this->prophesizeVideoManager($channel, $videos);

        $program->getSequence()->shouldBeCalledTimes(1)->willReturn($lastProgramSequence);

        $epgScheduler = new EpgScheduler($epgManager->reveal(), $videoManager->reveal());
        $programEndsAt = clone $lastProgramEndsAt;
        $programEndsAt->modify(sprintf('+%d seconds', $videoDuration)); // duration is (lastProgramEndsAt, $endsAt]
        $programStartsAt = clone $lastProgramEndsAt;
        $programStartsAt->modify('+1 second');
        $this->epgManagerShouldCreateProgram($epgManager, $channel, $video, $lastProgramSequence + 1, $programStartsAt, $programEndsAt);

        $epgScheduler->schedule($channel->reveal(), $startsAt, $endsAt, true);
    }

    private function epgManagerShouldCreateProgram(
        ObjectProphecy $epgManager,
        ObjectProphecy $channel,
        ObjectProphecy $video,
        $sequence, $videoStartsAt, $videoEndsAt
    ) {
        $newProgram = $this->prophesize('Chrisyue\Mala\Model\ProgramInterface');
        $epgManager->createProgram($channel->reveal(), $video->reveal(), $sequence, $videoStartsAt, $videoEndsAt)
            ->willReturn($newProgram->reveal());

        $epgManager->saveDeferred($newProgram->reveal())->shouldBeCalled();
        $epgManager->commit()->shouldBeCalledTimes(1);
    }

    private function prophesizeEpgManager(ObjectProphecy $channel, ObjectProphecy $lastProgram = null, $shouldCreateProgram = false)
    {
        $epgManager = $this->prophesize('Chrisyue\Mala\Manager\EpgManagerInterface');
        $epgManager->findLastProgram($channel->reveal())->shouldBeCalledTimes(1)
            ->willReturn(null === $lastProgram ? null : $lastProgram->reveal());

        return $epgManager;
    }

    private function prophesizeVideoManager(ObjectProphecy $channel, array $videos = array())
    {
        $videoManager = $this->prophesize('Chrisyue\Mala\Manager\VideoManagerInterface');
        $videoManager->findByChannel($channel->reveal())->shouldBeCalledTimes(1)->willReturn($videos);

        return $videoManager;
    }

    private function prophesizeChannel()
    {
        return $this->prophesize('Chrisyue\Mala\Model\ChannelInterface');
    }

    private function prophesizeProgram(\DateTime $endsAt)
    {
        $program = $this->prophesize('Chrisyue\Mala\Model\ProgramInterface');
        $program->getEndsAt()->shouldBeCalledTimes(1)->willReturn($endsAt);

        return $program;
    }

    private function prophesizeVideo($duration)
    {
        $video = $this->prophesize('Chrisyue\Mala\Model\VideoInterface');
        $video->getDuration()->shouldBeCalledTimes(1)->willReturn($duration);

        return $video;
    }
}
