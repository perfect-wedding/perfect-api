<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class WordLimit implements InvokableRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    // public $implicit = true;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($max_words = 500, $indicator = '>')
    {
        $this->max_words = $max_words;
        $this->indicator = $indicator;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (is_array($this->indicator)) {
            foreach ($this->indicator as $indicator => $max_words) {
                if (($check = $this->check($value, $fail, $indicator, $max_words)) !== true) {
                    return $check;
                }
            }
        }

        return $this->check($value, $fail);
    }

    protected function check($value, $fail, $indicator = null, $max_words = null)
    {
        $words = str_word_count($value);
        $max_words = $max_words ?? $this->max_words;
        $indicator = $indicator ?? $this->indicator;

        if ($indicator == '>') {
            if ($words > $max_words) {
                $fail('The :attribute must be less than :count words.')->translate([
                    'count' => $max_words,
                ]);
            }
        } elseif ($indicator == '<') {
            if ($words < $max_words) {
                $fail('The :attribute must contain at least :count words.')->translate([
                    'count' => $max_words,
                ]);
            }
        } elseif ($indicator == '=') {
            if ($words != $max_words) {
                $fail('The :attribute must be exactly :count words.')->translate([
                    'count' => $max_words,
                ]);
            }
        }

        return true;
    }
}
