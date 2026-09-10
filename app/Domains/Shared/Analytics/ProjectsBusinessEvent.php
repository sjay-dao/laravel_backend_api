<?php

namespace App\Domains\Shared\Analytics;

interface ProjectsBusinessEvent
{
    public function toBusinessEventProjection(): BusinessEventProjection;
}
