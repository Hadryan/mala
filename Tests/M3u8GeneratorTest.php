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

use Chrisyue\Mala\M3u8Generator;
use Prophecy\Argument;

class M3u8GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateWithNoMediaSegments()
    {
        $targetDuration = 10;
        $msManager = $this->prophesizeMediaSegmentManager(new \DateTime(), $targetDuration, array());

        $m3u8Generator = new M3u8Generator($msManager->reveal(), array('target_duration' => $targetDuration));
        $m3u8Generator->generate($this->prophesizeChannel()->reveal());
    }

    public function testGenerateWithMediaSegments()
    {
        $startsAt = new \DateTime();

        $targetDuration = rand(5, 15);
        $programSequence = rand(1, 9999);
        $version = rand(2, 3);
        $segmentEndsAt = clone $startsAt;
        $segment = $this->prophesizeMediaSegment($segmentEndsAt, $programSequence, true);

        $msManager = $this->prophesizeMediaSegmentManager($startsAt, $targetDuration, array($segment->reveal()));

        $m3u8Generator = new M3u8Generator($msManager->reveal(), array('target_duration' => $targetDuration, 'version' => $version));
        $m3u8 = $m3u8Generator->generate($this->prophesizeChannel()->reveal(), $startsAt);

        $this->assertEquals($m3u8->getTargetDuration(), $targetDuration);
        $this->assertEquals($m3u8->getVersion(), $version);
        // only when `discontinuity` tag is removed (which the first segment is not discontinuity segment), the media sequence should +1
        $this->assertEquals($m3u8->getDiscontinuitySequence(), --$programSequence);
        // even if first media segment will end at once, the age should be one second
        $this->assertEquals($m3u8->getAge(), 1);
    }

    private function prophesizeMediaSegmentManager(\DateTime $startsAt, $targetDuration, array $segments)
    {
        $msManager = $this->prophesize('Chrisyue\Mala\Manager\MediaSegmentManagerInterface');
        $msManager->findPlaying(Argument::type('Chrisyue\Mala\Model\ChannelInterface'), $startsAt, $targetDuration * 3)
            ->shouldBeCalledTimes(1)->willReturn($segments);

        return $msManager;
    }

    private function prophesizeChannel()
    {
        $channel = $this->prophesize('Chrisyue\Mala\Model\ChannelInterface');

        return $channel;
    }

    private function prophesizeMediaSegment(\DateTime $endsAt, $programSequence, $isDiscontinuity = false)
    {
        $mediaSegment = $this->prophesize('Chrisyue\Mala\Model\ScheduledMediaSegment');
        $mediaSegment->getEndsAt()->shouldBeCalledTimes(1)->willReturn($endsAt);

        $program = $this->prophesizeProgram($programSequence);
        $mediaSegment->getProgram()->shouldBeCalledTimes(1)->willReturn($program->reveal());

        $mediaSegment->isDiscontinuity()->shouldBeCalledTimes(1)->willReturn($isDiscontinuity);

        return $mediaSegment;
    }

    private function prophesizeProgram($sequence)
    {
        $program = $this->prophesize('Chrisyue\Mala\Model\ProgramInterface');
        $program->getSequence()->willReturn($sequence);

        return $program;
    }
}
