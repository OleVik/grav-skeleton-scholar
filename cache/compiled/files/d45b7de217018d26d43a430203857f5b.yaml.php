<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/Users/Ole/GitHub/grav-skeleton-scholar/system/languages/fr.yaml',
    'modified' => 1581466810,
    'data' => [
        'GRAV' => [
            'FRONTMATTER_ERROR_PAGE' => '---
titre: %1$s
---

# Erreur : Frontmatter invalide

Chemin: `%2$s`

**%3$s**

```
%4$s
```',
            'INFLECTOR_PLURALS' => [
                '/(quiz)$/i' => '\\1zes',
                '/^(ox)$/i' => '\\1en',
                '/([m|l])ouse$/i' => '\\1ice',
                '/(matr|vert|ind)ix|ex$/i' => '\\1ices',
                '/(x|ch|ss|sh)$/i' => '\\1es',
                '/([^aeiouy]|qu)ies$/i' => '\\1y',
                '/([^aeiouy]|qu)y$/i' => '\\1ies',
                '/(hive)$/i' => '\\1s',
                '/(?:([^f])fe|([lr])f)$/i' => '\\1\\2ves',
                '/sis$/i' => 'ses',
                '/([ti])um$/i' => '\\1a',
                '/(buffal|tomat)o$/i' => '\\1es',
                '/(bu)s$/i' => 'Bus',
                '/(alias|status)/i' => 'alias|status',
                '/(ax|test)is$/i' => '\\1s',
                '/s$/i' => 's',
                '/$/' => 's'
            ],
            'INFLECTOR_SINGULAR' => [
                '/(quiz)zes$/i' => '\\1',
                '/(alias|status)es$/i' => '\\1',
                '/([octop|vir])i$/i' => '\\1us'
            ],
            'INFLECTOR_UNCOUNTABLE' => [
                0 => 'équipement',
                1 => 'information',
                2 => 'riz',
                3 => 'argent',
                4 => 'espèces',
                5 => 'séries',
                6 => 'poisson',
                7 => 'mouton'
            ],
            'INFLECTOR_IRREGULAR' => [
                'person' => 'personnes',
                'man' => 'hommes',
                'child' => 'enfants',
                'sex' => 'sexes',
                'move' => 'déplacements'
            ],
            'INFLECTOR_ORDINALS' => [
                'default' => 'ème',
                'first' => 'er',
                'second' => 'ème',
                'third' => 'ème'
            ],
            'NICETIME' => [
                'NO_DATE_PROVIDED' => 'Aucune date fournie',
                'BAD_DATE' => 'Date erronée',
                'AGO' => 'plus tôt',
                'FROM_NOW' => 'à partir de maintenant',
                'JUST_NOW' => 'à l\'instant',
                'SECOND' => 'seconde',
                'MINUTE' => 'minute',
                'HOUR' => 'heure',
                'DAY' => 'jour',
                'WEEK' => 'semaine',
                'MONTH' => 'mois',
                'YEAR' => 'année',
                'DECADE' => 'décennie',
                'SEC' => 's',
                'MIN' => 'm',
                'HR' => 'h',
                'WK' => 'sem',
                'MO' => 'm',
                'YR' => 'an',
                'DEC' => 'déc',
                'SECOND_PLURAL' => 'secondes',
                'MINUTE_PLURAL' => 'minutes',
                'HOUR_PLURAL' => 'heures',
                'DAY_PLURAL' => 'jours',
                'WEEK_PLURAL' => 'semaines',
                'MONTH_PLURAL' => 'mois',
                'YEAR_PLURAL' => 'années',
                'DECADE_PLURAL' => 'décennies',
                'SEC_PLURAL' => 's',
                'MIN_PLURAL' => 'm',
                'HR_PLURAL' => 'h',
                'WK_PLURAL' => 'sem',
                'MO_PLURAL' => 'mois',
                'YR_PLURAL' => 'a',
                'DEC_PLURAL' => 'décs'
            ],
            'FORM' => [
                'VALIDATION_FAIL' => '<b>La validation a échoué :</b>',
                'INVALID_INPUT' => 'Saisie non valide',
                'MISSING_REQUIRED_FIELD' => 'Champ obligatoire manquant :'
            ],
            'MONTHS_OF_THE_YEAR' => [
                0 => 'Janvier',
                1 => 'Février',
                2 => 'Mars',
                3 => 'Avril',
                4 => 'Mai',
                5 => 'Juin',
                6 => 'Juillet',
                7 => 'Août',
                8 => 'Septembre',
                9 => 'Octobre',
                10 => 'Novembre',
                11 => 'Décembre'
            ],
            'DAYS_OF_THE_WEEK' => [
                0 => 'Lundi',
                1 => 'Mardi',
                2 => 'Mercredi',
                3 => 'Jeudi',
                4 => 'Vendredi',
                5 => 'Samedi',
                6 => 'Dimanche'
            ],
            'CRON' => [
                'EVERY' => 'chaque',
                'EVERY_HOUR' => 'toutes les heures',
                'EVERY_MINUTE' => 'chaque minute',
                'EVERY_DAY_OF_WEEK' => 'tous les jours de la semaine',
                'EVERY_DAY_OF_MONTH' => 'tous les jours du mois',
                'EVERY_MONTH' => 'chaque mois',
                'TEXT_PERIOD' => 'Chaque<b/>',
                'TEXT_MINS' => ' à <b /> minute(s) après l\'heure',
                'TEXT_TIME' => ' à<b/>:<b/>',
                'TEXT_DOW' => ' sur <b/>',
                'TEXT_MONTH' => ' de <b />',
                'TEXT_DOM' => ' sur <b/>',
                'ERROR1' => 'La balise %s n\'est pas supportée!',
                'ERROR2' => 'Nombre invalide d\'éléments',
                'ERROR3' => 'L\'élément jquery_element doit être défini dans les paramètres jqCron',
                'ERROR4' => 'Expression non reconnue'
            ]
        ]
    ]
];
