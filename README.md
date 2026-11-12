# reavideo/shotlist

Parse a plain-text shot list into shots, timings and cut statistics.

```bash
composer require reavideo/shotlist
```

## Usage

```php
use Reavideo\Shotlist\ShotList;

$list = ShotList::fromString(<<<'TXT'
00:00-00:04  wide shot, empty street, slow push in
00:04-00:07  close up, hands tying a knot
00:07-00:12  3.5s  drone pull back over the harbour
00:12        hard cut to black
TXT);

foreach ($list->shots() as $shot) {
    printf("%d  %s  %s\n", $shot['index'], ShotList::formatTimecode($shot['start']), $shot['description']);
}

print_r($list->summary());
```

`summary()` returns the numbers an edit usually gets asked for first:

```php
[
    'shots' => 4,
    'timed_shots' => 3,
    'runtime' => 12.0,
    'average_shot' => 4.0,
    'longest_shot' => 5.0,
    'shortest_shot' => 3.0,
    'cuts_per_minute' => 15.0,
]
```

## Input

One shot per line: `start[-end]  description`. A `3.5s` marker right after the
timecode is allowed and dropped from the description. Blank lines and `#`
comments are ignored. `mm:ss` and `hh:mm:ss` are both accepted.

## Timecodes

```php
ShotList::parseTimecode('1:02:03');  // 3723.0
ShotList::formatTimecode(3723);      // '01:02:03'
```

## Tests

```bash
php tests/run.php
```

## Why

Shot lists written during an edit are usually loose text, and they get
re-typed by hand whenever someone needs the timing again. Parsing them is
cheap, and doing it once means a breakdown can be diffed between versions of a
cut.

This library is the parsing half of the workflow behind
[Rea Video](https://reavideo.pro/), which detects the cuts in the video itself.

## License

MIT
