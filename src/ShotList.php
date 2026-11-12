<?php

declare(strict_types=1);

namespace Reavideo\Shotlist;

use InvalidArgumentException;

/**
 * Parse a plain-text shot list into shots, and summarise the cut rhythm.
 *
 * A shot list is one line per cut:
 *
 *     00:04  wide shot, empty street, slow push in
 *     00:12-00:19  close up, hands tying a knot
 *     00:20  3.5s  drone pull back over the harbour
 */
final class ShotList
{
    /** @var array<int, array{index:int,start:float,end:?float,duration:?float,description:string,line:string}> */
    private array $shots = [];

    public static function fromString(string $text): self
    {
        $list = new self();
        foreach (preg_split('/\R/', $text) ?: [] as $lineno => $raw) {
            $line = trim($raw);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 2) ?: [];
            $head = $parts[0];
            $rest = $parts[1] ?? '';

            [$start, $end] = self::parseSpan($head, $lineno + 1, $raw);

            $list->shots[] = [
                'index' => count($list->shots) + 1,
                'start' => $start,
                'end' => $end,
                'duration' => $end === null ? null : $end - $start,
                'description' => self::stripDuration($rest),
                'line' => $raw,
            ];
        }

        return $list;
    }

    /** @return array<int, array<string, mixed>> */
    public function shots(): array
    {
        return $this->shots;
    }

    /** @return array<string, float|int> */
    public function summary(): array
    {
        $durations = [];
        foreach ($this->shots as $shot) {
            if ($shot['duration'] !== null) {
                $durations[] = $shot['duration'];
            }
        }

        $timed = count($durations);
        $total = 0.0;
        foreach ($this->shots as $shot) {
            if ($shot['end'] !== null) {
                $total = $shot['end'] - $this->shots[0]['start'];
            }
        }

        return [
            'shots' => count($this->shots),
            'timed_shots' => $timed,
            'runtime' => $total,
            'average_shot' => $durations === [] ? 0.0 : round(array_sum($durations) / $timed, 2),
            'longest_shot' => $durations === [] ? 0.0 : max($durations),
            'shortest_shot' => $durations === [] ? 0.0 : min($durations),
            'cuts_per_minute' => $total > 0 ? round($timed / ($total / 60), 2) : 0.0,
        ];
    }

    public static function parseTimecode(string $value): float
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($value), $m)) {
            throw new InvalidArgumentException(sprintf('not a timecode: %s', $value));
        }

        if (!isset($m[3]) || $m[3] === '') {
            return (float) ((int) $m[1] * 60 + (int) $m[2]);
        }

        return (float) ((int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3]);
    }

    public static function formatTimecode(float $seconds): string
    {
        $total = (int) round($seconds);
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $secs = $total % 60;

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $secs)
            : sprintf('%02d:%02d', $minutes, $secs);
    }

    /** @return array{0:float,1:?float} */
    private static function parseSpan(string $head, int $lineno, string $raw): array
    {
        $span = explode('-', $head, 2);
        try {
            $start = self::parseTimecode($span[0]);
            $end = isset($span[1]) ? self::parseTimecode($span[1]) : null;
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('line %d has no leading timecode: %s', $lineno, trim($raw)),
                0,
                $e
            );
        }

        return [$start, $end];
    }

    private static function stripDuration(string $rest): string
    {
        if (preg_match('/^\d+(\.\d+)?s(?:\s+(.*))?$/s', $rest, $m)) {
            return trim($m[1] ?? '');
        }

        return trim($rest);
    }
}
