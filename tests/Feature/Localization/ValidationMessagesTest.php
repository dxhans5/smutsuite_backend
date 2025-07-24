<?php

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    #[Test]
    public function validation_messages_are_localized_in_english(): void
    {
        App::setLocale('en');

        $validator = Validator::make([
            'date_of_birth' => '',
        ], [
            'date_of_birth' => 'required|date',
        ]);

        $messages = $validator->errors()->get('date_of_birth');

        $this->assertContains('Your date of birth is required.', $messages);
    }

    #[Test]
    public function validation_messages_are_localized_in_german(): void
    {
        App::setLocale('de');

        $validator = Validator::make([
            'role' => 'invalid_value',
        ], [
            'role' => 'in:user,provider,host,creator',
        ]);

        $messages = $validator->errors()->get('role');

        $this->assertContains('Die ausgewählte Rolle ist ungültig.', $messages);
    }

    #[Test]
    public function validation_messages_are_localized_in_dutch(): void
    {
        App::setLocale('nl');

        $validator = Validator::make([
            'date_of_birth' => '',
        ], [
            'date_of_birth' => 'required|date',
        ]);

        $messages = $validator->errors()->get('date_of_birth');

        $this->assertContains('Je geboortedatum is verplicht.', $messages);
    }

    #[Test]
    public function validation_messages_are_localized_in_japanese(): void
    {
        App::setLocale('ja');

        $validator = Validator::make([
            'role' => 'invalid_value',
        ], [
            'role' => 'in:user,provider,host,creator',
        ]);

        $messages = $validator->errors()->get('role');

        $this->assertContains('選択された役割は無効です。', $messages);
    }

    #[Test]
    public function validation_messages_are_localized_in_french(): void
    {
        App::setLocale('fr');

        $validator = Validator::make([
            'date_of_birth' => '',
        ], [
            'date_of_birth' => 'required|date',
        ]);

        $messages = $validator->errors()->get('date_of_birth');

        $this->assertContains('Votre date de naissance est obligatoire.', $messages);
    }

    #[Test]
    public function validation_messages_are_localized_in_portuguese_brazil(): void
    {
        App::setLocale('pt_BR');

        $validator = Validator::make([
            'date_of_birth' => 'not-a-date',
        ], [
            'date_of_birth' => 'required|date',
        ]);

        $messages = $validator->errors()->get('date_of_birth');

        $this->assertContains('Sua data de nascimento deve ser uma data válida.', $messages);
    }

    #[Test]
    public function fallback_to_english_when_locale_does_not_exist(): void
    {
        App::setLocale('xx'); // deliberately missing

        $validator = Validator::make([
            'date_of_birth' => '',
        ], [
            'date_of_birth' => 'required|date',
        ]);

        $messages = $validator->errors()->get('date_of_birth');

        $this->assertContains('Your date of birth is required.', $messages);
    }
}
