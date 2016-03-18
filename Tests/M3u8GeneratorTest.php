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
        $targetDuration = rand(5, 15);
        $programSequence = rand(1, 9999);
        $version = rand(2, 3);
        $segment = $this->prophesizeMediaSegment(new \DateTime(), $programSequence);
        $msManager = $this->prophesizeMediaSegmentManager(new \DateTime(), $targetDuration, array($segment->reveal()));

        $m3u8Generator = new M3u8Generator($msManager->reveal(), array('target_duration' => $targetDuration, 'version' => $version));
        $m3u8 = $m3u8Generator->generate($this->prophesizeChannel()->reveal());

        $this->assertEquals($m3u8->getTargetDuration(), $targetDuration);
        $this->assertEquals($m3u8->getVersion(), $version);
        $this->assertEquals($m3u8->getDiscontinuitySequence(), $programSequence);
        $this->assertEquals($m3u8->getAge(), 1); // even if program will end at once, the age should be one second
    }

    private function prophesizeMediaSegmentManager(\DateTime $startsAt, $targetDuration, array $segments)
    {
        $msManager = $this->prophesize('Chrisyue\Mala\Manager\MediaSegmentManagerInterface');

        $now = new \DateTime();
        $msManager->findPlaying(Argument::type('Chrisyue\Mala\Model\ChannelInterface'), $startsAt, $targetDuration)
            ->shouldBeCalledTimes(1)->willReturn($segments);

        return $msManager;
    }

    private function prophesizeChannel()
    {
        $channel = $this->prophesize('Chrisyue\Mala\Model\ChannelInterface');

        return $channel;
    }

    private function prophesizeMediaSegment(\DateTime $endsAt, $programSequence)
    {
        $mediaSegment = $this->prophesize('Chrisyue\Mala\Model\ScheduledMediaSegment');
        $mediaSegment->getEndsAt()->shouldBeCalledTimes(1)->willReturn($endsAt);

        $program = $this->prophesizeProgram($programSequence);
        $mediaSegment->getProgram()->shouldBeCalledTimes(1)->willReturn($program->reveal());

        return $mediaSegment;
    }

    private function prophesizeProgram($sequence)
    {
        $program = $this->prophesize('Chrisyue\Mala\Model\ProgramInterface');
        $program->getSequence()->willReturn($sequence);

        return $program;
    }
}
