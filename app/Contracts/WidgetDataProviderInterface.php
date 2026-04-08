<?php

namespace App\Contracts;

interface WidgetDataProviderInterface
{
    public function getData(array $params = []): array;
}
