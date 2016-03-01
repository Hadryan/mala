<?php

namespace Chrisyue\Mala\Model;

interface ProgramInterface
{
    public function getChannel();

    public function getVideo();

    public function getSequence();

    public function getStartsAt();

    public function getEndsAt();
}
