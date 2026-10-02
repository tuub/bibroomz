<?php

declare(strict_types=1);

return [
    'title' => 'Impressum',
    'description' => ' ',
    'paragraphs' => [
        'contact' => [
            'header' => 'Kontakt',
            'content' => 'Technische Universität Berlin – Universitätsbibliothek, Fasanenstraße 88, 10623 Berlin',
        ],
        'requests' => [
            'header' => 'Anfragen',
            'content' => 'info@ub.tu-berlin.de',
        ],
        'about' => [
            'header' => 'Über die TU Berlin',
            'content' => 'Die Technische Universität Berlin ist eine Körperschaft öffentlichen Rechts gemäß §§ 1
                          und 2 des Berliner Hochschulgesetzes (BerlHG) und zugleich eine staatliche Einrichtung.
                          Sie wird durch die Präsidentin gesetzlich vertreten. Der Name “Technische Universität Berlin”
                          wird nicht ins Englische übersetzt.',
        ],
        'authority' => [
            'header' => 'Rechtsaufsicht',
            'content' => 'Senatsverwaltung für Wissenschaft, Gesundheit und Pflege',
        ],
        'tax_id' => [
            'header' => 'Umsatzsteueridentifikationsnummer (USt-Id-Nr.)',
            'content' => 'DE 811 231 089',
        ],
        'source_code' => [
            'header' => 'Quellcode',
            'content' => 'Diese Anwendung steht unter der GNU General Public License v3.0. Der vollständige
                          Quellcode ist verfügbar',
            'link_label' => 'auf GitHub',
            'link' => 'https://github.com/tuub/bibroomz',
        ],
        'third_party' => [
            'header' => 'Schrift und Symbole',
            'content' => 'Die Schriften und die Symbole dieser Seite stammen von anderen und stehen unter eigenen
                          Lizenzen:',
            'noto_sans' => [
                'content' => 'Noto Sans, Copyright 2022 The Noto Project Authors, unter der',
                'link_label' => 'SIL Open Font License 1.1',
                'link' => 'https://openfontlicense.org/open-font-license-official-text/',
            ],
            'remix_icon' => [
                'content' => 'Remix Icon, Copyright RemixIcon.com, unter der',
                'link_label' => 'Apache License 2.0',
                'link' => 'https://www.apache.org/licenses/LICENSE-2.0',
            ],
            'prime_icons' => [
                'content' => 'PrimeIcons, Copyright 2018-2021 PrimeTek, unter der',
                'link_label' => 'MIT License',
                'link' => 'https://opensource.org/license/mit',
            ],
            'notices' => [
                'content' => 'Diese Seite liefert auch Code anderer aus. Die vollständige Lizenz jedes darin
                              enthaltenen Pakets steht gesammelt in den',
                'link_label' => 'Lizenzhinweisen zu Drittsoftware',
            ],
        ],
    ],
];
