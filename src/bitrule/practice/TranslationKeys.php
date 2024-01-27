<?php

declare(strict_types=1);

namespace bitrule\practice;

enum TranslationKeys: string {

    case MATCH_END_STATISTICS_NORMAL = 'match.end_statistics_normal';
    case MATCH_END_STATISTICS_POT = 'match.end_statistics_pot';

    public function build(string... $arguments): string {
        $placeholders = Translations::$translations[$this->value] ?? [];
        if (count($arguments) !== count($placeholders)) {
            throw new \InvalidArgumentException('Invalid number of arguments. Expected ' . count($placeholders) . ' but got ' . count($arguments) . '.');
        }

        return Practice::wrapMessage($this->value, array_combine($placeholders, $arguments));
    }
}