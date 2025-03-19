<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Laukas :attribute turi būti priimtas.',
    'accepted_if' => 'Laukas :attribute turi būti priimtas, kai :other yra :value.',
    'active_url' => 'Laukas :attribute nėra galiojantis URL.',
    'after' => 'Laukas :attribute turi būti data po :date.',
    'after_or_equal' => 'Laukas :attribute turi būti data po arba lygi :date.',
    'alpha' => 'Laukas :attribute gali turėti tik raides.',
    'alpha_dash' => 'Laukas :attribute gali turėti tik raides, skaičius, brūkšnelius ir pabraukimus.',
    'alpha_num' => 'Laukas :attribute gali turėti tik raides ir skaičius.',
    'array' => 'Laukas :attribute turi būti masyvas.',
    'before' => 'Laukas :attribute turi būti data prieš :date.',
    'before_or_equal' => 'Laukas :attribute turi būti data prieš arba lygi :date.',
    'between' => [
        'numeric' => 'Laukas :attribute turi būti tarp :min ir :max.',
        'file' => 'Laukas :attribute turi būti tarp :min ir :max kilobaitų.',
        'string' => 'Laukas :attribute turi būti tarp :min ir :max simbolių.',
        'array' => 'Laukas :attribute turi turėti nuo :min iki :max elementų.',
    ],
    'boolean' => 'Laukas :attribute turi būti teisingas arba klaidingas.',
    'confirmed' => 'Laukas :attribute patvirtinimas nesutampa.',
    'current_password' => 'Slaptažodis yra neteisingas.',
    'date' => 'Laukas :attribute nėra galiojanti data.',
    'date_equals' => 'Laukas :attribute turi būti data lygi :date.',
    'date_format' => 'Laukas :attribute neatitinka formato :format.',
    'declined' => 'Laukas :attribute turi būti atmestas.',
    'declined_if' => 'Laukas :attribute turi būti atmestas, kai :other yra :value.',
    'different' => 'Laukai :attribute ir :other turi skirtis.',
    'digits' => 'Laukas :attribute turi būti :digits skaitmenų.',
    'digits_between' => 'Laukas :attribute turi būti tarp :min ir :max skaitmenų.',
    'dimensions' => 'Laukas :attribute turi neteisingas paveikslėlio dimensijas.',
    'distinct' => 'Laukas :attribute turi pasikartojančią reikšmę.',
    'email' => 'Laukas :attribute turi būti galiojantis el. pašto adresas.',
    'ends_with' => 'Laukas :attribute turi baigtis vienu iš: :values.',
    'enum' => 'Pasirinktas :attribute yra neteisingas.',
    'exists' => 'Pasirinktas :attribute yra neteisingas.',
    'file' => 'Laukas :attribute turi būti failas.',
    'filled' => 'Laukas :attribute turi turėti reikšmę.',
    'gt' => [
        'numeric' => 'Laukas :attribute turi būti didesnis nei :value.',
        'file' => 'Laukas :attribute turi būti didesnis nei :value kilobaitų.',
        'string' => 'Laukas :attribute turi būti didesnis nei :value simbolių.',
        'array' => 'Laukas :attribute turi turėti daugiau nei :value elementų.',
    ],
    'gte' => [
        'numeric' => 'Laukas :attribute turi būti didesnis arba lygus :value.',
        'file' => 'Laukas :attribute turi būti didesnis arba lygus :value kilobaitų.',
        'string' => 'Laukas :attribute turi būti didesnis arba lygus :value simbolių.',
        'array' => 'Laukas :attribute turi turėti :value elementų arba daugiau.',
    ],
    'image' => 'Laukas :attribute turi būti paveikslėlis.',
    'in' => 'Pasirinktas :attribute yra neteisingas.',
    'in_array' => 'Laukas :attribute neegzistuoja :other.',
    'integer' => 'Laukas :attribute turi būti sveikasis skaičius.',
    'ip' => 'Laukas :attribute turi būti galiojantis IP adresas.',
    'ipv4' => 'Laukas :attribute turi būti galiojantis IPv4 adresas.',
    'ipv6' => 'Laukas :attribute turi būti galiojantis IPv6 adresas.',
    'json' => 'Laukas :attribute turi būti galiojanti JSON eilutė.',
    'lt' => [
        'numeric' => 'Laukas :attribute turi būti mažesnis nei :value.',
        'file' => 'Laukas :attribute turi būti mažesnis nei :value kilobaitų.',
        'string' => 'Laukas :attribute turi būti mažesnis nei :value simbolių.',
        'array' => 'Laukas :attribute turi turėti mažiau nei :value elementų.',
    ],
    'lte' => [
        'numeric' => 'Laukas :attribute turi būti mažesnis arba lygus :value.',
        'file' => 'Laukas :attribute turi būti mažesnis arba lygus :value kilobaitų.',
        'string' => 'Laukas :attribute turi būti mažesnis arba lygus :value simbolių.',
        'array' => 'Laukas :attribute turi turėti ne daugiau kaip :value elementų.',
    ],
    'mac_address' => 'Laukas :attribute turi būti galiojantis MAC adresas.',
    'max' => [
        'numeric' => 'Laukas :attribute negali būti didesnis nei :max.',
        'file' => 'Laukas :attribute negali būti didesnis nei :max kilobaitų.',
        'string' => 'Laukas :attribute negali būti didesnis nei :max simbolių.',
        'array' => 'Laukas :attribute negali turėti daugiau nei :max elementų.',
    ],
    'mimes' => 'Laukas :attribute turi būti failo tipas: :values.',
    'mimetypes' => 'Laukas :attribute turi būti failo tipas: :values.',
    'min' => [
        'numeric' => 'Laukas :attribute turi būti bent :min.',
        'file' => 'Laukas :attribute turi būti bent :min kilobaitų.',
        'string' => 'Laukas :attribute turi būti bent :min simbolių.',
        'array' => 'Laukas :attribute turi turėti bent :min elementų.',
    ],
    'multiple_of' => 'Laukas :attribute turi būti :value kartotinis.',
    'not_in' => 'Pasirinktas :attribute yra neteisingas.',
    'not_regex' => 'Lauko :attribute formatas yra neteisingas.',
    'numeric' => 'Laukas :attribute turi būti skaičius.',
    'password' => 'Slaptažodis yra neteisingas.',
    'present' => 'Laukas :attribute turi būti.',
    'prohibited' => 'Laukas :attribute yra draudžiamas.',
    'prohibited_if' => 'Laukas :attribute yra draudžiamas, kai :other yra :value.',
    'prohibited_unless' => 'Laukas :attribute yra draudžiamas, nebent :other yra :values.',
    'prohibits' => 'Laukas :attribute draudžia :other būti.',
    'regex' => 'Lauko :attribute formatas yra neteisingas.',
    'required' => 'Laukas :attribute yra privalomas.',
    'required_array_keys' => 'Laukas :attribute turi turėti įrašus: :values.',
    'required_if' => 'Laukas :attribute yra privalomas, kai :other yra :value.',
    'required_unless' => 'Laukas :attribute yra privalomas, nebent :other yra :values.',
    'required_with' => 'Laukas :attribute yra privalomas, kai yra :values.',
    'required_with_all' => 'Laukas :attribute yra privalomas, kai yra :values.',
    'required_without' => 'Laukas :attribute yra privalomas, kai nėra :values.',
    'required_without_all' => 'Laukas :attribute yra privalomas, kai nėra nei vieno iš :values.',
    'same' => 'Laukai :attribute ir :other turi sutapti.',
    'size' => [
        'numeric' => 'Laukas :attribute turi būti :size.',
        'file' => 'Laukas :attribute turi būti :size kilobaitų.',
        'string' => 'Laukas :attribute turi būti :size simbolių.',
        'array' => 'Laukas :attribute turi turėti :size elementų.',
    ],
    'starts_with' => 'Laukas :attribute turi prasidėti vienu iš: :values.',
    'string' => 'Laukas :attribute turi būti eilutė.',
    'timezone' => 'Laukas :attribute turi būti galiojanti laiko juosta.',
    'unique' => 'Toks :attribute jau egzistuoja.',
    'uploaded' => 'Nepavyko įkelti :attribute.',
    'url' => 'Laukas :attribute turi būti galiojantis URL.',
    'uuid' => 'Laukas :attribute turi būti galiojantis UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],
];
