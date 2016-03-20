Mala
====

v1.0.2

Transform your m3u8 videos into a http live streaming channel

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f2d48dbd-2a9c-42eb-94ee-a097fb79e1f6/big.png)](https://insight.sensiolabs.com/projects/f2d48dbd-2a9c-42eb-94ee-a097fb79e1f6)

[![Latest Stable Version](https://poser.pugx.org/chrisyue/mala/v/stable)](https://packagist.org/packages/chrisyue/mala)
[![License](https://poser.pugx.org/chrisyue/mala/license)](https://packagist.org/packages/chrisyue/mala)
[![Build Status](https://travis-ci.org/chrisyue/mala.svg?branch=develop)](https://travis-ci.org/chrisyue/mala)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chrisyue/mala/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/chrisyue/mala/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/chrisyue/mala/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/chrisyue/mala/?branch=develop)
[![StyleCI](https://styleci.io/repos/52257600/shield)](https://styleci.io/repos/52257600)

Installation
------------

```
$ composer require 'chrisyue/mala'
```

Usage
-----

I suggest you check the [mala-demo](https://github.com/chrisyue/mala-demo) to see more details.

Suppose you've already implemented all interfaces under `Model` and `Manager` namespaces, then you can

### Generate EPG with m3u8 videos and generate hls playlist (scheduled media segments)

```php
// $epgManager = Chrisyue\Mala\Manager\EpgManagerInterface;
// $videoManager = Chrisyue\Mala\Manager\VideoManagerInterface;
$epgScheduler = new \Chrisyue\Mala\EpgScheduler($epgManager, $videoManager);

// $channel = Chrisyue\Mala\Model\ChannelInterface;

// generate tomorrow's epg
$epgScheduler->schedule($channel, new \DateTime('tomorror midnight'), new \DateTime('tomorrow 23:59:59'));

// generate playlist
// $mediaSegmentManager = ...;
$parser = new \Chrisyue\PhpM3u8\Parser();
$playlistScheduler = new \Chrisyue\Mala\PlaylistScheduler($parser, $epgManager, $mediaSegmentManager);
$playlistScheduler->schedule($channel, new \DateTime('tomorror midnight'), new \DateTime('tomorrow 23:59:59'));
```

### Generate current hls m3u8 from scheduled epg and playlist

```php
// $mediaSegmentManager = ...;
$options = ['target_duration' => 10, 'version' => 3];
$m3u8Generator = new M3u8Generator($mediaSegmentManager, $options);

// $channel = ...;
$m3u8 = $m3u8Generator->generate($channel[, $playsAt]); // or can you specify the play time as the 2nd parameter

// $dumper = ...;
$dumper->dump($m3u8);
```

You can check the [mala-demo](https://github.com/chrisyue/mala-demo) to get more details and examples about implementing the model/manager interfaces
