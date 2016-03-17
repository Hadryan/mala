<?php

/*
 * This file is part of the Mala package.
 *
 * (c) Chrisyue <http://chrisyue.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chrisyue\Mala\Model;

interface ProgramInterface
{
    public function getChannel();

    public function getVideo();

    public function getSequence();

    public function getStartsAt();

    public function getEndsAt();
}
