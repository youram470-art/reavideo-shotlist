<?php

declare(strict_types=1);

require __DIR__ . '/../src/ShotList.php';

use Reavideo\Shotlist\ShotList;

$failures = 0;

function check(string $label, $actual, $expected): void
{
    global $failures;
    if ($actual === $expected) {
        echo "ok   $label\n";

        return;
    }
    $failures++;
    echo "FAIL $label\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
}

$text = <<<'TXT'
# take 3
00:00-00:04  wide shot, empty street, slow push in
00:04-00:07  close up, hands tying a knot
00:07-00:12  3.5s  drone pull back over the harbour
00:12        hard cut to black
TXT;

$list = ShotList::fromString($text);
$shots = $list->shots();

check('shot count', count($shots), 4);
check('first description', $shots[0]['description'], 'wide shot, empty street, slow push in');
check('duration marker stripped', $shots[2]['description'], 'drone pull back over the harbour');
check('second duration', $shots[1]['duration'], 3.0);
check('bare timecode has no end', $shots[3]['end'], null);
check('summary shots', $list->summary()['shots'], 4);
check('summary timed_shots', $list->summary()['timed_shots'], 3);
check('summary runtime', $list->summary()['runtime'], 12.0);
check('summary average', $list->summary()['average_shot'], 4.0);
check('summary cpm', $list->summary()['cuts_per_minute'], 15.0);
check('parse hh:mm:ss', ShotList::parseTimecode('1:02:03'), 3723.0);
check('format hh:mm:ss', ShotList::formatTimecode(3723), '01:02:03');

try {
    ShotList::fromString("opening title card\n");
    check('missing timecode throws', false, true);
} catch (InvalidArgumentException $e) {
    check('missing timecode throws', true, true);
}

echo $failures === 0 ? "\nall checks passed\n" : "\n$failures failed\n";
exit($failures === 0 ? 0 : 1);
