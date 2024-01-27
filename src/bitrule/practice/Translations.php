<?php

declare(strict_types=1);

namespace bitrule\practice;

final class Translations {

    public static array $translations = [
    	'match.end_statistics_normal' => [
    		'opponent',
    		'self_elo_changes',
    		'self_critics',
    		'self_damage_dealt',
    		'opponent_critics',
    		'opponent_damage_dealt',
    	],
    	'match.end_statistics_pot' => [
    		'opponent',
    		'self_elo_changes',
    		'self_critics',
    		'self_damage_dealt',
    		'self_total_potions',
    		'opponent_critics',
    		'opponent_damage_dealt',
    		'opponent_total_potions'
    	],
    	'match.opponent_found' => [
    		'player',
    		'type',
    		'kit'
    	],
    ];
}