<?php
declare(strict_types=1);
// Created by i729
function visitor_admin_authorized(string $expected, string $provided): bool {
  return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}
