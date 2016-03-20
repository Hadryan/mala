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

use Chrisyue\Mala\PlaylistScheduler;
use Prophecy\Prophecy\ObjectProphecy;

class PlaylistSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException UnexpectedValueException
     */
    public function testScheduleFirstTimeButNoEpg() // no last media segment
    {
        $startsAt = new \DateTime();
        $endsAt = new \DateTime('+30 seconds');
        $channel = $this->prophesizeChannel();

        $parser = $this->prophesizeParser();
        $msManager = $this->prophesizeMediaSegmentManager();
        $epgManager = $this->prophesizeEpgManager();

        $scheduler = new PlaylistScheduler($parser->reveal(), $epgManager->reveal(), $msManager->reveal());
        $scheduler->schedule($channel->reveal(), $startsAt, $endsAt);
    }

    public function testSchedule()
    {
        $startsAt = new \DateTime();
        $endsAt = new \DateTime('+30 seconds');
        $channel = $this->prophesizeChannel();

        $videoUri = 'http://www.example.com/foo.m3u8';
        $video = $this->prophesizeVideo($videoUri);

        $programStartsAt = new \DateTime('+10 seconds');
        $programEndsAt = new \DateTime('+40 seconds');
        $program = $this->prophesizeProgram($programStartsAt, $programEndsAt, $video);

        $epgManager = $this->prophesizeEpgManager($channel, $startsAt, $endsAt, $program);

        $lastScheduledMsSequence = 10;
        $lastScheduledMsEndsAt = new \DateTime('+10 seconds');
        $lastScheduledMs = $this->prophesizeLastScheduledMediaSegment($lastScheduledMsEndsAt, $lastScheduledMsSequence);

        $msDuration = 30;
        $msUri = 'http://example.com/foo1.ts';
        $mediaSegment = $this->prophesizeMediaSegment($msUri, $msDuration);

        $m3u8 = $this->prophesizeM3u8($mediaSegment);

        $parser = $this->prophesizeParser($videoUri, $m3u8);

        $newScheduledMs = $this->prophesizeNewScheduledMediaSegment();

        $msManager = $this->prophesizeMediaSegmentManager($channel, $programStartsAt, $lastScheduledMs);
        $msManager->create($program->reveal(), $programStartsAt, $programEndsAt, $msUri, $msDuration, $lastScheduledMsSequence + 1, true)
            ->shouldBeCalledTimes(1)->willReturn($newScheduledMs->reveal());

        $msManager->saveDeferred($newScheduledMs->reveal())->shouldBeCalledTimes(1);
        $msManager->commit()->shouldBeCalledTimes(1);

        $scheduler = new PlaylistScheduler($parser->reveal(), $epgManager->reveal(), $msManager->reveal());
        $scheduler->schedule($channel->reveal(), $startsAt, $endsAt);
    }

    private function prophesizeMediaSegmentManager(ObjectProphecy $channel = null, $programStartsAt = null, ObjectProphecy $last = null)
    {
        $msManager = $this->prophesize('Chrisyue\Mala\Manager\MediaSegmentManagerInterface');

        if (null === $channel) {
            return $msManager;
        }

        $msManager->clear($channel->reveal(), $programStartsAt)->shouldBeCalledTimes(1);
        $msManager->findLast($channel->reveal())->shouldBeCalledTimes(1)->willReturn(null === $last ? null : $last->reveal());

        return $msManager;
    }

    private function prophesizeChannel()
    {
        $channel = $this->prophesize('Chrisyue\Mala\Model\ChannelInterface');

        return $channel;
    }

    private function prophesizeEpgManager(
        ObjectProphecy $channel = null,
        \DateTime $startsAt = null,
        \DateTime $endsAt = null,
        ObjectProphecy $program = null
    ) {
        $epgManager = $this->prophesize('Chrisyue\Mala\Manager\EpgManagerInterface');
        if (null === $channel) {
            return $epgManager;
        }

        $findResult = null;
        if (null !== $program) {
            $findResult = array($program->reveal());
        }

        $epgManager->find($channel->reveal(), $startsAt, $endsAt)->willReturn($findResult);

        return $epgManager;
    }

    private function prophesizeLastScheduledMediaSegment(\DateTime $endsAt, $sequence)
    {
        $mediaSegment = $this->prophesize('Chrisyue\Mala\Model\ScheduledMediaSegment');
        $mediaSegment->getEndsAt()->shouldBeCalledTimes(1)->willReturn($endsAt);
        $mediaSegment->getSequence()->shouldBeCalledTimes(1)->willReturn($sequence);

        return $mediaSegment;
    }

    private function prophesizeNewScheduledMediaSegment()
    {
        $mediaSegment = $this->prophesize('Chrisyue\Mala\Model\ScheduledMediaSegment');

        return $mediaSegment;
    }

    private function prophesizeMediaSegment($uri, $duration)
    {
        $mediaSegment = $this->prophesize('Chrisyue\Mala\Model\ScheduledMediaSegment');
        $mediaSegment->getUri()->shouldBeCalledTimes(1)->willReturn($uri);
        $mediaSegment->getDuration()->shouldBeCalledTimes(2)->willReturn($duration);

        return $mediaSegment;
    }

    private function prophesizeParser($m3u8Uri = null, ObjectProphecy $m3u8 = null)
    {
        $parser = $this->prophesize('Chrisyue\PhpM3u8\Parser');
        if (null === $m3u8Uri) {
            return $parser;
        }

        $parser->parseFromUri($m3u8Uri)->shouldBeCalledTimes(1)->willReturn($m3u8->reveal());

        return $parser;
    }

    private function prophesizeProgram(\DateTime $startsAt, \DateTime $endsAt, ObjectProphecy $video)
    {
        $program = $this->prophesize('Chrisyue\Mala\Model\ProgramInterface');
        $program->getVideo()->shouldBeCalledTimes(1)->willReturn($video->reveal());
        $program->getStartsAt()->shouldBeCalled()->willReturn($startsAt);

        return $program;
    }

    private function prophesizeVideo($uri)
    {
        $video = $this->prophesize('Chrisyue\Mala\Model\VideoInterface');
        $video->getUri()->shouldBeCalledTimes(1)->willReturn($uri);

        return $video;
    }

    private function prophesizeM3u8(ObjectProphecy $mediaSegment)
    {
        $m3u8 = $this->prophesize('Chrisyue\PhpM3u8\M3u8\M3u8');
        $m3u8->getPlaylist()->shouldBeCalledTimes(1)->willReturn(array($mediaSegment->reveal()));

        return $m3u8;
    }
}
