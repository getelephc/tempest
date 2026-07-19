<?php

declare(strict_types=1);

namespace Tempest\ElephcProbe;

final class Subject
{
}

final class Holder
{
    public Subject $subject;

    public function __construct()
    {
        $this->subject = new Subject();
    }
}

function make_subject(): Subject
{
    return new Subject();
}

$subject = new Subject();
$holder = new Holder();

echo $subject::class . "\n";
echo $holder->subject::class . "\n";
echo make_subject()::class . "\n";
