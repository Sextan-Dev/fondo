<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Income classifications
    |--------------------------------------------------------------------------
    |
    | Classification => subclassifications of an income. The operation form, the
    | income import and its template all read this list, so they never disagree.
    |
    */

    'income_categories' => [
        'AVN' => ['AVN'],
        'Corporativo' => ['Matching', 'Nacional', 'Internacional', 'Voluntariado', 'Plataformas'],
        'Reciclaje' => ['Plastico', 'Unicel', 'Carton', 'Metales', 'Otros'],
        'Especie' => [
            'Articulos de limpieza e higiene personal', 'Papeleria', 'Alimentos', 'Electrodomesticos',
            'Electronica', 'Juguetes', 'Medicamentos', 'Mobiliarios y equipo de oficina', 'Otros',
        ],
        'Emergencias' => ['Nacional', 'Internacional', 'Individuales', 'Plataforma'],
        'Donativos Individuales' => ['Plataforma', 'Nacional', 'Internacional'],
        'No identificados' => ['Nacional', 'Internacional'],
        'Remanentes' => ['Remanentes de proyectos', 'Extraordinarios'],
        'Liberaciones' => ['Liberaciones CDMX', 'Fee'],
        'Costo Operativo' => ['Costo Operativo'],
        'RESERVA RETENCIÓN CO' => ['RESERVA RETENCIÓN CO'],
    ],

    // The system books these when an income retains operating cost: an import can't use them.
    'system_income_categories' => ['Costo Operativo', 'RESERVA RETENCIÓN CO'],

    // Contact that receives the operating cost retained from imported incomes.
    'operating_cost_contact_id' => 891,

    // Largest income layout accepted, in data rows.
    'income_import_max_rows' => 5000,

];
