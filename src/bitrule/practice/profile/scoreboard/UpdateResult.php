<?php

declare(strict_types=1);

namespace bitrule\practice\profile\scoreboard;

enum UpdateResult {
    case ADDED;
    case UPDATED;
    case NOT_UPDATED;
    case REMOVED;
}