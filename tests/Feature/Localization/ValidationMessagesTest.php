<?php

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ValidationMessagesTest
 *
 * Ensures custom validation messages are correctly localized across supported languages.
 * Falls back to English when locale is missing.
 */
class ValidationMessagesTest extends TestCase
{
    /**
     * Helper to assert validation messages for a given locale and rule.
     *
     * @param  string  $locale        Target locale (e.g. 'en', 'ja', 'de')
     * @param  array   $data          Input data for validation
     * @param  array   $rules         Validation rules
     * @param  string  $field         Field to inspect for error messages
     * @param  string  $expected      Expected localized message
     */
    protected function assertLocalizedMessage(string $locale, array $data, array $rules, string $field, string $expected): void
    {
        App::setLocale($locale);

        $validator = Validator::make($data, $rules);
        $messages  = $validator->errors()->get($field);

        $this->assertContains($expected, $messages, "Failed asserting localized message for [$locale:$field]");
    }

    #[Test]
    public function it_localizes_validation_messages_in_english(): void
    {
        $this->assertLocalizedMessage(
            'en',
            ['date_of_birth' => ''],
            ['date_of_birth' => 'required|date'],
            'date_of_birth',
            'Your date of birth is required.'
        );
    }

    #[Test]
    public function it_localizes_validation_messages_in_german(): void
    {
        $this->assertLocalizedMessage(
            'de',
            ['role' => 'invalid_value'],
            ['role' => 'in:user,provider,host,creator'],
            'role',
            'Ungültige Rolle ausgewählt.'
        );
    }

    #[Test]
    public function it_localizes_validation_messages_in_dutch(): void
    {
        $this->assertLocalizedMessage(
            'nl',
            ['date_of_birth' => ''],
            ['date_of_birth' => 'required|date'],
            'date_of_birth',
            'Je geboortedatum is verplicht.'
        );
    }

    #[Test]
    public function it_localizes_validation_messages_in_japanese(): void
    {
        $this->assertLocalizedMessage(
            'ja',
            ['role' => 'invalid_value'],
            ['role' => 'in:user,provider,host,creator'],
            'role',
            '選択された役割は無効です。'
        );
    }

    #[Test]
    public function it_localizes_validation_messages_in_french(): void
    {
        $this->assertLocalizedMessage(
            'fr',
            ['date_of_birth' => ''],
            ['date_of_birth' => 'required|date'],
            'date_of_birth',
            'Votre date de naissance est obligatoire.'
        );
    }

    #[Test]
    public function it_localizes_validation_messages_in_brazilian_portuguese(): void
    {
        $this->assertLocalizedMessage(
            'pt_BR',
            ['date_of_birth' => 'not-a-date'],
            ['date_of_birth' => 'required|date'],
            'date_of_birth',
            'Sua data de nascimento deve ser uma data válida.'
        );
    }

    #[Test]
    public function it_falls_back_to_english_when_locale_is_missing(): void
    {
        $this->assertLocalizedMessage(
            'xx',
            ['date_of_birth' => ''],
            ['date_of_birth' => 'required|date'],
            'date_of_birth',
            'Your date of birth is required.'
        );
    }
}
